<?php declare(strict_types=1);

namespace DrupalCheck;

use Symfony\Component\Console\Application as BaseApplication;

final class Application extends BaseApplication
{
    public function __construct() {
        parent::__construct('Drupal Check', '0.0.0');
        $this->add(new Command\CheckCommand());
        $this->setDefaultCommand('check', true);
    }
}
