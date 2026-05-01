<?php

/**
 * Bulk WHOIS privacy toggle.
 *
 * Reads domain names from a file (one per line) and PATCHes /domain/{name}/update/whois_privacy
 * to enable/disable privacy on the registrant contact (and optionally admin/tech/billing via flags).
 *
 * Output: TSV to stdout with columns: domain, oid, jobId, status
 *
 * Usage:
 *   php examples/privacy.php --input domains.txt --on > result.tsv
 *   php examples/privacy.php --input domains.txt --off --apply-to-all > result.tsv
 */

declare(strict_types=1);

require __DIR__ . '/_lib.php';

use Luchaninov\NicnamesClient\Dto\UpdateWhoisPrivacyRequest;
use Luchaninov\NicnamesClient\Exception\NicnamesException;

$env = loadEnv();
$inputFile = requireArg($argv, '--input');

if (!hasFlag($argv, '--on') && !hasFlag($argv, '--off')) {
    fwrite(STDERR, "Error: pass --on or --off\n");
    exit(1);
}
$mode = hasFlag($argv, '--on') ? UpdateWhoisPrivacyRequest::ENABLE : UpdateWhoisPrivacyRequest::DISABLE;
$applyAll = hasFlag($argv, '--apply-to-all');

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

    $request = $applyAll
        ? new UpdateWhoisPrivacyRequest(registrant: $mode, admin: $mode, tech: $mode, billing: $mode)
        : new UpdateWhoisPrivacyRequest(registrant: $mode);

    try {
        $result = $client->updateDomainWhoisPrivacy($domain, $request);
        if ($result->isAsync()) {
            $jobId = $result->jobId ?? '';
            $status = 'ASYNC';
        } else {
            $oid = $result->order?->oid ?? '';
        }
    } catch (NicnamesException $e) {
        fwrite(STDERR, "Privacy update failed for {$domain}: [{$e->getCode()}] {$e->getMessage()}\n");
        $status = 'FAILED';
    }

    echo "{$domain}\t{$oid}\t{$jobId}\t{$status}\n";
}
