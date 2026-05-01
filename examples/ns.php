<?php

/**
 * Bulk domain nameserver update.
 *
 * Reads TSV input (domain<TAB>ns1<TAB>ns2[<TAB>ns3...]) and PATCHes /domain/{name}/update/ns.
 *
 * Output: TSV to stdout with columns: domain, oid, jobId, status
 *
 * Usage:
 *   php examples/ns.php --input ns.tsv > result.tsv
 */

declare(strict_types=1);

require __DIR__ . '/_lib.php';

use Luchaninov\NicnamesClient\Dto\UpdateNameServersRequest;
use Luchaninov\NicnamesClient\Exception\NicnamesException;

$env = loadEnv();
$inputFile = requireArg($argv, '--input');

$lines = readInputLines($inputFile);
$rows = [];
foreach ($lines as $line) {
    $cols = array_values(array_filter(array_map('trim', explode("\t", $line)), fn(string $s) => $s !== ''));
    if (count($cols) < 2) {
        fwrite(STDERR, "Skipping malformed row: {$line}\n");
        continue;
    }
    $rows[] = [
        'domain' => $cols[0],
        'ns' => array_slice($cols, 1, 13),
    ];
}

$keys = array_column($rows, 'domain');
['list' => $keys, 'resuming' => $resuming] = applyResume($keys, $argv);
$keysIndex = array_flip($keys);
$rows = array_values(array_filter(
    $rows,
    static fn(array $r) => isset($keysIndex[$r['domain']]),
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
        $result = $client->updateDomainNameServers($domain, new UpdateNameServersRequest($row['ns']));
        if ($result->isAsync()) {
            $jobId = $result->jobId ?? '';
            $status = 'ASYNC';
        } else {
            $oid = $result->order?->oid ?? '';
        }
    } catch (NicnamesException $e) {
        fwrite(STDERR, "NS update failed for {$domain}: [{$e->getCode()}] {$e->getMessage()}\n");
        $status = 'FAILED';
    }

    echo "{$domain}\t{$oid}\t{$jobId}\t{$status}\n";
}
