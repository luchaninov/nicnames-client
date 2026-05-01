<?php

declare(strict_types=1);

require __DIR__ . '/_lib.php';

use Luchaninov\NicnamesClient\Exception\NicnamesException;

$env = loadEnv();
$client = createClient($env);

try {
    echo "=== List Domains (page 1, page length 5) ===\n";
    $domains = $client->listDomains(new Luchaninov\NicnamesClient\Dto\ListParams(pgn: 1, pgl: 5));
    echo "Total domains: {$domains->total}\n";
    foreach ($domains->list as $d) {
        $name = $d->domain?->name ?? '(no domain)';
        $status = implode(',', $d->status);
        echo sprintf("  %-30s status: %-20s expires: %d\n", $name, $status, $d->ets ?? 0);
    }
    echo "\n";

    echo "=== List Contacts (page 1, page length 5) ===\n";
    $contacts = $client->listContacts(new Luchaninov\NicnamesClient\Dto\ListParams(pgn: 1, pgl: 5));
    echo "Total contacts: {$contacts->total}\n";
    foreach ($contacts->list as $c) {
        echo sprintf("  %-15s %s %s <%s>\n", $c->contactId, $c->firstName, $c->lastName, $c->email);
    }
    echo "\n";

    echo "=== Domain Availability ===\n";
    $check = $client->checkDomain('example.com');
    echo "example.com: availableFor={$check->availableFor} tier={$check->tier} prices=" . count($check->price) . "\n";

    $rand = 'xyzzy-test-' . time() . '.com';
    $check2 = $client->checkDomain($rand);
    echo "{$rand}: availableFor={$check2->availableFor} tier={$check2->tier}\n";

    echo "\nDone.\n";
} catch (NicnamesException $e) {
    echo "API Error [{$e->getCode()}]: {$e->getMessage()}\n";
    if ($e->traceId !== null) {
        echo "Trace ID: {$e->traceId}\n";
    }
    exit(1);
}
