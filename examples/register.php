<?php

/**
 * Bulk domain registration.
 *
 * Reads domain names from a file (one per line), creates each via POST /domain/{name}/create.
 * Pulls the price quote from /domain/{name}/check before registering.
 *
 * Output: TSV to stdout with columns: domain, oid, jobId, status
 *   status: OK, ASYNC, NOT_AVAILABLE, FAILED
 *
 * Usage:
 *   php examples/register.php --input domains.txt --registrant c987654321 > result.tsv
 *   php examples/register.php --input domains.txt --registrant c987654321 --term 2 --resume last.com >> result.tsv
 *
 * Options:
 *   --input <file>           File with domain names (one per line, required)
 *   --registrant <contactId> Contact id to use as registrant (or NICNAMES_DEFAULT_REGISTRANT in .env.local)
 *   --term <years>           Registration term in years (default 1)
 *   --resume <domain>        Skip up to and including <domain>
 */

declare(strict_types=1);

require __DIR__ . '/_lib.php';

use Luchaninov\NicnamesClient\Dto\CreateDomainRequest;
use Luchaninov\NicnamesClient\Dto\OperationModel;
use Luchaninov\NicnamesClient\Exception\NicnamesException;

$env = loadEnv();

$inputFile = requireArg($argv, '--input');
$registrant = getArg($argv, '--registrant') ?? $env['NICNAMES_DEFAULT_REGISTRANT'] ?? '';
if ($registrant === '') {
    fwrite(STDERR, "Error: --registrant <contactId> or NICNAMES_DEFAULT_REGISTRANT in .env.local is required\n");
    exit(1);
}

$term = (int) (getArg($argv, '--term') ?? '1');

$lines = readInputLines($inputFile);
$domains = array_values(array_filter(array_map('trim', $lines), fn(string $s) => $s !== ''));
['list' => $domains, 'resuming' => $resuming] = applyResume($domains, $argv);

$client = createClient($env);

if (!$resuming) {
    echo "domain\toid\tjobId\tstatus\n";
}

foreach ($domains as $domain) {
    $oid = '';
    $jobId = '';
    $status = 'OK';

    try {
        // Get a price quote for the registration term.
        $check = $client->checkDomain($domain);
        if ($check->availableFor !== OperationModel::CREATE) {
            fwrite(STDERR, "Not available for CREATE: {$domain} ({$check->availableFor->value})\n");
            echo "{$domain}\t\t\tNOT_AVAILABLE\n";
            continue;
        }
        $price = null;
        foreach ($check->price as $p) {
            if ($p->op === OperationModel::CREATE && $p->period->value === $term) {
                $price = $p;
                break;
            }
        }
        if ($price === null) {
            fwrite(STDERR, "No CREATE price for {$term}-year term on {$domain}\n");
            echo "{$domain}\t\t\tFAILED\n";
            continue;
        }

        $request = new CreateDomainRequest($price, $registrant);
        $result = $client->createDomain($domain, $request);
        if ($result->isAsync()) {
            $jobId = $result->jobId ?? '';
            $status = 'ASYNC';
        } else {
            $oid = $result->order?->oid ?? '';
        }
    } catch (NicnamesException $e) {
        fwrite(STDERR, "Register failed for {$domain}: [{$e->getCode()}] {$e->getMessage()}\n");
        $status = 'FAILED';
    }

    echo "{$domain}\t{$oid}\t{$jobId}\t{$status}\n";
}
