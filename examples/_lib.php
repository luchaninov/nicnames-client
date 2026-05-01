<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Luchaninov\NicnamesClient\HttpTransport;
use Luchaninov\NicnamesClient\NicnamesClient;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Parse .env.local and return key-value pairs. Exits on failure.
 *
 * @return array<string, string>
 */
function loadEnv(): array
{
    $env = parse_ini_file(__DIR__ . '/../.env.local');
    if ($env === false) {
        fwrite(STDERR, "Error: cannot read .env.local\n");
        exit(1);
    }

    return $env;
}

/**
 * Get a CLI argument value by name (e.g. --input <value>). Returns null if not found.
 *
 * @param string[] $argv
 */
function getArg(array $argv, string $name): ?string
{
    $index = array_search($name, $argv, true);
    if ($index === false || !isset($argv[$index + 1])) {
        return null;
    }

    return $argv[$index + 1];
}

/**
 * Get a required CLI argument value by name. Exits with error if missing.
 *
 * @param string[] $argv
 */
function requireArg(array $argv, string $name): string
{
    $value = getArg($argv, $name);
    if ($value === null) {
        fwrite(STDERR, "Error: {$name} <value> is required\n");
        exit(1);
    }

    return $value;
}

/**
 * Read a file into an array of non-empty lines. Exits on failure.
 *
 * @return string[]
 */
function readInputLines(string $inputFile): array
{
    $lines = file($inputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        fwrite(STDERR, "Error: cannot read {$inputFile}\n");
        exit(1);
    }

    return $lines;
}

/**
 * Apply --resume flag: skip items up to and including the given value.
 *
 * @param string[] $items
 * @param string[] $argv
 * @return array{list: string[], resuming: bool}
 */
function applyResume(array $items, array $argv): array
{
    $resuming = false;
    $last = getArg($argv, '--resume');
    if ($last !== null) {
        $resuming = true;
        $pos = array_search($last, $items, true);
        if ($pos !== false) {
            $items = array_slice($items, $pos + 1);
        }
    }

    return ['list' => $items, 'resuming' => $resuming];
}

/**
 * Check if a flag (e.g. --on, --off) is present in argv.
 *
 * @param string[] $argv
 */
function hasFlag(array $argv, string $name): bool
{
    return in_array($name, $argv, true);
}

/**
 * Create a NicnamesClient from env vars (NICNAMES_API_KEY, optional NICNAMES_BASE_URL).
 * Exits if credentials are missing.
 *
 * @param array<string, string> $env
 */
function createClient(array $env): NicnamesClient
{
    $apiKey = $env['NICNAMES_API_KEY'] ?? '';
    if ($apiKey === '') {
        fwrite(STDERR, "Error: fill NICNAMES_API_KEY in .env.local\n");
        exit(1);
    }
    $baseUrl = $env['NICNAMES_BASE_URL'] ?? 'https://api.nicnames.com/2';

    $logDir = __DIR__ . '/../var/log';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logger = new Logger('nicnames');
    $logger->pushHandler(new StreamHandler($logDir . '/debug.log'));

    $transport = new HttpTransport(HttpClient::create(), $apiKey, $baseUrl, $logger);

    return new NicnamesClient($transport);
}
