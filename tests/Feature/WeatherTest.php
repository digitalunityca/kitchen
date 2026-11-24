<?php

namespace Tests\Feature;

use App\Actions\Weather\FetchForecast;
use App\Dashboard\ModuleRegistry;
use App\Modules\Weather\WeatherModule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WeatherTest extends TestCase
{
    private const KEY = 'dashboard.modules.'.WeatherModule::class;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->configure(['city' => 'Ottawa', 'lat' => '45.42', 'lon' => '-75.69']);
    }

    /** Modules are built from config once, so rebuild them after changing it. */
    private function configure(array $values): void
    {
        foreach ($values as $key => $value) {
            config([self::KEY.'.'.$key => $value]);
        }
        $this->app->instance(ModuleRegistry::class, new ModuleRegistry);
    }

    /** An Open-Meteo response at 13:45 local time: rain gets likely at 3 PM. */
    private function openMeteo(array $pops = [10, 20, 30, 70, 40, 0]): array
    {
        $hours = ['12', '13', '14', '15', '16', '17'];

        return [
            'current' => ['time' => '2026-11-24T13:45', 'temperature_2m' => -0.6, 'weather_code' => 3, 'is_day' => 1],
            'hourly' => [
                'time' => array_map(fn ($h) => "2026-11-24T$h:00", $hours),
                'temperature_2m' => [-1.2, -0.6, 0.4, 1.6, 1.1, -0.2],
                'precipitation_probability' => $pops,
                'weather_code' => [3, 3, 3, 61, 61, 3],
            ],
            'daily' => ['temperature_2m_max' => [2.4, 5.0], 'temperature_2m_min' => [-3.5, -1.0]],
        ];
    }

    public function test_it_shows_the_open_meteo_forecast(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->openMeteo())]);

        $this->get('/')->assertOk()
            ->assertSee('Outside · Ottawa')
            ->assertSee('−1°')
            ->assertSee('Cloudy · H 2° L −4°')
            ->assertSee('<div><span>2 PM</span><span>0°</span></div>', false)
            ->assertSee('<div><span>5 PM</span><span>0°</span></div>', false)
            ->assertSee('id="weather-sample" hidden', false);

        Http::assertSent(fn (Request $r) => str_starts_with($r->url(), FetchForecast::URL)
            && $r['latitude'] == 45.42 && $r['longitude'] == -75.69 && $r['timezone'] === 'auto');
    }

    public function test_json_endpoint_has_the_summary_and_icons(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->openMeteo())]);

        $this->getJson('/modules/weather/now')->assertOk()
            ->assertJsonPath('real', true)
            ->assertJsonPath('temp', -1)
            ->assertJsonPath('label', 'Cloudy')
            ->assertJsonPath('icon', 'cloudy')
            ->assertJsonPath('hours.0.label', '2 PM')
            ->assertJsonPath('hours.1.icon', 'rain')
            ->assertJsonPath('precip', 'Rain likely 3–4 PM')
            ->assertJsonPath('precip_icon', 'rain')
            ->assertJsonPath('summary', '☁️ −1° Cloudy · H 2° L −4° · 🌧️ Rain likely 3–4 PM')
            ->assertJson(fn ($json) => $json->whereType('icon_svg', 'string')->etc());
    }

    public function test_no_likely_rain_leaves_the_summary_short(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->openMeteo([0, 0, 10, 20, 30, 0]))]);

        $this->getJson('/modules/weather/now')
            ->assertJsonPath('precip', null)
            ->assertJsonPath('summary', '☁️ −1° Cloudy · H 2° L −4°');
    }

    public function test_forecast_is_cached_for_15_minutes(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->openMeteo())]);

        $this->getJson('/modules/weather/now')->assertOk();
        $this->getJson('/modules/weather/now')->assertOk();
        Http::assertSentCount(1);

        $this->travel(16)->minutes();
        $this->getJson('/modules/weather/now')->assertOk();
        Http::assertSentCount(2);
    }

    public function test_api_failure_falls_back_to_sample_data(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response('oops', 500)]);

        $this->get('/')->assertOk()
            ->assertSee('Sample data')
            ->assertDontSee('id="weather-sample" hidden', false)
            ->assertSee('Partly cloudy · H 6° L −1°');
        $this->getJson('/modules/weather/now')->assertJsonPath('real', false);
    }

    public function test_without_coordinates_it_shows_sample_data_and_calls_nothing(): void
    {
        $this->configure(['lat' => null, 'lon' => '']);
        Http::fake();

        $this->get('/')->assertOk()->assertSee('Sample data')->assertSee('4°');
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'open-meteo'));
    }
}
