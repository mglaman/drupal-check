<?php declare(strict_types=1);

namespace DrupalCheck\Command;

use DrupalCheck\Application;
use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
{

    protected function configure(): void
    {
        $this
            ->setName('update')
            ->setDescription('Updates the phar');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $currentVersion = $this->getApplication()->getVersion();
        if ($currentVersion === Application::FALLBACK_VERSION || strpos($currentVersion, 'dev-') === 0) {
            $output->writeln("Cannot update with a development installation.");
            return 1;
        }
        $updater = new Updater(null, false);
        $updater->setStrategy(Updater::STRATEGY_GITHUB);
        $updater->getStrategy()->setPackageName('mglaman/drupal-check');
        $updater->getStrategy()->setPharName('drupal-check.phar');
        $updater->getStrategy()->setCurrentLocalVersion($currentVersion);
        try {
            $result = $updater->update();
            if ($result) {
                $new = $updater->getNewVersion();
                $old = $updater->getOldVersion();
                $output->writeln(sprintf('<info>Drupal Check has been updated from %s to %s!</info>', $old, $new));
            } else {
                $output->writeln('<comment>No update required!</comment>');
            }
            return 0;
        } catch (\Throwable $e) {
            $output->writeln('Automatic update failed, please download the latest version from https://github.com/mglaman/drupal-check/releases/latest');
            return 1;
        }
    }

}
