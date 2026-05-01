<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Exception;

/**
 * Thrown when the API returns a response we cannot interpret: e.g. an HTTP 202 Accepted with no
 * `jobId`, or a successful body missing required fields.
 */
class MalformedResponseException extends NicnamesException
{
}
