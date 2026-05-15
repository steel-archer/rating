<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TournamentSession;
use App\Entity\TournamentSessionTeam;
use App\Entity\TournamentSessionTeamAnswer;
use App\Enum\DisputeStatus;
use App\Repository\TournamentSessionTeamAnswerRepository;
use App\Repository\TournamentSessionTeamRepository;
use App\Service\Cache\CacheInvalidator;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SessionResultUploadService
{
    private const string MIME_XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    private const int MAX_SIZE_BYTES = 5 * 1024 * 1024;

    public function __construct(
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private TournamentSessionTeamAnswerRepository $answerRepository,
        private EntityManagerInterface $em,
        private CacheInvalidator $cacheInvalidator,
    ) {
    }

    /**
     * @throws LogicException
     */
    public function generateTemplate(TournamentSession $session): StreamedResponse
    {
        $tournament = $session->getTournament();
        $toursCount = $tournament->getToursCount()
            ?? throw new LogicException('results.error.no_tours_count');
        $questionsPerTour = $tournament->getQuestionsPerTour()
            ?? throw new LogicException('results.error.no_questions_per_tour');

        $sessionTeams = $this->sessionTeamRepository->findBySessionWithTeamAndTown($session);

        if ($sessionTeams === []) {
            throw new LogicException('results.error.no_teams');
        }

        // Pre-fill with existing unsubmitted answers
        $answerMap = $this->buildAnswerMap($sessionTeams);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header row (row 2, row 1 is empty)
        $headerRow = 2;
        $sheet->setCellValue('A' . $headerRow, 'Team ID');
        $sheet->setCellValue('B' . $headerRow, 'Назва');
        $sheet->setCellValue('C' . $headerRow, 'Місто');
        $sheet->setCellValue('D' . $headerRow, 'Тур');
        for ($q = 1; $q <= $questionsPerTour; $q++) {
            $col = $this->questionColumn($q);
            $sheet->setCellValue($col . $headerRow, $q);
        }

        $currentRow = $headerRow + 1;
        for ($tour = 1; $tour <= $toursCount; $tour++) {
            foreach ($sessionTeams as $sessionTeam) {
                $team = $sessionTeam->getTeam();
                $teamId = $sessionTeam->getId();
                $sheet->setCellValue('A' . $currentRow, $teamId);
                $sheet->setCellValue('B' . $currentRow, $sessionTeam->getOneTimeName() ?? $team->getName());
                $sheet->setCellValue('C' . $currentRow, $team->getTown()->getName());
                $sheet->setCellValue('D' . $currentRow, $tour);

                for ($q = 1; $q <= $questionsPerTour; $q++) {
                    $questionNumber = ($tour - 1) * $questionsPerTour + $q;
                    $col = $this->questionColumn($q);
                    $value = $answerMap[$teamId][$questionNumber] ?? 0;
                    $sheet->setCellValue($col . $currentRow, $value);
                }

                $currentRow++;
            }
            // Empty row between tours
            $currentRow++;
        }

        $playedAt = $session->getPlayedAt()?->format('d-M-Y') ?? 'no-date';
        $filename = "results-{$session->getId()}-$playedAt.xlsx";

        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(
            static function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => self::MIME_XLSX,
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'Cache-Control' => 'max-age=0',
            ],
        );
    }

    /**
     * @return list<string>
     *
     * @throws LogicException
     */
    public function uploadResults(TournamentSession $session, UploadedFile $file): array
    {
        $errors = $this->validateFile($file);
        if ($errors !== []) {
            return $errors;
        }

        $tournament = $session->getTournament();
        $toursCount = $tournament->getToursCount()
            ?? throw new LogicException('results.error.no_tours_count');
        $questionsPerTour = $tournament->getQuestionsPerTour()
            ?? throw new LogicException('results.error.no_questions_per_tour');

        $sessionTeams = $this->sessionTeamRepository->findBy(
            ['tournamentSession' => $session],
        );

        if ($sessionTeams === []) {
            throw new LogicException('results.error.no_teams');
        }

        $sessionTeamsById = [];
        foreach ($sessionTeams as $sessionTeam) {
            $sessionTeamsById[$sessionTeam->getId()] = $sessionTeam;
        }

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
        } catch (Throwable) {
            return ['results.error.invalid_file_format'];
        }

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $parsedResults = [];

        for ($row = 3; $row <= $highestRow; $row++) {
            $teamIdCell = $sheet->getCell('A' . $row)->getValue();
            if ($teamIdCell === null || $teamIdCell === '') {
                continue;
            }

            $teamId = (int) $teamIdCell;
            if (!isset($sessionTeamsById[$teamId])) {
                $errors[] = "results.error.unknown_team:$teamId";
                continue;
            }

            $tourNumber = (int) $sheet->getCell('D' . $row)->getValue();
            if ($tourNumber < 1 || $tourNumber > $toursCount) {
                $errors[] = "results.error.invalid_tour:$tourNumber";
                continue;
            }

            for ($q = 1; $q <= $questionsPerTour; $q++) {
                $col = $this->questionColumn($q);
                $value = $sheet->getCell($col . $row)->getValue();

                if ($value === null || $value === '') {
                    $value = 0;
                }

                $stringValue = trim((string) $value);
                $questionNumber = ($tourNumber - 1) * $questionsPerTour + $q;

                if ($stringValue === '0' || $stringValue === '1') {
                    $parsedResults[$teamId][$questionNumber] = [
                        'isCorrect' => $stringValue === '1',
                        'disputeText' => null,
                    ];
                } else {
                    $parsedResults[$teamId][$questionNumber] = [
                        'isCorrect' => false,
                        'disputeText' => $stringValue,
                    ];
                }
            }
        }

        // Validate all teams have results
        $totalQuestions = $toursCount * $questionsPerTour;
        foreach ($sessionTeamsById as $teamId => $sessionTeam) {
            if (!isset($parsedResults[$teamId])) {
                $teamName = $sessionTeam->getOneTimeName() ?? $sessionTeam->getTeam()->getName();
                $errors[] = "results.error.missing_team_results:$teamName";
            } elseif (count($parsedResults[$teamId]) !== $totalQuestions) {
                $teamName = $sessionTeam->getOneTimeName() ?? $sessionTeam->getTeam()->getName();
                $got = count($parsedResults[$teamId]);
                $errors[] = "results.error.incomplete_results:$teamName:$got:$totalQuestions";
            }
        }

        // Validate max one dispute per team per question (enforced by structure)
        // Validate dispute text length
        foreach ($parsedResults as $answers) {
            foreach ($answers as $answerData) {
                if ($answerData['disputeText'] !== null && mb_strlen($answerData['disputeText']) > 500) {
                    $errors[] = 'dispute.error.text_too_long';
                    break 2;
                }
            }
        }

        if ($errors !== []) {
            return $errors;
        }

        // Save results (not submitted yet)
        $this->saveResults($sessionTeamsById, $parsedResults);

        return [];
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function submitResults(TournamentSession $session): void
    {
        $sessionTeams = $this->sessionTeamRepository->findBy(
            ['tournamentSession' => $session],
        );

        $sessionTeamIds = array_map(static fn($st) => $st->getId(), $sessionTeams);
        $answerCounts = $this->getAnswerCounts($sessionTeamIds);

        $hasUnsubmitted = array_any(
            $sessionTeams,
            static fn(TournamentSessionTeam $st) => !$st->isResultsSubmitted() && ($answerCounts[$st->getId()] ?? 0) > 0,
        );

        if (!$hasUnsubmitted) {
            throw new LogicException('results.error.nothing_to_submit');
        }

        foreach ($sessionTeams as $sessionTeam) {
            if (($answerCounts[$sessionTeam->getId()] ?? 0) === 0) {
                throw new LogicException('results.error.not_all_teams_have_results');
            }
            $sessionTeam->setResultsSubmitted(true);
        }

        $this->recalculateScores($sessionTeamIds);
        $this->submitCreatedDisputes($sessionTeamIds);
        $this->em->flush();
        $this->cacheInvalidator->invalidateTournamentWithParticipants($session->getTournament());
    }

    /**
     * @param list<int> $sessionTeamIds
     */
    private function submitCreatedDisputes(array $sessionTeamIds): void
    {
        $this->answerRepository->submitCreatedDisputes($sessionTeamIds);
    }

    /**
     * @param list<int> $sessionTeamIds
     * @return array<int, int> sessionTeamId => count
     */
    private function getAnswerCounts(array $sessionTeamIds): array
    {
        if ($sessionTeamIds === []) {
            return [];
        }

        $rows = $this->answerRepository->createQueryBuilder('a')
            ->select('IDENTITY(a.tournamentSessionTeam) AS teamId', 'COUNT(a.id) AS cnt')
            ->where('a.tournamentSessionTeam IN (:ids)')
            ->setParameter('ids', $sessionTeamIds)
            ->groupBy('a.tournamentSessionTeam')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['teamId']] = (int) $row['cnt'];
        }

        return $result;
    }

    /**
     * @param list<int> $sessionTeamIds
     */
    private function recalculateScores(array $sessionTeamIds): void
    {
        if ($sessionTeamIds === []) {
            return;
        }

        $rows = $this->answerRepository->createQueryBuilder('a')
            ->select('IDENTITY(a.tournamentSessionTeam) AS teamId', 'SUM(CASE WHEN a.isCorrect = true THEN 1 ELSE 0 END) AS score')
            ->where('a.tournamentSessionTeam IN (:ids)')
            ->setParameter('ids', $sessionTeamIds)
            ->groupBy('a.tournamentSessionTeam')
            ->getQuery()
            ->getArrayResult();

        $scoreMap = [];
        foreach ($rows as $row) {
            $scoreMap[(int) $row['teamId']] = (int) $row['score'];
        }

        $sessionTeams = $this->sessionTeamRepository->findBy(['id' => $sessionTeamIds]);
        foreach ($sessionTeams as $sessionTeam) {
            $sessionTeam->setScore($scoreMap[$sessionTeam->getId()] ?? 0);
        }
    }

    /**
     * @param array<int, TournamentSessionTeam> $sessionTeamsById
     * @param array<int, array<int, array{isCorrect: bool, disputeText: ?string}>> $parsedResults
     */
    private function saveResults(array $sessionTeamsById, array $parsedResults): void
    {
        $teamIds = array_keys($parsedResults);

        // Bulk delete existing answers
        $this->answerRepository->createQueryBuilder('a')
            ->delete()
            ->where('a.tournamentSessionTeam IN (:ids)')
            ->setParameter('ids', $teamIds)
            ->getQuery()
            ->execute();

        // Insert new answers
        foreach ($parsedResults as $teamId => $answers) {
            $sessionTeam = $sessionTeamsById[$teamId];
            $sessionTeam->setResultsSubmitted(false);
            $sessionTeam->getAnswers()->clear();

            foreach ($answers as $questionNumber => $answerData) {
                $answer = new TournamentSessionTeamAnswer();
                $answer->setTournamentSessionTeam($sessionTeam);
                $answer->setQuestionNumber($questionNumber);
                $answer->setIsCorrect($answerData['isCorrect']);

                if ($answerData['disputeText'] !== null) {
                    $answer->setDisputeText($answerData['disputeText']);
                    $answer->setDisputeStatus(DisputeStatus::Created);
                }

                $sessionTeam->getAnswers()->add($answer);
                $this->em->persist($answer);
            }

            $sessionTeam->recalculateScore();
        }

        $this->em->flush();
    }

    /**
     * @return list<string>
     */
    private function validateFile(UploadedFile $file): array
    {
        $errors = [];

        $mimeType = $file->getMimeType() ?? '';
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension !== 'xlsx' || $mimeType !== self::MIME_XLSX) {
            $errors[] = 'results.error.invalid_file_format';
        }

        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            $errors[] = 'results.error.file_too_large';
        }

        return $errors;
    }

    /**
     * @param list<TournamentSessionTeam> $sessionTeams
     * @return array<int, array<int, int|string>> teamId => [questionNumber => 0|1|disputeText]
     */
    private function buildAnswerMap(array $sessionTeams): array
    {
        $ids = array_map(static fn($st) => $st->getId(), $sessionTeams);

        if ($ids === []) {
            return [];
        }

        $rows = $this->answerRepository->createQueryBuilder('a')
            ->select(
                'IDENTITY(a.tournamentSessionTeam) AS teamId',
                'a.questionNumber',
                'a.isCorrect',
                'a.disputeText',
            )
            ->where('a.tournamentSessionTeam IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $teamId = (int) $row['teamId'];
            $questionNumber = (int) $row['questionNumber'];

            if ($row['disputeText'] !== null) {
                $map[$teamId][$questionNumber] = $row['disputeText'];
            } else {
                $map[$teamId][$questionNumber] = $row['isCorrect'] ? 1 : 0;
            }
        }

        return $map;
    }

    /**
     * @throws LogicException
     */
    private function questionColumn(int $questionNumber): string
    {
        // Questions start at column E (5th column)
        return chr(ord('E') + $questionNumber - 1 > ord('Z')
            ? throw new LogicException('Too many questions per tour')
            : ord('E') + $questionNumber - 1);
    }
}
