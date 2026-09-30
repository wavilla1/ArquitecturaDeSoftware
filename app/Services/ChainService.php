<?php

namespace App\Services;

use App\Models\Block;
use Illuminate\Support\Facades\DB;

class ChainService
{
    /**
     * Append a new event to the internal hash chain, linking it to the
     * previous block just like the rest of the marketplace does.
     */
    public function appendBlock(string $event): Block
    {
        return DB::transaction(function () use ($event) {
            // A permanent singleton serializes appends, including an empty chain.
            DB::table('chain_locks')->where('id', 1)->lockForUpdate()->first();
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
        });
    }

    public function verify(): array
    {
        $previousHash = str_repeat('0', 64);
        $position = 1;
        $errors = [];
        foreach (Block::orderBy('position')->cursor() as $block) {
            $calculated = hash('sha256', implode('|', [$block->position, $block->previous_hash, $block->event, $block->occurred_at->toIso8601String()]));
            if ($block->position !== $position || ! hash_equals($previousHash, $block->previous_hash) || ! hash_equals($calculated, $block->current_hash)) {
                $errors[] = $block->position;
            }
            $previousHash = $block->current_hash;
            $position++;
        }

        return ['valid' => $errors === [], 'count' => $position - 1, 'errors' => $errors, 'head' => $previousHash];
    }
}
