<?php declare(strict_types=1);

namespace DrupalCheck\Command;

use DrupalCheck\DrupalCheckErrorHandler;
use DrupalFinder\DrupalFinder;
use PHPStan\Command\AnalyseApplication;
use PHPStan\Command\CommandHelper;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\ErrorsConsoleStyle;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends Command
{
    private $isDeprecationsCheck = false;
    private $isAnalysisCheck = false;
    private $isStyleCheck = false;
    private $memoryLimit = null;
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
                ErrorsConsoleStyle::OPTION_NO_PROGRESS,
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
        $errorHandler = new DrupalCheckErrorHandler();
        $errorHandler->register();

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
        // Spoof the global phpstan normally provides when running as its
        // binary alongside a project.
        $GLOBALS['autoloaderInWorkingDirectory'] = $this->vendorRoot . '/autoload.php';

        $output->writeln(sprintf('<info>Using autoloader: %s</info>', $GLOBALS['autoloaderInWorkingDirectory']), OutputInterface::VERBOSITY_DEBUG);

        if ($this->isDeprecationsCheck && $this->isAnalysisCheck) {
            $configuration = __DIR__ . '/../../phpstan/rules_and_deprecations_testing.neon';
        } elseif ($this->isDeprecationsCheck && !$this->isAnalysisCheck) {
            $configuration = __DIR__ . '/../../phpstan/deprecation_testing.neon';
        } elseif (!$this->isDeprecationsCheck && $this->isAnalysisCheck) {
            $configuration = __DIR__ . '/../../phpstan/rules_testing.neon';
        } else {
            // @todo: only analysis check, style check. all of the above at once.
            $output->writeln('Not support, yet');
            return 1;
        }

        try {
            $inceptionResult = CommandHelper::begin(
                $input,
                $output,
                $input->getArgument('path'),
                null,
                $this->memoryLimit,
                null,
                $configuration,
                null,
                false
            );
        } catch (\PHPStan\Command\InceptionNotSuccessfulException $e) {
            return 1;
        } catch (ShouldNotHappenException $e) {
            return 1;
        }

        $errorOutput = $inceptionResult->getErrorOutput();

        $container = $inceptionResult->getContainer();
        $errorFormatterServiceName = sprintf('errorFormatter.%s', $input->getOption('format'));
        if (!$container->hasService($errorFormatterServiceName)) {
            $errorOutput->writeln(sprintf(
                'Error formatter "%s" not found. Available error formatters are: %s',
                $input->getOption('format'),
                implode(', ', array_map(static function (string $name) {
                    return substr($name, strlen('errorFormatter.'));
                }, $container->findByType(ErrorFormatter::class)))
            ));
            return 1;
        }

        /** @var ErrorFormatter $errorFormatter */
        $errorFormatter = $container->getService($errorFormatterServiceName);

        /** @var AnalyseApplication  $application */
        $application = $container->getByType(AnalyseApplication::class);

        $exitCode = $inceptionResult->handleReturn(
            $application->analyse(
                $inceptionResult->getFiles(),
                $inceptionResult->isOnlyFiles(),
                $inceptionResult->getConsoleStyle(),
                $errorFormatter,
                $inceptionResult->isDefaultLevelUsed(),
                $output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG,
                null
            )
        );
        $errorHandler->restore();
        $warnings = $errorHandler->getWarnings();
        if (count($warnings) > 0) {
            $output->write(PHP_EOL);
            foreach ($warnings as $warning) {
                $output->writeln("<info>$warning</info>");
            }
        }

        return $exitCode;
    }
}
