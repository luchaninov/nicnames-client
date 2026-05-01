<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

/**
 * Operation kinds used in two places:
 *  - {@see PriceModel::$op}: identifies which kind of operation a price quote applies to
 *    (`CREATE`, `TRANSFER`, `RENEW`, `RESTORE`).
 *  - {@see DomainCheckResult::$availableFor}: which operation the domain is currently eligible for,
 *    including `NONE` (nothing currently possible) and `UPDATE` (the domain exists and can be
 *    modified, e.g. nameservers / WHOIS privacy). The client never emits `UPDATE` itself; it only
 *    appears in `availableFor` responses from the `/check` endpoint.
 */
enum OperationModel: string
{
    case NONE = 'NONE';
    case CREATE = 'CREATE';
    case TRANSFER = 'TRANSFER';
    case RENEW = 'RENEW';
    case RESTORE = 'RESTORE';
    case UPDATE = 'UPDATE';
}
