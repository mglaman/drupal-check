<?php declare(strict_types=1);

namespace DrupalCheck\Util;

use Symfony\Component\Process\Process;

/**
 * Wrapper for universal support of TTY-related functionality across versions of
 * Symfony Process.
 */
class Tty
{
    /**
     * In Symfony Process 4+, this is simply a wrapper for Process::isTtySupported().
     * In lower versions, it mimics the same functionality.
     */
    public static function isTtySupported()
    {
        // Start off by checking STDIN with `posix_isatty`, as that appears to be more reliable
        if (function_exists('posix_isatty')) {
            return posix_isatty(STDIN);
        }
        if (method_exists('\Symfony\Component\Process\Process', 'isTtySupported')) {
            return Process::isTtySupported();
        }
        return (bool) @proc_open('echo 1 >/dev/null', array(array('file', '/dev/tty', 'r'), array('file', '/dev/tty', 'w'), array('file', '/dev/tty', 'w')), $pipes);
    }
}
