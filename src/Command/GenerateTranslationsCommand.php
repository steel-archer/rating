<?php

namespace App\Command;

use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(name: 'app:generate-translations', description: 'Generate JS translations file')]
final class GenerateTranslationsCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    /**
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $yaml = Yaml::parseFile($this->projectDir . '/translations/messages.uk.yaml');
        $flat = $this->flatten($yaml);

        $json = json_encode($flat, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $js = "export default " . $json . ";\n";

        file_put_contents($this->projectDir . '/assets/translations.js', $js);

        $output->writeln('<info>Generated assets/translations.js</info>');

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $array
     * @return array<string, string>
     */
    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $fullKey = $prefix !== '' ? $prefix . '.' . $key : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flatten($value, $fullKey));
            } else {
                $result[$fullKey] = (string) $value;
            }
        }

        return $result;
    }
}
