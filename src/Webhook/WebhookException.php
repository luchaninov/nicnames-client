<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Webhook;

use RuntimeException;

/**
 * Thrown when an incoming webhook fails validation: missing fields, bad HMAC signature,
 * stale timestamp (anti-replay), or malformed JSON payload.
 *
 * This is intentionally distinct from `NicnamesException`, which models errors returned by the
 * Nicnames API itself.
 */
class WebhookException extends RuntimeException
{
}
