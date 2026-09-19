<?php

namespace App\Services;

use App\Models\Block;

class ChainService
{
    /**
     * Append a new event to the internal hash chain, linking it to the
     * previous block just like the rest of the marketplace does.
     */
    public function appendBlock(string $event): Block
    {
        $lastBlock = Block::query()->orderByDesc('position')->lockForUpdate()->first();
        $position = ($lastBlock?->position ?? 0) + 1;
        $previousHash = $lastBlock?->current_hash ?? str_repeat('0', 64);
        $occurredAt = now();
        $currentHash = hash('sha256', implode('|', [$position, $previousHash, $event, $occurredAt->toIso8601String()]));

        return Block::create([
            'position' => $position,
            'previous_hash' => $previousHash,
            'current_hash' => $currentHash,
            'event' => $event,
            'occurred_at' => $occurredAt,
        ]);
    }
}
