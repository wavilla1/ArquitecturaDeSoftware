<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Listing;
use App\Models\Nft;
use App\Models\NftCollection;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User
    {
        return User::where('handle', 'admin')->firstOrFail();
    }

    public function test_a_regular_user_cannot_open_the_admin_panel(): void
    {
        $user = User::where('handle', 'will')->firstOrFail();

        $this->withSession(['demo_user_id' => $user->id])
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_the_admin_sees_the_dashboard_with_its_reports(): void
    {
        $this->withSession(['demo_user_id' => $this->admin()->id])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Panel de administración')
            ->assertSee('Volumen transado')
            ->assertSee('Colecciones con más volumen');
    }

    public function test_hiding_a_collection_removes_it_from_the_public_marketplace(): void
    {
        $collection = NftCollection::where('slug', 'pixel-rebels')->firstOrFail();
        $listing = Listing::query()
            ->whereIn('nft_id', Nft::where('collection_id', $collection->id)->select('id'))
            ->where('status', 'active')
            ->where('type', Listing::TYPE_FIXED)
            ->firstOrFail();

        $this->withSession(['demo_user_id' => $this->admin()->id])
            ->post(route('admin.collections.toggle', $collection))
            ->assertRedirect();

        $this->assertTrue($collection->fresh()->isHidden());
        $this->assertSame('cancelled', $listing->fresh()->status);
        $this->assertFalse((bool) $listing->nft->fresh()->in_sale);

        // La colección sigue nombrada en la cadena de bloques (el historial no se
        // reescribe), así que se verifica la grilla de publicaciones del mercado.
        $this->withSession(['demo_user_id' => User::where('handle', 'will')->firstOrFail()->id])
            ->get(route('marketplace'))
            ->assertOk()
            ->assertViewHas('listings', fn ($listings) => $listings
                ->pluck('nft.collection_id')
                ->doesntContain($collection->id));
    }

    public function test_restoring_a_collection_makes_it_visible_again(): void
    {
        $collection = NftCollection::where('slug', 'pixel-rebels')->firstOrFail();
        $session = $this->withSession(['demo_user_id' => $this->admin()->id]);

        $session->post(route('admin.collections.toggle', $collection))->assertRedirect();
        $this->post(route('admin.collections.toggle', $collection))->assertRedirect();

        $this->assertFalse($collection->fresh()->isHidden());
    }

    public function test_a_suspended_user_cannot_buy_and_loses_its_direct_listings(): void
    {
        $user = User::where('handle', 'will')->firstOrFail();
        $ownListing = Listing::where('seller_id', $user->id)
            ->where('status', 'active')
            ->where('type', Listing::TYPE_FIXED)
            ->firstOrFail();
        $otherListing = Listing::where('seller_id', '!=', $user->id)
            ->where('status', 'active')
            ->where('type', Listing::TYPE_FIXED)
            ->firstOrFail();

        $this->withSession(['demo_user_id' => $this->admin()->id])
            ->post(route('admin.users.toggle', $user))
            ->assertRedirect();

        $this->assertTrue($user->fresh()->isSuspended());
        $this->assertSame('cancelled', $ownListing->fresh()->status);

        $this->withSession(['demo_user_id' => $user->id])
            ->post(route('listings.buy', $otherListing))
            ->assertForbidden();

        $this->assertSame('active', $otherListing->fresh()->status);
    }

    public function test_an_admin_cannot_be_suspended(): void
    {
        $admin = $this->admin();

        $this->withSession(['demo_user_id' => $admin->id])
            ->post(route('admin.users.toggle', $admin))
            ->assertStatus(422);

        $this->assertFalse($admin->fresh()->isSuspended());
    }

    public function test_every_moderation_action_is_appended_to_the_chain(): void
    {
        $collection = NftCollection::where('slug', 'nebula-echoes')->firstOrFail();
        $blocksBefore = Block::count();

        $this->withSession(['demo_user_id' => $this->admin()->id])
            ->post(route('admin.collections.toggle', $collection))
            ->assertRedirect();

        $this->assertSame($blocksBefore + 1, Block::count());
        $last = Block::orderByDesc('position')->firstOrFail();
        $this->assertStringContainsString('Moderación', $last->event);
        $this->assertStringContainsString('@admin', $last->event);
        $this->assertSame(Block::where('position', $last->position - 1)->value('current_hash'), $last->previous_hash);
    }
}
