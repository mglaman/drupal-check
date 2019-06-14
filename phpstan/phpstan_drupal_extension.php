<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    return [
        'includes' => [
            '../vendor/mglaman/phpstan-drupal/extension.neon',
        ],
    ];
}
if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    return [
        'includes' => [
            '../../../../vendor/mglaman/phpstan-drupal/extension.neon',
        ],
    ];
}



