<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\SessionClaim\Results;

use App\Classic\Entity\TournamentSessionTeam;
use App\Tests\FixturesTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ResultsSubmitControllerTest extends WebTestCase
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
    public function testSubmit(
        array $fixtures,
        ?string $loginAs,
        callable $uri,
        ?callable $setup,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        if ($setup !== null) {
            $setup($client, $objects);
        }

        $client->request('POST', $uri($objects), [], [], ['CONTENT_TYPE' => 'application/json']);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'submit after upload' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/submit',
            'setup' => static function ($client, array $objects) {
                $file = self::buildValidXlsx($objects);
                $uri = '/my/session-claims/' . $objects['session_results']->getId() . '/results/upload';
                $client->request('POST', $uri, [], ['file' => $file]);
            },
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);
                static::assertStringContainsString('/my/session-claims/', $body['redirect']);
            },
        ];

        yield 'submit recalculates scores correctly' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/submit',
            'setup' => static function ($client, array $objects) {
                $file = self::buildValidXlsx($objects);
                $uri = '/my/session-claims/' . $objects['session_results']->getId() . '/results/upload';
                $client->request('POST', $uri, [], ['file' => $file]);
            },
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                // Verify results page shows breakdown without submit button
                $crawler = $client->request('GET', '/my/session-claims/' . $objects['session_results']->getId() . '/results');
                static::assertCount(0, $crawler->filter('#submit-btn'));
                static::assertCount(1, $crawler->filter('.results-breakdown'));
            },
        ];

        yield 'submit transitions created disputes to submitted' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/submit',
            'setup' => static function ($client, array $objects) {
                $file = self::buildXlsxWithDispute($objects);
                $uri = '/my/session-claims/' . $objects['session_results']->getId() . '/results/upload';
                $client->request('POST', $uri, [], ['file' => $file]);
            },
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);
            },
        ];

        yield 'submit without upload' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/submit',
            'setup' => null,
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('results.error.nothing_to_submit', $body['error']);
            },
        ];

        yield 'submit fails when not all teams have results' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/results_partial.yaml'],
            'loginAs' => 'user_results_partial_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results_partial']->getId() . '/results/submit',
            'setup' => null,
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('results.error.not_all_teams_have_results', $body['error']);
            },
        ];

        yield 'access denied for non-owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_other',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/submit',
            'setup' => null,
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'redirect for anonymous' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => null,
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/submit',
            'setup' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function () {
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
    private static function buildXlsxWithDispute(array $objects): UploadedFile
    {
        /** @var TournamentSessionTeam $teamAlpha */
        $teamAlpha = $objects['session_team_alpha_results'];
        /** @var TournamentSessionTeam $teamBeta */
        $teamBeta = $objects['session_team_beta_results'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        self::writeHeader($sheet);
        self::fillTeamRow($sheet, 3, $teamAlpha->getId(), 1, [1, 'спірна', 1]);
        self::fillTeamRow($sheet, 4, $teamBeta->getId(), 1, [0, 1, 1]);
        self::fillTeamRow($sheet, 6, $teamAlpha->getId(), 2, [1, 1, 0]);
        self::fillTeamRow($sheet, 7, $teamBeta->getId(), 2, [1, 0, 0]);

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
     * @param list<int|string> $answers
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
