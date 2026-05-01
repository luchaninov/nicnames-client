<?php

/**
 * Bulk domain availability checker.
 *
 * Reads domain names from a file (one per line), calls /domain/{name}/check for each.
 *
 * Output: TSV to stdout with columns: domain, availableFor, tier, lowestAmt, ccy
 *
 * Usage:
 *   php examples/check.php --input domains.txt > result.tsv
 *   php examples/check.php --input domains.txt --resume last.com >> result.tsv
 */

declare(strict_types=1);

require __DIR__ . '/_lib.php';

use Luchaninov\NicnamesClient\Exception\NicnamesException;

$env = loadEnv();
$inputFile = requireArg($argv, '--input');
$lines = readInputLines($inputFile);
$domains = array_values(array_filter(array_map('trim', $lines), fn(string $s) => $s !== ''));
['list' => $domains, 'resuming' => $resuming] = applyResume($domains, $argv);

$client = createClient($env);

if (!$resuming) {
    echo "domain\tavailableFor\ttier\tlowestAmt\tccy\n";
}

foreach ($domains as $domain) {
    try {
        $check = $client->checkDomain($domain);
        $lowestAmt = '';
        $ccy = '';
        if ($check->price !== []) {
            $minPrice = $check->price[0];
            foreach ($check->price as $p) {
                if ($p->amt < $minPrice->amt) {
                    $minPrice = $p;
                }
            }
            $lowestAmt = (string) $minPrice->amt;
            $ccy = (string) $minPrice->ccy;
        }
        echo "{$domain}\t{$check->availableFor->value}\t{$check->tier->value}\t{$lowestAmt}\t{$ccy}\n";
    } catch (NicnamesException $e) {
        fwrite(STDERR, "Check failed for {$domain}: [{$e->getCode()}] {$e->getMessage()}\n");
        echo "{$domain}\tERROR\t\t\t\n";
    }
}
