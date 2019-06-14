<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    return [
        'includes' => [
            '../vendor/phpstan/phpstan-deprecation-rules/rules.neon',
        ],
    ];
}
if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    return [
        'includes' => [
            '../../../../vendor/phpstan/phpstan-deprecation-rules/rules.neon',
        ],
    ];
}
