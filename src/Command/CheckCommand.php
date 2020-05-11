<?php declare(strict_types=1);

namespace DrupalCheck\Command;

use DrupalCheck\Util\Tty;
use DrupalFinder\DrupalFinder;
use Nette\Neon\Neon;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption('memory-limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit for analysis')
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

        // Default to deprecations.
        if (!$this->isDeprecationsCheck) {
            if (!$this->isAnalysisCheck && !$this->isStyleCheck) {
                $this->isDeprecationsCheck = true;
            } else {
                $this->isDeprecationsCheck = false;
            }
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

        $drupalFinder->locateRoot($drupalRootCandidate);
        $this->drupalRoot = $drupalFinder->getDrupalRoot();
        $this->vendorRoot = $drupalFinder->getVendorDir();

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
                'excludes_analyse' => [
                    '*/tests/Drupal/Tests/Listeners/Legacy/*',
                    '*/tests/fixtures/*.php',
                    '*/settings*.php',
                ],
                'drupal' => [
                    'drupal_root' => $this->drupalRoot,
                ]
            ]
        ];

        if ($this->isAnalysisCheck) {
            $configuration_data['parameters']['level'] = 4;
        } else {
            $configuration_data['parameters']['customRulesetUsed'] = true;
        }

        if ($this->isDeprecationsCheck) {
            $configuration_data['parameters']['ignoreErrors'] = [
                '#\Drupal calls should be avoided in classes, use dependency injection instead#',
                '#Plugin definitions cannot be altered.#',
                '#Missing cache backend declaration for performance.#',
                '#Plugin manager has cache backend specified but does not declare cache tags.#'
            ];
        }

        if ($this->isStyleCheck) {
            // @todo: only analysis check, style check. all of the above at once.
            $output->writeln('Not support, yet');
            return 1;
        }

        $pharPath = \Phar::running();
        if ($pharPath !== '') {
            // Running in packaged Phar archive.
            $phpstanBin = 'vendor/phpstan/phpstan/phpstan';
            $configuration_data['parameters']['bootstrap'] = $pharPath . '/error-bootstrap.php';
            $configuration_data['includes'] = [
                $pharPath . '/vendor/phpstan/phpstan-deprecation-rules/rules.neon',
                $pharPath . '/vendor/mglaman/phpstan-drupal/extension.neon',
            ];
        } elseif (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
            // Running as a project dependency.
            $phpstanBin = __DIR__ . '/../../vendor/phpstan/phpstan/phpstan';
            $configuration_data['parameters']['bootstrap'] = __DIR__ . '/../../error-bootstrap.php';
            $configuration_data['includes'] = [
                __DIR__ . '/../../vendor/phpstan/phpstan-deprecation-rules/rules.neon',
                __DIR__ . '/../../vendor/mglaman/phpstan-drupal/extension.neon',
            ];
        } elseif (file_exists(__DIR__ . '/../../../../autoload.php')) {
            // Running as a global dependency.
            $phpstanBin = __DIR__ . '/../../../../phpstan/phpstan/phpstan';
            $configuration_data['parameters']['bootstrap'] = __DIR__ . '/../../error-bootstrap.php';
            // The phpstan/extension-installer doesn't seem to register.
            $configuration_data['includes'] = [
                __DIR__ . '/../../../../phpstan/phpstan-deprecation-rules/rules.neon',
                __DIR__ . '/../../../../mglaman/phpstan-drupal/extension.neon',
            ];
        } else {
            throw new ShouldNotHappenException('Could not determine if local or global installation');
        }

        $configuration_encoded = Neon::encode($configuration_data, Neon::BLOCK);
        $configuration = sys_get_temp_dir() . '/drupal_check_phpstan_' . time() . '.neon';
        file_put_contents($configuration, $configuration_encoded);

        $output->writeln('<comment>PHPStan configuration:</comment>', OutputInterface::VERBOSITY_DEBUG);
        $output->writeln($configuration_encoded, OutputInterface::VERBOSITY_DEBUG);

        $command = [
            $phpstanBin,
            'analyse',
            '-c',
            $configuration,
            '--error-format=' . $input->getOption('format')
        ];
        if ($output->getVerbosity() === OutputInterface::VERBOSITY_VERBOSE) {
            $command[] = '-v';
        } elseif ($output->getVerbosity() === OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $command[] = '-vv';
        } elseif ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $command[] = '-vvv';
        }
        $command = array_merge($command, $paths);

        $process = new Process($command);
        $process->setTty(Tty::isTtySupported());
        $process->setTimeout(null);
        $process->run(static function ($type, $buffer) use ($output) {
            if (Process::ERR === $type) {
                $output->write($buffer, false, OutputInterface::OUTPUT_RAW);
            } else {
                $output->writeln($buffer, OutputInterface::OUTPUT_RAW);
            }
        });
        unlink($configuration);

        return $process->getExitCode();
    }
}
