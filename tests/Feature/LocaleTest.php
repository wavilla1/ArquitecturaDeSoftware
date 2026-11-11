<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_spanish_is_the_default_language(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="es">', false)
            ->assertSee('Arte único')
            ->assertSee('Piezas en venta');
    }

    public function test_visitor_can_switch_to_english_and_back(): void
    {
        $this->from('/cadena')->get(route('locale.switch', 'en'))
            ->assertRedirect('/cadena')
            ->assertSessionHas('locale', 'en');

        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('Unique art.')
            ->assertSee('Pieces for sale')
            ->assertDontSee('Piezas en venta');

        $this->get(route('locale.switch', 'es'))->assertSessionHas('locale', 'es');
        $this->get('/')->assertSee('Piezas en venta');
    }

    public function test_unsupported_language_is_rejected(): void
    {
        $this->get('/idioma/fr')->assertNotFound();
    }

    public function test_flash_and_error_messages_follow_the_chosen_language(): void
    {
        $buyer = User::where('handle', 'joseluis')->firstOrFail();
        $listing = Listing::where('status', 'active')->where('type', 'fixed')->where('seller_id', '!=', $buyer->id)->firstOrFail();

        $this->actingAs($buyer)
            ->withSession(['locale' => 'en'])
            ->post(route('listings.buy', $listing))
            ->assertSessionHas('success', 'Purchase completed. The NFT now appears in your inventory.');
    }

    public function test_validation_messages_follow_the_chosen_language(): void
    {
        $this->withSession(['locale' => 'es'])
            ->post(route('login.store'), ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email' => 'El campo correo electrónico es obligatorio.']);

        $this->withSession(['locale' => 'en'])
            ->post(route('login.store'), ['email' => 'nobody@monoverse.test', 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'Incorrect email or password.']);
    }

    public function test_every_english_translation_has_a_value(): void
    {
        $translations = json_decode(file_get_contents(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertNotEmpty($translations);
        foreach ($translations as $spanish => $english) {
            $this->assertNotSame('', trim($english), "Falta traducción para: {$spanish}");
        }
    }
}
