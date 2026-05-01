<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class ListParams
{
    public function __construct(
        public int $pgn = 1,
        public int $pgl = 25,
        public ?string $filter = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $a = [
            'pgn' => $this->pgn,
            'pgl' => $this->pgl,
        ];
        if ($this->filter !== null) {
            $a['filter'] = $this->filter;
        }

        return $a;
    }
}
