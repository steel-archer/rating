<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\SessionClaim;

use App\Entity\TournamentSessionTeam;
use App\Tests\FixturesTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ResultsUploadControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/results.yaml',
    ];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testUpload(
        array $fixtures,
        ?string $loginAs,
        callable $uri,
        callable $file,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $uploadedFile = $file($objects);

        $client->request(
            'POST',
            $uri($objects),
            [],
            $uploadedFile !== null ? ['file' => $uploadedFile] : [],
        );

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'upload valid file' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/upload',
            'file' => static fn(array $objects) => self::buildValidXlsx($objects),
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);
            },
        ];

        yield 'upload with invalid answer values' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/upload',
            'file' => static fn(array $objects) => self::buildXlsxWithInvalidValues($objects),
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertNotEmpty($body['errors']);
                static::assertStringContainsString('results.error.invalid_answer_value', $body['errors'][0]);
            },
        ];

        yield 'upload with missing team' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/upload',
            'file' => static fn(array $objects) => self::buildXlsxWithOneTeam($objects),
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertNotEmpty($body['errors']);
                static::assertStringContainsString('results.error.missing_team_results', $body['errors'][0]);
            },
        ];

        yield 'upload with unknown team id' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/upload',
            'file' => static fn(array $objects) => self::buildXlsxWithUnknownTeam($objects),
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertNotEmpty($body['errors']);
                static::assertStringContainsString('results.error.unknown_team', $body['errors'][0]);
            },
        ];

        yield 'upload with invalid tour number' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/upload',
            'file' => static fn(array $objects) => self::buildXlsxWithInvalidTour($objects),
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertNotEmpty($body['errors']);
                static::assertStringContainsString('results.error.invalid_tour', $body['errors'][0]);
            },
        ];

        yield 'upload invalid file format' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/upload',
            'file' => static fn(array $objects) => self::buildInvalidFormatFile(),
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertContains('results.error.invalid_file_format', $body['errors']);
            },
        ];

        yield 'upload no file' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/upload',
            'file' => static fn(array $objects) => null,
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertContains('results.error.no_file', $body['errors']);
            },
        ];

        yield 'reupload overwrites previous results' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/upload',
            'file' => static fn(array $objects) => self::buildValidXlsx($objects),
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                // Upload again with different data
                $file = self::buildAlternativeValidXlsx($objects);
                $uri = '/my/session-claims/' . $objects['session_results']->getId() . '/results/upload';
                $client->request('POST', $uri, [], ['file' => $file]);
                static::assertResponseStatusCodeSame(200);

                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);
            },
        ];

        yield 'access denied for non-owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_other',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/upload',
            'file' => static fn(array $objects) => self::buildValidXlsx($objects),
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'redirect for anonymous' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => null,
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/upload',
            'file' => static fn(array $objects) => self::buildValidXlsx($objects),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'upload fails when session has no teams' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/results_empty.yaml'],
            'loginAs' => 'user_results_empty_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results_empty']->getId() . '/results/upload',
            'file' => static fn(array $objects) => self::buildMinimalXlsx(),
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertContains('results.error.no_teams', $body['errors']);
            },
        ];
    }

    /**
     * @param array<string, object> $objects
     */
    private static function buildValidXlsx(array $objects): UploadedFile
    {
        /** @var TournamentSessionTeam $teamAlpha */
        $teamAlpha = $objects['session_team_alpha_results'];
        /** @var TournamentSessionTeam $teamBeta */
        $teamBeta = $objects['session_team_beta_results'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        self::writeHeader($sheet);
        self::fillTeamRow($sheet, 3, $teamAlpha->getId(), 1, [1, 0, 1]);
        self::fillTeamRow($sheet, 4, $teamBeta->getId(), 1, [0, 1, 1]);
        self::fillTeamRow($sheet, 6, $teamAlpha->getId(), 2, [1, 1, 0]);
        self::fillTeamRow($sheet, 7, $teamBeta->getId(), 2, [1, 0, 0]);

        return self::toUploadedFile($spreadsheet);
    }

    /**
     * @param array<string, object> $objects
     */
    private static function buildXlsxWithInvalidValues(array $objects): UploadedFile
    {
        /** @var TournamentSessionTeam $teamAlpha */
        $teamAlpha = $objects['session_team_alpha_results'];
        /** @var TournamentSessionTeam $teamBeta */
        $teamBeta = $objects['session_team_beta_results'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        self::writeHeader($sheet);
        self::fillTeamRow($sheet, 3, $teamAlpha->getId(), 1, [1, 2, 1]);
        self::fillTeamRow($sheet, 4, $teamBeta->getId(), 1, [0, 1, 1]);
        self::fillTeamRow($sheet, 6, $teamAlpha->getId(), 2, [1, 1, 0]);
        self::fillTeamRow($sheet, 7, $teamBeta->getId(), 2, [1, 0, 0]);

        return self::toUploadedFile($spreadsheet);
    }

    /**
     * @param array<string, object> $objects
     */
    private static function buildXlsxWithOneTeam(array $objects): UploadedFile
    {
        /** @var TournamentSessionTeam $teamAlpha */
        $teamAlpha = $objects['session_team_alpha_results'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        self::writeHeader($sheet);
        self::fillTeamRow($sheet, 3, $teamAlpha->getId(), 1, [1, 0, 1]);
        self::fillTeamRow($sheet, 5, $teamAlpha->getId(), 2, [1, 1, 0]);

        return self::toUploadedFile($spreadsheet);
    }

    /**
     * @param array<string, object> $objects
     */
    private static function buildXlsxWithInvalidTour(array $objects): UploadedFile
    {
        /** @var TournamentSessionTeam $teamAlpha */
        $teamAlpha = $objects['session_team_alpha_results'];
        /** @var TournamentSessionTeam $teamBeta */
        $teamBeta = $objects['session_team_beta_results'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        self::writeHeader($sheet);
        self::fillTeamRow($sheet, 3, $teamAlpha->getId(), 1, [1, 0, 1]);
        self::fillTeamRow($sheet, 4, $teamBeta->getId(), 1, [0, 1, 1]);
        self::fillTeamRow($sheet, 6, $teamAlpha->getId(), 99, [1, 1, 0]);
        self::fillTeamRow($sheet, 7, $teamBeta->getId(), 2, [1, 0, 0]);

        return self::toUploadedFile($spreadsheet);
    }

    /**
     * @param array<string, object> $objects
     */
    private static function buildAlternativeValidXlsx(array $objects): UploadedFile
    {
        /** @var TournamentSessionTeam $teamAlpha */
        $teamAlpha = $objects['session_team_alpha_results'];
        /** @var TournamentSessionTeam $teamBeta */
        $teamBeta = $objects['session_team_beta_results'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        self::writeHeader($sheet);
        self::fillTeamRow($sheet, 3, $teamAlpha->getId(), 1, [0, 0, 0]);
        self::fillTeamRow($sheet, 4, $teamBeta->getId(), 1, [1, 1, 1]);
        self::fillTeamRow($sheet, 6, $teamAlpha->getId(), 2, [0, 0, 0]);
        self::fillTeamRow($sheet, 7, $teamBeta->getId(), 2, [1, 1, 1]);

        return self::toUploadedFile($spreadsheet);
    }

    private static function buildInvalidFormatFile(): UploadedFile
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_results_') . '.txt';
        file_put_contents($tempFile, 'not a spreadsheet');

        return new UploadedFile($tempFile, 'results.txt', 'text/plain', null, true);
    }

    private static function buildMinimalXlsx(): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        self::writeHeader($sheet);
        self::fillTeamRow($sheet, 3, 1, 1, [1, 0, 1]);

        return self::toUploadedFile($spreadsheet);
    }

    /**
     * @param array<string, object> $objects
     */
    private static function buildXlsxWithUnknownTeam(array $objects): UploadedFile
    {
        /** @var TournamentSessionTeam $teamAlpha */
        $teamAlpha = $objects['session_team_alpha_results'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        self::writeHeader($sheet);
        self::fillTeamRow($sheet, 3, $teamAlpha->getId(), 1, [1, 0, 1]);
        self::fillTeamRow($sheet, 4, 999999, 1, [0, 1, 1]);
        self::fillTeamRow($sheet, 6, $teamAlpha->getId(), 2, [1, 1, 0]);
        self::fillTeamRow($sheet, 7, 999999, 2, [1, 0, 0]);

        return self::toUploadedFile($spreadsheet);
    }

    private static function writeHeader(Worksheet $sheet): void
    {
        $sheet->setCellValue('A2', 'Team ID');
        $sheet->setCellValue('B2', 'Назва');
        $sheet->setCellValue('C2', 'Місто');
        $sheet->setCellValue('D2', 'Тур');
        $sheet->setCellValue('E2', 1);
        $sheet->setCellValue('F2', 2);
        $sheet->setCellValue('G2', 3);
    }

    /**
     * @param list<int> $answers
     */
    private static function fillTeamRow(Worksheet $sheet, int $row, int $teamId, int $tour, array $answers): void
    {
        $sheet->setCellValue('A' . $row, $teamId);
        $sheet->setCellValue('B' . $row, 'Team');
        $sheet->setCellValue('C' . $row, 'Town');
        $sheet->setCellValue('D' . $row, $tour);

        foreach ($answers as $index => $value) {
            $sheet->setCellValue(chr(ord('E') + $index) . $row, $value);
        }
    }

    private static function toUploadedFile(Spreadsheet $spreadsheet): UploadedFile
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_results_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempFile);

        return new UploadedFile(
            $tempFile,
            'results.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }
}
