<?php declare(strict_types = 1);

require __DIR__ . '/src/ErrorHandler.php';

$errorHandler = new \DrupalCheck\ErrorHandler();
$errorHandler->register();

register_shutdown_function(static function() use ($errorHandler): void {
    $errorHandler->restore();
    $warnings = $errorHandler->getWarnings();
    if (count($warnings) > 0) {
        print PHP_EOL;
        foreach ($warnings as $warning) {
            print $warning;
        }
    }
});
