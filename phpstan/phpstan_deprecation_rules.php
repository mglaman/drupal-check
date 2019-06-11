<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    return [
        'includes' => [
            '../vendor/phpstan/phpstan-deprecation-rules/rules.neon',
        ],
    ];
}

if (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
    $path = __DIR__ . '/../../../../vendor';
    return [
        'includes' => [
            $path . '/phpstan/phpstan-deprecation-rules/rules.neon',
        ],
    ];
}
