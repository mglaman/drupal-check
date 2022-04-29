<?php declare(strict_types=1);

namespace DrupalCheck\Command;

use DrupalFinder\DrupalFinder;
use Nette\Neon\Neon;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CheckCommand extends Command
{
    private $isDeprecationsCheck = false;
    private $isAnalysisCheck = false;
    private $isStyleCheck = false;
    private $memoryLimit;
    private $drupalRoot;
    private $vendorRoot;
    private $excludeDirectory;

    protected function configure(): void
    {
        $this
            ->setName('check')
            ->setDescription('Checks a Drupal site')
            ->addArgument('path', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The Drupal code path(s) to inspect')
            ->addOption('drupal-root', null, InputOption::VALUE_OPTIONAL, 'Path to Drupal root.')
            ->addOption('format', null, InputOption::VALUE_OPTIONAL, 'Formatter to use: raw, table, checkstyle, json, or junit', 'table')
            ->addOption('deprecations', 'd', InputOption::VALUE_NONE, 'Check for deprecations')
            ->addOption('analysis', 'a', InputOption::VALUE_NONE, 'Check code analysis')
            ->addOption('style', 's', InputOption::VALUE_NONE, 'Check code style')
            ->addOption('php8', null, InputOption::VALUE_NONE, 'Set PHPStan phpVersion for 8.1 (Drupal 10 requirement)')
            ->addOption('memory-limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit for analysis')
            ->addOption('exclude-dir', 'e', InputOption::VALUE_OPTIONAL, 'Directories to exclude. Separate multiple directories with a comma, no spaces.')
            ->addOption(
                'no-progress',
                null,
                InputOption::VALUE_NONE,
                'Do not show progress bar, only results'
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->isDeprecationsCheck = $input->getOption('deprecations');
        $this->isAnalysisCheck = $input->getOption('analysis');
        $this->isStyleCheck = $input->getOption('style');
        $this->memoryLimit = $input->getOption('memory-limit');
        $this->excludeDirectory = $input->getOption('exclude-dir');

        // Default to deprecations.
        if (!$this->isDeprecationsCheck) {
            if (!$this->isAnalysisCheck && !$this->isStyleCheck) {
                $this->isDeprecationsCheck = true;
            } else {
                $this->isDeprecationsCheck = false;
            }
        }

        if ($this->memoryLimit) {
            $output->writeln("<comment>Memory limit set to $this->memoryLimit", OutputInterface::VERBOSITY_DEBUG);
        }
        if ($this->isDeprecationsCheck) {
            $output->writeln('<comment>Performing deprecation checks', OutputInterface::VERBOSITY_DEBUG);
        }
        if ($this->isAnalysisCheck) {
            $output->writeln('<comment>Performing analysis checks', OutputInterface::VERBOSITY_DEBUG);
        }
        if ($this->isStyleCheck) {
            $output->writeln('<comment>Performing code styling checks', OutputInterface::VERBOSITY_DEBUG);
        }

        if ($input->getOption('format') === 'json') {
            $input->setOption('format', 'prettyJson');
        }
        if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $this->getApplication()->setCatchExceptions(false);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $drupalFinder = new DrupalFinder();

        $paths = [];
        foreach ($input->getArgument('path') as $path) {
            $realPath = realpath($path);
            if (!$realPath) {
                $output->writeln(sprintf('<error>%s does not exist</error>', $path));
                return 1;
            }

            $output->writeln(sprintf('<comment>Analyzing path: %s</comment>', $realPath), OutputInterface::VERBOSITY_DEBUG);
            $paths[] = $realPath;
        }

        $drupalRootCandidate = $paths[0];

        if (!empty($input->getOption('drupal-root'))) {
            $drupalRootCandidate = realpath($input->getOption('drupal-root'));
            if ($drupalRootCandidate === false) {
                $output->writeln(sprintf('<error>%s does not exist</error>', $input->getOption('drupal-root')));
                return 1;
            }
        }

        if ($drupalFinder->locateRoot($drupalRootCandidate)) {
            $this->drupalRoot = realpath($drupalFinder->getDrupalRoot());
            $this->vendorRoot = realpath($drupalFinder->getVendorDir());
        }

        if (!$this->drupalRoot) {
            $output->writeln(sprintf('<error>Unable to locate the Drupal root in %s</error>', $drupalRootCandidate));
            return 1;
        }

        $output->writeln(sprintf('<comment>Current working directory: %s</comment>', getcwd()), OutputInterface::VERBOSITY_DEBUG);
        $output->writeln(sprintf('<info>Using Drupal root: %s</info>', $this->drupalRoot), OutputInterface::VERBOSITY_DEBUG);
        $output->writeln(sprintf('<info>Using vendor root: %s</info>', $this->vendorRoot), OutputInterface::VERBOSITY_DEBUG);
        if (!is_file($this->vendorRoot . '/autoload.php')) {
            $output->writeln('<error>Could not find autoload file.</error>');
            return 1;
        }


        $configuration_data = [
            'parameters' => [
                'tipsOfTheDay' => false,
                'reportUnmatchedIgnoredErrors' => false,
                'excludePaths' => [
                    '*/tests/Drupal/Tests/Listeners/Legacy/*',
                    '*/tests/fixtures/*.php',
                    '*/settings*.php',
                    '*/node_modules/*'
                ],
                'ignoreErrors' => [
                    '#Unsafe usage of new static\(\)#'
                ],
                'drupal' => [
                    'drupal_root' => $this->drupalRoot,
                ]
            ]
        ];

        if ($input->getOption('php8')) {
            $configuration_data['parameters']['phpVersion'] = 80100;
        }

        if (!empty($this->excludeDirectory)) {
            // There may be more than one path passed in, comma separated.
            $excluded_directories = explode(',', $this->excludeDirectory);
            $configuration_data['parameters']['excludePaths'] = array_merge($excluded_directories, $configuration_data['parameters']['excludePaths']);
        }

        if ($this->isAnalysisCheck) {
            $configuration_data['parameters']['level'] = 6;
            $ignored_analysis_errors = [];
            $configuration_data['parameters']['ignoreErrors'] = array_merge($ignored_analysis_errors, $configuration_data['parameters']['ignoreErrors']);
        } else {
            $configuration_data['parameters']['level'] = 2;
        }

        if ($this->isDeprecationsCheck) {
            $ignored_deprecation_errors = [
                '#\Drupal calls should be avoided in classes, use dependency injection instead#',
                '#Plugin definitions cannot be altered.#',
                '#Missing cache backend declaration for performance.#',
                '#Plugin manager has cache backend specified but does not declare cache tags.#'
            ];
            $configuration_data['parameters']['ignoreErrors'] = array_merge($ignored_deprecation_errors, $configuration_data['parameters']['ignoreErrors']);
        }

        if ($this->isStyleCheck) {
            // @todo: only analysis check, style check. all of the above at once.
            $output->writeln('Not support, yet');
            return 1;
        }

        if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
            // Running as a project dependency.
            $output->writeln('<comment>Assumed running as local dependency</comment>', OutputInterface::VERBOSITY_DEBUG);
            $phpstanBin = \realpath(__DIR__ . '/../../vendor/phpstan/phpstan/phpstan.phar');
            $configuration_data['parameters']['bootstrapFiles'] = [\realpath(__DIR__ . '/../../error-bootstrap.php')];
            if (!class_exists('PHPStan\ExtensionInstaller\GeneratedConfig')) {
                $configuration_data['includes'] = [
                    \realpath(__DIR__ . '/../../vendor/phpstan/phpstan-deprecation-rules/rules.neon'),
                    \realpath(__DIR__ . '/../../vendor/mglaman/phpstan-drupal/extension.neon'),
                ];
            }
        } elseif (file_exists(__DIR__ . '/../../../../autoload.php')) {
            // Running as a global dependency.
            $output->writeln('<comment>Assumed running as global dependency</comment>', OutputInterface::VERBOSITY_DEBUG);
            $phpstanBin = \realpath(__DIR__ . '/../../../../phpstan/phpstan/phpstan.phar');
            $configuration_data['parameters']['bootstrapFiles'] = [\realpath(__DIR__ . '/../../error-bootstrap.php')];
            if (!class_exists('PHPStan\ExtensionInstaller\GeneratedConfig')) {
                $configuration_data['includes'] = [
                    \realpath(__DIR__ . '/../../../../phpstan/phpstan-deprecation-rules/rules.neon'),
                    \realpath(__DIR__ . '/../../../../mglaman/phpstan-drupal/extension.neon'),
                ];
            }
        } else {
            throw new ShouldNotHappenException('Could not determine if local or global installation');
        }

        if (!file_exists($phpstanBin)) {
            $output->writeln('Could not find PHPStan at ' . $phpstanBin);
            return 1;
        }

        $output->writeln(sprintf('<comment>PHPStan path: %s</comment>', $phpstanBin), OutputInterface::VERBOSITY_DEBUG);
        $configuration_encoded = Neon::encode($configuration_data, Neon::BLOCK);
        $configuration = sys_get_temp_dir() . '/drupal_check_phpstan_' . time() . '.neon';
        file_put_contents($configuration, $configuration_encoded);
        $configuration = realpath($configuration);
        $output->writeln(sprintf('<comment>PHPStan configuration path: %s</comment>', $configuration), OutputInterface::VERBOSITY_DEBUG);

        $output->writeln('<comment>PHPStan configuration:</comment>', OutputInterface::VERBOSITY_DEBUG);
        $output->writeln($configuration_encoded, OutputInterface::VERBOSITY_DEBUG);

        $command = [
            $phpstanBin,
            'analyse',
            '-c',
            $configuration,
            '--error-format=' . $input->getOption('format')
        ];

        if (substr(PHP_OS, 0, 3) == 'WIN') {
            array_unshift($command, 'php');
        }

        if ($input->getOption('no-progress')) {
            $command[] = '--no-progress';
        }
        if ($input->getOption('memory-limit')) {
            $command[] = '--memory-limit=' . $input->getOption('memory-limit');
        }

        if ($output->getVerbosity() === OutputInterface::VERBOSITY_VERBOSE) {
            $command[] = '-v';
        } elseif ($output->getVerbosity() === OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $command[] = '-vv';
        } elseif ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $command[] = '-vvv';
        }
        $command = array_merge($command, $paths);

        $process = new Process($command);
        $process->setTimeout(null);

        $output->writeln('<comment>Executing PHPStan</comment>', OutputInterface::VERBOSITY_DEBUG);
        $process->run(static function ($type, $buffer) use ($output) {
            if (Process::ERR === $type) {
                $output->getErrorOutput()->write($buffer, false, OutputInterface::OUTPUT_RAW);
            } else {
                $output->write($buffer, false, OutputInterface::OUTPUT_RAW);
            }
        });
        $output->writeln('<comment>Finished executing PHPStan</comment>', OutputInterface::VERBOSITY_DEBUG);
        $output->writeln('<comment>Unlinking PHPStan configuration</comment>', OutputInterface::VERBOSITY_DEBUG);
        unlink($configuration);

        $output->writeln('<comment>Return PHPStan exit code</comment>', OutputInterface::VERBOSITY_DEBUG);

        if ($output instanceof ConsoleOutputInterface) {
            $stderr = $output->getErrorOutput();
            $stderr->writeln('Thanks for using <info>drupal-check</info>!');
            $stderr->writeln('');
            $stderr->writeln('Consider sponsoring the development of the maintainers which make <options=bold>drupal-check</> possible:');
            $stderr->writeln('');
            $stderr->writeln('- <options=bold>phpstan (ondrejmirtes)</>: https://github.com/sponsors/ondrejmirtes');
            $stderr->writeln('- <options=bold>phpstan-deprecation-rules (ondrejmirtes))</>: https://github.com/sponsors/ondrejmirtes');
            $stderr->writeln('- <options=bold>phpstan-drupal (mglaman))</>: https://github.com/sponsors/mglaman');
            $stderr->writeln('- <options=bold>drupal-check (mglaman))</>: https://github.com/sponsors/mglaman');
        }

        return $process->getExitCode();
    }
}
