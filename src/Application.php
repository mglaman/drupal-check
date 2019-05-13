<?php declare(strict_types=1);

namespace DrupalCheck;

use Symfony\Component\Console\Application as BaseApplication;

final class Application extends BaseApplication
{
    const FALLBACK_VERSION = '0.0.0';

    public function __construct()
    {
        try {
            $version = \Jean85\PrettyVersions::getVersion('mglaman/drupal-check')->getPrettyVersion();
        } catch (\OutOfBoundsException $e) {
            $version = self::FALLBACK_VERSION;
        }
        parent::__construct('Drupal Check', $version);
        $this->add(new Command\CheckCommand());
        $this->setDefaultCommand('check', true);
    }
}
