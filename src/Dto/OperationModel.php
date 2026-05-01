<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final class OperationModel
{
    public const string NONE = 'NONE';
    public const string CREATE = 'CREATE';
    public const string TRANSFER = 'TRANSFER';
    public const string RENEW = 'RENEW';
    public const string RESTORE = 'RESTORE';
    public const string UPDATE = 'UPDATE';
}
