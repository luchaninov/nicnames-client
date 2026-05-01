<?php

/**
 * Bulk domain renewal.
 *
 * Reads domain names from a file (one per line), looks up current ETS via /info,
 * fetches a renewal price quote via /check, and POSTs to /domain/{name}/renew.
 *
 * Output: TSV to stdout with columns: domain, oid, jobId, status
 *
 * Usage:
 *   php examples/renew.php --input domains.txt --term 1 > result.tsv
 */

declare(strict_types=1);

require __DIR__ . '/_lib.php';

use Luchaninov\NicnamesClient\Dto\OperationModel;
use Luchaninov\NicnamesClient\Dto\RenewDomainRequest;
use Luchaninov\NicnamesClient\Exception\NicnamesException;

$env = loadEnv();
$inputFile = requireArg($argv, '--input');
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
        $info = $client->infoDomain($domain);
        $currentETS = $info->ets ?? 0;
        if ($currentETS === 0) {
            fwrite(STDERR, "No ETS for {$domain}\n");
            echo "{$domain}\t\t\tFAILED\n";
            continue;
        }

        $check = $client->checkDomain($domain);
        $price = null;
        foreach ($check->price as $p) {
            if ($p->op === OperationModel::RENEW && $p->period->value === $term) {
                $price = $p;
                break;
            }
        }
        if ($price === null) {
            fwrite(STDERR, "No RENEW price for {$term}-year term on {$domain}\n");
            echo "{$domain}\t\t\tFAILED\n";
            continue;
        }

        $result = $client->renewDomain($domain, new RenewDomainRequest($price, $currentETS));
        if ($result->isAsync()) {
            $jobId = $result->jobId ?? '';
            $status = 'ASYNC';
        } else {
            $oid = $result->order?->oid ?? '';
        }
    } catch (NicnamesException $e) {
        fwrite(STDERR, "Renew failed for {$domain}: [{$e->getCode()}] {$e->getMessage()}\n");
        $status = 'FAILED';
    }

    echo "{$domain}\t{$oid}\t{$jobId}\t{$status}\n";
}
