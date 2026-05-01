<?php

/**
 * Bulk domain transfer.
 *
 * Reads TSV input (domain<TAB>authCode), fetches a transfer price quote via /check,
 * and POSTs to /domain/{name}/transfer.
 *
 * Output: TSV to stdout with columns: domain, oid, jobId, status
 *
 * Usage:
 *   php examples/transfer.php --input transfers.tsv --registrant c987654321 --term 1 > result.tsv
 */

declare(strict_types=1);

require __DIR__ . '/_lib.php';

use Luchaninov\NicnamesClient\Dto\OperationModel;
use Luchaninov\NicnamesClient\Dto\TransferDomainRequest;
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
$rows = [];
foreach ($lines as $line) {
    $cols = explode("\t", $line);
    if (count($cols) < 2) {
        fwrite(STDERR, "Skipping malformed row: {$line}\n");
        continue;
    }
    $rows[] = ['domain' => trim($cols[0]), 'authCode' => trim($cols[1])];
}

$keys = array_column($rows, 'domain');
['list' => $keys, 'resuming' => $resuming] = applyResume($keys, $argv);
$rows = array_values(array_filter(
    $rows,
    static fn(array $r) => in_array($r['domain'], $keys, true),
));

$client = createClient($env);

if (!$resuming) {
    echo "domain\toid\tjobId\tstatus\n";
}

foreach ($rows as $row) {
    $domain = $row['domain'];
    $oid = '';
    $jobId = '';
    $status = 'OK';

    try {
        $check = $client->checkDomain($domain);
        $price = null;
        foreach ($check->price as $p) {
            if ($p->op === OperationModel::TRANSFER && $p->period->value === $term) {
                $price = $p;
                break;
            }
        }
        if ($price === null) {
            fwrite(STDERR, "No TRANSFER price for {$term}-year term on {$domain}\n");
            echo "{$domain}\t\t\tFAILED\n";
            continue;
        }

        $result = $client->transferDomain(
            $domain,
            new TransferDomainRequest($price, $registrant, $row['authCode']),
        );
        if ($result->isAsync()) {
            $jobId = $result->jobId ?? '';
            $status = 'ASYNC';
        } else {
            $oid = $result->order?->oid ?? '';
        }
    } catch (NicnamesException $e) {
        fwrite(STDERR, "Transfer failed for {$domain}: [{$e->getCode()}] {$e->getMessage()}\n");
        $status = 'FAILED';
    }

    echo "{$domain}\t{$oid}\t{$jobId}\t{$status}\n";
}
