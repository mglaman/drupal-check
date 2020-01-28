<?php declare(strict_types=1);

namespace DrupalCheck;

final class ErrorHandler
{
    private $previousErrorHandler;
    private $gatheredWarnings = [];

    public function handleError($type, $msg, $file, $line, $context = []): void
    {
        if (E_USER_WARNING !== $type) {
            $h = $this->previousErrorHandler;
            if (\is_callable($h)) {
                $h($type, $msg, $file, $line, $context);
            }
        } else {
            $this->gatheredWarnings[] = $msg;
        }
    }

    public function register(): void
    {
        $this->previousErrorHandler = set_error_handler([$this, 'handleError']);
    }

    public function restore(): void
    {
        $this->previousErrorHandler = null;
        restore_error_handler();
    }

    public function getWarnings(): array
    {
        return $this->gatheredWarnings;
    }
}
