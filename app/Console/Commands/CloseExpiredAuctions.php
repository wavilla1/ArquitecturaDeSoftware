<?php

namespace App\Console\Commands;

use App\Services\AuctionService;
use Illuminate\Console\Command;

class CloseExpiredAuctions extends Command
{
    protected $signature = 'auctions:close';

    protected $description = 'Adjudica al mejor postor (o expira) las subastas cuyo cierre ya se cumplió.';

    public function handle(AuctionService $auctions): int
    {
        $closed = $auctions->closeExpired();
        $this->info("Subastas cerradas: {$closed}.");

        return self::SUCCESS;
    }
}
