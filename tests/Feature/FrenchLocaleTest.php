<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Homework\HomeworkModule;
use App\Modules\Homework\KidPinStore;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FrenchLocaleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['dashboard.pin' => '4821']);
        app()->setLocale('fr');
        Carbon::setLocale('fr');
        $this->travelTo(CarbonImmutable::parse('2026-11-25 16:00', HomeworkModule::TIMEZONE));
    }

    public function test_every_english_key_in_the_french_file_has_a_translation(): void
    {
        $french = json_decode((string) file_get_contents(lang_path('fr.json')), true);

        $this->assertIsArray($french);
        $this->assertSame([], array_keys(array_filter($french, fn (mixed $value): bool => ! is_string($value) || trim($value) === '')));
    }

    public function test_the_dashboard_renders_in_quebec_french(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<html lang="fr"', false)
            ->assertSee('window.LOCALE = "fr-CA"', false)
            ->assertSee('Devoirs de Theo')
            ->assertSee('Réglages')
            ->assertDontSee('Theo’s homework');
    }

    public function test_the_kid_area_renders_in_french(): void
    {
        app(KidPinStore::class)->put('theo', '1234');

        $this->get('/modules/homework/kids/theo/sheet')->assertOk()->assertSee('Entre ton NIP');
        $this->post('/modules/homework/kids/theo/unlock', ['pin' => '9999'])->assertForbidden()->assertJson(['error' => 'Ce n’est pas le bon NIP.']);
        $this->post('/modules/homework/kids/theo/unlock', ['pin' => '1234'])->assertOk()
            ->assertSee('Salut Theo !')
            ->assertSeeInOrder(['Devoirs', 'Récompenses', 'Historique', 'Nouvelles']);
        $this->get('/modules/homework/kids/theo/sheet?screen=homework')->assertOk()->assertSee('Imprimer tout');
    }

    public function test_the_settings_pages_render_in_french(): void
    {
        $this->post('/settings/unlock', ['pin' => '4821']);

        $this->get('/settings')->assertOk()->assertSee('Verrouiller les réglages')->assertSee('Calendriers');
        $this->get('/modules/homework/kid-pins')->assertOk()->assertSee('Enfants');
    }
}
