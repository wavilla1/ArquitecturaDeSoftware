<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Listing;
use App\Models\MarketplaceTransaction;
use App\Models\Nft;
use App\Models\NftCollection;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = collect([
            ['name' => 'Jose Luis Restrepo', 'handle' => 'joseluis', 'email' => 'jose@monoverse.test', 'balance' => 18.40, 'accent' => '#7c3aed'],
            ['name' => 'Juan Jose Garcia', 'handle' => 'juanjose', 'email' => 'juan@monoverse.test', 'balance' => 15.60, 'accent' => '#06b6d4'],
            ['name' => 'William Alberto Villa', 'handle' => 'will', 'email' => 'will@monoverse.test', 'balance' => 21.25, 'accent' => '#ec4899'],
        ])->map(fn (array $user) => User::create($user + ['password' => Hash::make('demo1234')]));

        $collections = collect([
            ['creator_id' => $users[0]->id, 'name' => 'Nebula Echoes', 'slug' => 'nebula-echoes', 'description' => 'Fragmentos de color nacidos en una nebulosa digital.', 'total_supply' => 24, 'minted_count' => 2, 'base_price' => 3.20, 'palette_from' => '#6d28d9', 'palette_to' => '#06b6d4'],
            ['creator_id' => $users[1]->id, 'name' => 'Quantum Garden', 'slug' => 'quantum-garden', 'description' => 'Jardines generativos donde cada semilla crea una forma única.', 'total_supply' => 18, 'minted_count' => 2, 'base_price' => 4.50, 'palette_from' => '#0f766e', 'palette_to' => '#a3e635'],
            ['creator_id' => $users[2]->id, 'name' => 'Pixel Rebels', 'slug' => 'pixel-rebels', 'description' => 'Retratos de una resistencia nacida entre píxeles y neón.', 'total_supply' => 40, 'minted_count' => 2, 'base_price' => 2.80, 'palette_from' => '#be185d', 'palette_to' => '#7c3aed'],
        ])->map(fn (array $collection) => NftCollection::create($collection));

        $owners = [1, 0, 2, 1, 0, 1];
        $prices = [3.20, null, 4.75, 4.95, null, 3.40];

        foreach ($collections as $collectionIndex => $collection) {
            for ($token = 1; $token <= 2; $token++) {
                $flatIndex = ($collectionIndex * 2) + ($token - 1);
                $owner = $users[$owners[$flatIndex]];
                $isListed = $prices[$flatIndex] !== null;
                $nft = Nft::create([
                    'collection_id' => $collection->id,
                    'owner_id' => $owner->id,
                    'token_number' => $token,
                    'token_hash' => hash('sha256', "{$collection->slug}:{$token}:monoverse"),
                    'in_sale' => $isListed,
                ]);

                MarketplaceTransaction::create([
                    'buyer_id' => $owner->id,
                    'nft_id' => $nft->id,
                    'amount' => 0,
                    'type' => 'mint',
                ]);

                if ($isListed) {
                    Listing::create([
                        'nft_id' => $nft->id,
                        'seller_id' => $owner->id,
                        'price' => $prices[$flatIndex],
                        'status' => 'active',
                    ]);
                }
            }
        }

        $events = [
            'Bloque génesis de Monoverse',
            'Acuñación inicial de Nebula Echoes #1',
            'Publicación de Quantum Garden #1 por @will',
            'Publicación de Pixel Rebels #2 por @juanjose',
        ];
        $previousHash = str_repeat('0', 64);

        foreach ($events as $index => $event) {
            $position = $index + 1;
            $occurredAt = now()->subMinutes((count($events) - $index) * 9);
            $currentHash = hash('sha256', implode('|', [$position, $previousHash, $event, $occurredAt->toIso8601String()]));
            Block::create([
                'position' => $position,
                'previous_hash' => $previousHash,
                'current_hash' => $currentHash,
                'event' => $event,
                'occurred_at' => $occurredAt,
            ]);
            $previousHash = $currentHash;
        }
    }
}
