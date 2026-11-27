<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Homework\AddHomeworkTasks;
use App\Actions\Homework\BuildAdditionSheet;
use App\Dashboard\ModuleRegistry;
use App\Models\HomeworkTask;
use App\Models\Kid;
use App\Models\KidPin;
use App\Modules\Homework\HomeworkModule;
use App\Modules\Homework\HomeworkStore;
use App\Modules\Homework\KidPinStore;
use App\Modules\Homework\KidSession;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeworkTest extends TestCase
{
    private const SHEET = '/modules/homework/kids/theo/sheet';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['dashboard.pin' => '4821']);
        // Wednesday 25 November 2026, after school.
        $this->travelTo(CarbonImmutable::parse('2026-11-25 16:00', HomeworkModule::TIMEZONE));
    }

    public function test_each_kid_gets_a_button_under_home_and_a_homework_area(): void
    {
        $this->get('/')->assertOk()
            ->assertSeeInOrder(['<nav class="side-menu"', 'id="menu-home"', 'data-kid="theo"', 'data-kid="arthur"', 'id="habs-open"', '</nav>'], false)
            ->assertSee('Theo’s homework', false)
            ->assertSee('Arthur’s homework', false)
            ->assertSee('/modules/homework/kids/arthur/sheet', false)
            ->assertSee('--kid-ink: #A66000', false);
    }

    public function test_kids_come_from_the_database_seeded_from_config(): void
    {
        $this->assertSame(['theo', 'arthur'], Kid::query()->orderBy('position')->pluck('slug')->all());

        // A new kid in the config is added by the seed; a kid's row can then be changed in the database.
        config(['dashboard.modules.'.HomeworkModule::class.'.kids' => [['name' => 'Léa', 'badge' => '🦄', 'color' => '#AF52DE']]]);
        Kid::seedFromConfig();
        Kid::query()->where('slug', 'arthur')->update(['name' => 'Arthur B.']);
        $this->app->instance(ModuleRegistry::class, new ModuleRegistry);

        $this->get('/')->assertOk()->assertSee('data-kid="lea"', false)->assertSee('🦄')->assertSee('data-kid="theo"', false)->assertSee('Arthur B.');
    }

    public function test_the_area_opens_on_the_pin_screen_and_the_kid_pin_opens_it(): void
    {
        app(KidPinStore::class)->put('theo', '1234');

        $this->get(self::SHEET)->assertOk()->assertSee('data-screen="pin"', false)->assertSee('data-max="4"', false)->assertSee('Type your PIN')->assertDontSee('data-go="homework"', false);
        $this->get(self::SHEET.'?screen=rewards')->assertSee('data-screen="pin"', false)->assertDontSee('Choose dessert');

        $this->post('/modules/homework/kids/theo/unlock', ['pin' => '9999'])->assertForbidden()->assertJson(['error' => 'That’s not the right PIN.']);
        $this->post('/modules/homework/kids/theo/unlock', ['pin' => '1234'])->assertOk()
            ->assertSee('data-screen="home"', false)
            ->assertSeeInOrder(['Homework', 'Rewards', 'History', 'News']);
        $this->get(self::SHEET.'?screen=news')->assertOk()->assertSee('Messages from your teachers will show here.');

        // Theo's PIN doesn't open Arthur's homework.
        app(KidPinStore::class)->put('arthur', '5555');
        $this->get('/modules/homework/kids/arthur/sheet')->assertSee('data-screen="pin"', false);
        $this->post('/modules/homework/kids/arthur/unlock', ['pin' => '1234'])->assertForbidden();

        // ✕ signs Theo out.
        $this->post('/modules/homework/kids/theo/lock')->assertNoContent();
        $this->get(self::SHEET)->assertSee('data-screen="pin"', false);
    }

    public function test_a_kid_photo_shows_on_the_pin_screen_when_a_parent_added_one(): void
    {
        $this->get(self::SHEET)->assertSee('<span class="kz-avatar big" aria-hidden="true">T</span>', false);
        $this->get('/modules/homework/kids/theo/photo')->assertNotFound();

        Storage::disk('local')->put('kid-photos/theo.jpg', 'jpeg bytes');
        $this->app->instance(ModuleRegistry::class, new ModuleRegistry);

        $this->get(self::SHEET)->assertSee('<img class="kz-avatar big" src="/modules/homework/kids/theo/photo?v=', false);
        $this->get('/')->assertSee('class="kid-btn has-photo" data-kid="theo"', false)->assertSee('class="kid-btn" data-kid="arthur"', false);
        $this->get('/modules/homework/kids/theo/photo')->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get('/modules/homework/kids/arthur/photo')->assertNotFound();
    }

    public function test_the_parent_pin_opens_any_kid_and_a_kid_without_a_pin_is_told_to_ask(): void
    {
        $this->get(self::SHEET)->assertSee('Ask a parent to set your PIN in Settings.')->assertSee('data-max="8"', false);
        $this->post('/modules/homework/kids/theo/unlock', ['pin' => '4821'])->assertOk()->assertSee('data-screen="home"', false);

        app(KidPinStore::class)->put('arthur', '5555');
        $this->post('/modules/homework/kids/arthur/unlock', ['pin' => '4821'])->assertOk()->assertSee('Hi Arthur!');
        $this->assertStringNotContainsString('5555', (string) KidPin::query()->find(Kid::idFor('arthur'))?->hash);
        $this->assertTrue(app(KidPinStore::class)->matches('arthur', '5555'));
    }

    public function test_wrong_pins_lock_the_kid_out_for_a_minute(): void
    {
        app(KidPinStore::class)->put('theo', '1234');

        for ($i = 0; $i < 4; $i++) {
            $this->post('/modules/homework/kids/theo/unlock', ['pin' => '0000'])->assertForbidden();
        }
        $this->post('/modules/homework/kids/theo/unlock', ['pin' => '0000'])->assertStatus(429)->assertJsonPath('wait', 60);
        $this->post('/modules/homework/kids/theo/unlock', ['pin' => '1234'])->assertStatus(429);

        $this->travel(61)->seconds();
        $this->post('/modules/homework/kids/theo/unlock', ['pin' => '1234'])->assertOk();
    }

    public function test_background_polling_never_uses_up_the_pin_screens_allowance(): void
    {
        app(KidPinStore::class)->put('theo', '1234');

        // The dashboard polls several endpoints all the time (homework events, now playing...).
        for ($i = 0; $i < 40; $i++) {
            $this->getJson('/modules/homework/events')->assertOk();
        }

        $this->post('/modules/homework/kids/theo/unlock', ['pin' => '1234'])->assertOk()->assertSee('data-screen="home"', false);
    }

    public function test_the_kid_is_signed_out_after_five_idle_minutes(): void
    {
        $this->signIn('theo');
        $this->travel(4)->minutes();
        $this->get(self::SHEET)->assertSee('data-screen="home"', false);
        $this->travel(KidSession::IDLE_SECONDS + 1)->seconds();
        $this->get(self::SHEET)->assertSee('data-screen="pin"', false);
    }

    public function test_homework_shows_only_the_choices_for_the_kids_grade_and_print_all(): void
    {
        $this->signIn('arthur');
        $this->get('/modules/homework/kids/arthur/sheet?screen=homework')->assertOk()
            ->assertSee('data-choice="writing"', false)
            ->assertSee('15 min')
            ->assertSee('data-print-all disabled', false)
            ->assertDontSee('Choose dessert');

        $this->signIn('theo');
        $this->pick('theo', ['reading' => 1]);
        $this->get(self::SHEET.'?screen=homework')
            ->assertSee('data-choice="division"', false)
            ->assertDontSee('data-choice="writing"', false)
            ->assertSee('data-choice="reading" data-points="10" aria-pressed="false">', false)
            ->assertSee('1 printed today');
    }

    public function test_weekends_have_no_homework(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-28 10:00', HomeworkModule::TIMEZONE));
        $this->signIn('theo');

        $this->get(self::SHEET.'?screen=homework')->assertOk()->assertSee('No homework today')->assertDontSee('data-print-all', false);
        $this->postJson('/modules/homework/kids/theo/print', ['counts' => ['addition' => 1]])->assertStatus(422);
    }

    public function test_addition_sheets_are_made_on_the_hub_in_columns_sized_to_the_biggest_number(): void
    {
        config(['dashboard.homework_print_spool' => $this->spool()]);
        Http::preventStrayRequests(); // no AI call for additions

        $this->signIn('theo');
        $this->postJson('/modules/homework/kids/theo/print', ['counts' => ['addition' => 1]])->assertOk();
        $sheet = app(HomeworkStore::class)->tasks('theo', '2026-11-25')[0]['worksheet'];
        $this->assertCount(20, $sheet);
        $this->assertSame((string) ($sheet[0]['top'] + $sheet[0]['bottom']), $sheet[0]['answer']);

        // 4-digit + 3-digit numbers: the column fits a 5-digit sum, and the title names the digits.
        $layout = BuildAdditionSheet::layout([['top' => 9876, 'bottom' => 543], ['top' => 1200, 'bottom' => 999]]);
        $this->assertSame(['chars' => 6, 'font' => 19, 'rows' => 1, 'digits' => 4, 'title' => 'Additions : 3 et 4 chiffres'], $layout);
        $this->assertSame(5, BuildAdditionSheet::layout([['top' => 99, 'bottom' => 99]])['chars'] + 1);

        $this->get('/modules/homework/kids/theo/preview/2026-11-25/t1')->assertOk()
            ->assertSee('Calcule chaque somme.')
            ->assertSee('<p class="sheet-no">#0001</p>', false)
            ->assertSee('aria-label="Code QR de confirmation"', false)
            ->assertDontSee('Scannez le code QR');
    }

    public function test_worksheets_are_generated_only_when_print_all_is_tapped(): void
    {
        config(['services.openai.key' => 'test-key', 'dashboard.homework_print_spool' => $this->spool()]);
        Http::preventStrayRequests();
        Http::fake(['https://api.openai.com/v1/responses' => Http::sequence()
            ->push($this->packet(['Lecture']))
            ->push($this->packet(['Lecture']))]);

        $this->signIn('theo');
        $this->get(self::SHEET.'?screen=homework')->assertOk();
        Http::assertNothingSent();

        $this->postJson('/modules/homework/kids/theo/print', ['counts' => ['multiplication' => 1, 'reading' => 1]])->assertOk()->assertJson(['ok' => true, 'sheets' => 2]);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => json_decode($request['input'], true)['topics'] === ['Lecture']);

        $tasks = app(HomeworkStore::class)->tasks('theo', '2026-11-25');
        $this->assertSame(['done', 'done'], array_column($tasks, 'status'));
        $this->assertArrayHasKey('worksheet', $tasks[0]);
        $this->assertCount(9, $tasks[0]['worksheet']); // multiplications are made on the hub, in grids
        $this->assertSame((string) ($tasks[0]['worksheet'][0]['top'] * $tasks[0]['worksheet'][0]['bottom']), $tasks[0]['worksheet'][0]['answer']);
        $this->assertCount(1, glob($this->spool().'/*.pdf'));
        $this->assertSame(0, app(HomeworkStore::class)->balance('theo'));

        // No daily limit: another reading and multiplication print fine; only a runaway pick is refused.
        $this->postJson('/modules/homework/kids/theo/print', ['counts' => ['multiplication' => 1, 'reading' => 1]])->assertOk();
        $this->assertCount(4, app(HomeworkStore::class)->tasks('theo', '2026-11-25'));
        $this->postJson('/modules/homework/kids/theo/print', ['counts' => ['addition' => 13]])->assertStatus(422)->assertJson(['error' => 'That’s a lot! Print up to 12 sheets at a time.']);
        $this->postJson('/modules/homework/kids/theo/print', ['counts' => []])->assertStatus(422)->assertJson(['error' => 'Pick some homework first.']);
    }

    public function test_a_failed_print_takes_the_picked_tasks_back(): void
    {
        config(['services.openai.key' => 'test-key', 'dashboard.homework_print_spool' => $this->spool()]);
        Http::fake(['https://api.openai.com/v1/responses' => Http::response('down', 503)]);
        $this->signIn('theo');

        $this->postJson('/modules/homework/kids/theo/print', ['counts' => ['division' => 2]])->assertStatus(422)->assertJson(['error' => 'Couldn’t generate the worksheets. Try again.']);
        $this->assertEmpty(app(HomeworkStore::class)->tasks('theo', '2026-11-25') ?? []);

        config(['dashboard.homework_print_spool' => null]);
        $this->postJson('/modules/homework/kids/theo/print', ['counts' => ['addition' => 1]])->assertStatus(422)->assertJson(['error' => 'The printer isn’t reachable right now. Ask a parent.']);
    }

    public function test_signed_out_kids_cannot_print_check_or_buy(): void
    {
        $this->pick('theo', ['addition' => 1]);

        $this->postJson('/modules/homework/kids/theo/print', ['counts' => ['addition' => 1]])->assertUnauthorized()->assertJson(['locked' => true]);
        $this->postJson('/modules/homework/kids/theo/check/2026-11-25/t1', ['pin' => '4821'])->assertUnauthorized();
        $this->postJson('/modules/homework/kids/theo/rewards/dessert', ['pin' => '4821'])->assertUnauthorized();
    }

    public function test_points_count_only_after_a_parent_checks_with_the_parent_pin(): void
    {
        $this->pick('arthur', ['addition' => 1, 'writing' => 1, 'reading' => 1]);
        app(KidPinStore::class)->put('arthur', '1111');
        $this->signIn('arthur', '1111');

        $this->get('/modules/homework/kids/arthur/sheet?screen=history')->assertOk()
            ->assertSee('data-filter="check"', false)
            ->assertSee('To check <span>3</span>', false)
            ->assertSeeInOrder(['Reading', 'Writing', 'Addition'])
            ->assertSee('>Approve</button>', false);

        $check = '/modules/homework/kids/arthur/check/2026-11-25/t1';
        $this->post($check)->assertForbidden()->assertJson(['pin' => true]);
        // The kid's own PIN isn't a parent's.
        $this->post($check, ['pin' => '1111'])->assertForbidden()->assertJson(['error' => 'Wrong parent PIN.']);
        $this->assertSame(0, app(HomeworkStore::class)->balance('arthur'));

        $this->post($check, ['pin' => '4821', 'decision' => 'approve'])->assertOk()
            ->assertSee('Approved <span>1</span>', false)->assertSee('Approved <span class="kz-score-tag">· Good</span> · +10', false)
            ->assertSee('+10 points</b>', false);
        $this->assertSame(10, app(HomeworkStore::class)->balance('arthur'));

        // Each check needs the PIN again, and approving twice doesn't pay twice.
        $this->post($check)->assertForbidden();
        $this->post($check, ['pin' => '4821'])->assertOk();
        $this->assertSame(10, app(HomeworkStore::class)->balance('arthur'));

        // "Not done": no points, stays in the list.
        $this->post('/modules/homework/kids/arthur/check/2026-11-25/t2', ['pin' => '4821', 'decision' => 'missed'])->assertOk()
            ->assertSee('Not done <span>1</span>', false)
            ->assertSee('<li class="kz-row is-missed" data-status="missed">', false);
        $this->assertSame('missed', app(HomeworkStore::class)->tasks('arthur', '2026-11-25')[1]['status']);

        $this->get('/modules/homework/kids/arthur/sheet')->assertSee('<b>1 of 2</b> checked today', false)->assertSee('1 to check');

        $this->post('/modules/homework/kids/arthur/check/2026-11-25/t3', ['pin' => '4821']);
        $this->get('/modules/homework/kids/arthur/sheet?screen=history')->assertSee('data-filter="all"', false)->assertDontSee('>Approve</button>', false);
    }

    public function test_the_tablet_hears_about_approvals_and_rewards_and_badges_clear_on_sign_in(): void
    {
        $this->pick('theo', ['addition' => 1, 'division' => 1]);
        $page = $this->get('/')->assertOk()->assertSee('id="hw-cheer"', false)->assertSee('data-cursor="0"', false);
        $page->assertSee('<span class="kid-badge" data-kid-badge="theo"  hidden >', false);

        $this->getJson('/modules/homework/events?after=0')->assertOk()->assertExactJson(['next' => 0, 'events' => [], 'badges' => ['theo' => 0, 'arthur' => 0]]);

        // Two sheets approved (here from the tablet; the QR link on paper does the same): one combined celebration.
        $this->signIn('theo');
        foreach (['t1', 't2'] as $task) {
            $this->post("/modules/homework/kids/theo/check/2026-11-25/{$task}", ['pin' => '4821']);
        }
        $this->getJson('/modules/homework/events?after=0')->assertOk()
            ->assertJsonPath('next', 2)
            ->assertJsonPath('events', [['kind' => 'approved', 'kid' => 'theo', 'points' => 25, 'sheets' => 2, 'balance' => 25]])
            ->assertJsonPath('badges.theo', 25);
        $this->getJson('/modules/homework/events?after=2')->assertJsonPath('events', []);
        $this->get('/')->assertSee('data-kid-badge="theo" >+25</span>', false);

        // A reward bought: a lighter celebration.
        $this->post('/modules/homework/kids/theo/rewards/dessert', ['pin' => '4821'])->assertOk();
        $this->getJson('/modules/homework/events?after=2')->assertJsonPath('events', [['kind' => 'reward', 'kid' => 'theo', 'points' => -25, 'name' => 'Choose dessert', 'balance' => 0]]);

        // Theo opens his area: his badge clears.
        $this->post('/modules/homework/kids/theo/lock');
        $this->signIn('theo');
        $this->getJson('/modules/homework/events')->assertJsonPath('badges.theo', 0)->assertJsonPath('events', []);

        // Old news isn't celebrated when the tablet comes back hours later.
        $this->travel(3)->hours();
        $this->getJson('/modules/homework/events?after=0')->assertJsonPath('events', []);
    }

    public function test_printed_sheets_get_a_number_shown_in_history_and_on_the_paper(): void
    {
        $store = app(HomeworkStore::class);
        // Two sheets printed before numbering existed, on different days.
        $store->update(function (array &$data): void {
            $sheet = [['question' => 'Combien font 2 + 2?', 'answer' => '4']];
            $data['days']['2026-11-24']['theo'] = [['id' => 't1', 'template' => 'addition', 'points' => 10, 'status' => 'approved', 'seed' => 1, 'worksheet' => $sheet]];
            $data['days']['2026-11-25']['arthur'] = [['id' => 't1', 'template' => 'addition', 'points' => 10, 'status' => 'done', 'seed' => 2, 'worksheet' => $sheet]];
        });

        config(['services.openai.key' => 'test-key', 'dashboard.homework_print_spool' => $this->spool()]);
        Http::fake(['https://api.openai.com/v1/responses' => Http::response($this->packet(['Divisions']))]);
        $this->signIn('theo');
        $this->postJson('/modules/homework/kids/theo/print', ['counts' => ['division' => 1]])->assertOk();

        $this->assertSame(1, $store->tasks('theo', '2026-11-24')[0]['number']);
        $this->assertSame(2, $store->tasks('arthur', '2026-11-25')[0]['number']);
        $this->assertSame(3, $store->tasks('theo', '2026-11-25')[0]['number']);

        $this->get(self::SHEET.'?screen=history')
            ->assertSeeInOrder(['Today', '#0003', 'Division', 'Waiting for a parent', 'Yesterday', '#0001', 'Addition', 'Approved · +10'])
            ->assertSee('/modules/homework/kids/theo/preview/2026-11-25/t1', false);

        $this->get('/modules/homework/kids/theo/preview/2026-11-25/t1')->assertOk()
            ->assertSee('<p class="sheet-no">#0003</p>', false)
            ->assertSee('Combien font 6 × 7?')
            ->assertDontSee('class="toolbar"', false);
    }

    public function test_a_preview_is_only_for_the_signed_in_kid_or_a_parent(): void
    {
        app(HomeworkStore::class)->update(function (array &$data): void {
            $data['days']['2026-11-25']['theo'] = [['id' => 't1', 'template' => 'addition', 'points' => 10, 'status' => 'done', 'seed' => 1, 'worksheet' => [['question' => 'Q?', 'answer' => 'A']]]];
        });
        $preview = '/modules/homework/kids/theo/preview/2026-11-25/t1';

        $this->get($preview)->assertForbidden();
        $this->signIn('arthur');
        $this->get($preview)->assertForbidden();
        $this->signIn('theo');
        $this->get($preview)->assertOk();
        $this->get('/modules/homework/kids/theo/preview/2026-11-25/t9')->assertNotFound();

        $this->post('/modules/homework/kids/theo/lock');
        $this->get($preview)->assertForbidden();
        $this->post('/settings/unlock', ['pin' => '4821']);
        $this->get($preview)->assertOk();
    }

    public function test_rewards_show_what_the_kid_can_get_first_and_the_rest_disabled(): void
    {
        $this->signIn('theo');

        // No points: everything is disabled, the cheapest first.
        $this->get(self::SHEET.'?screen=rewards')
            ->assertDontSee('data-state="ready"', false)
            ->assertSeeInOrder(['Choose dessert', '25 more points', 'Stay up 15 min later', '30 more points'])
            ->assertSeeInOrder(['Weekend treat', 'Weekends only']);

        $this->pick('theo', ['multiplication' => 2, 'division' => 1]);
        foreach (['t1', 't2'] as $task) {
            $this->post("/modules/homework/kids/theo/check/2026-11-25/{$task}", ['pin' => '4821']);
        }

        // 30 points, one sheet still to check: affordable evening rewards wait for the check.
        $this->get(self::SHEET.'?screen=rewards')
            ->assertSee('Tonight’s rewards open once today’s homework is checked.')
            ->assertSeeInOrder(['Choose dessert', 'Yours once your homework is checked', 'Movie in the evening', '10 more points']);

        $this->post('/modules/homework/kids/theo/check/2026-11-25/t3', ['pin' => '4821']);

        // 45 points, all checked: what he can get comes first, then the rest by points missing.
        $this->get(self::SHEET.'?screen=rewards')
            ->assertSeeInOrder(['Choose dessert', 'Stay up 15 min later', 'Board game with a parent', 'Movie in the evening', 'Habs game: 2 periods', '5 more points', '30 min PS5', '15 more points', 'Habs game: 3rd period', 'After the first 2 periods'])
            ->assertSeeInOrder(['Movie in the evening', 'You can get it now'])
            ->assertSee('<li class="kz-reward is-locked" data-state="locked"  aria-disabled="true" >', false);

        // Once taken tonight, a 1-per-evening reward is disabled, not gone.
        $this->post('/modules/homework/kids/theo/rewards/movie', ['pin' => '4821'])->assertOk()
            ->assertSee('Enjoy! Movie in the evening is yours.')
            ->assertSeeInOrder(['Movie in the evening', 'Already taken tonight']);
    }

    public function test_weekend_prizes_are_enabled_only_on_weekends(): void
    {
        app(HomeworkStore::class)->addPoints('theo', 160, 'Saved');
        $this->signIn('theo');
        $this->get(self::SHEET.'?screen=rewards')->assertSeeInOrder(['Weekend treat', 'Weekends only']);

        $this->travelTo(CarbonImmutable::parse('2026-11-28 10:00', HomeworkModule::TIMEZONE));
        $this->signIn('theo');
        $this->get(self::SHEET.'?screen=rewards')
            ->assertSeeInOrder(['Weekend treat', 'You can get it now'])
            ->assertSeeInOrder(['Weekend outing', '140 more points']);
    }

    public function test_the_parent_scores_each_sheet_and_a_sheet_to_go_over_again_earns_half_the_points(): void
    {
        config(['dashboard.homework_print_spool' => $this->spool()]);
        Http::preventStrayRequests();
        $this->signIn('theo');
        $this->postJson('/modules/homework/kids/theo/print', ['counts' => ['addition' => 2, 'multiplication' => 1]])->assertOk();

        $this->get(self::SHEET.'?screen=history')->assertSee('data-parent-scores="1" data-parent-points="10"', false)
            ->assertSee('data-decision="bien"', false)->assertSee('data-decision="erreurs"', false)->assertSee('data-decision="revoir"', false);

        $this->post('/modules/homework/kids/theo/check/2026-11-25/t1', ['pin' => '4821', 'decision' => 'erreurs'])->assertOk();
        $this->post('/modules/homework/kids/theo/check/2026-11-25/t2', ['pin' => '4821', 'decision' => 'revoir'])->assertOk();
        $this->post('/modules/homework/kids/theo/check/2026-11-25/t3', ['pin' => '4821', 'decision' => 'revoir'])->assertOk()
            ->assertSee('<span class="kz-score-tag">· A few mistakes</span> · +10', false)
            ->assertSee('<span class="kz-score-tag">· Go over again</span> · +5', false)
            ->assertSee('<span class="kz-score-tag">· Go over again</span> · +8', false); // multiplication: 15 → 8, rounded up

        $this->assertSame(23, app(HomeworkStore::class)->balance('theo'));
        $this->assertSame(['erreurs', 'revoir', 'revoir'], array_column(app(HomeworkStore::class)->tasks('theo', '2026-11-25'), 'score'));
        $this->assertSame(['erreurs', 'revoir', 'revoir'], HomeworkTask::query()->orderBy('position')->pluck('score')->all());
    }

    public function test_kids_buy_rewards_with_the_parent_pin_and_save_up_for_weekend_prizes(): void
    {
        $store = app(HomeworkStore::class);
        $store->addPoints('theo', 200, 'Saved');
        $this->signIn('theo');

        // School day: nothing to buy until today's homework is checked.
        $this->post('/modules/homework/kids/theo/rewards/movie', ['pin' => '4821'])->assertStatus(422)->assertJson(['error' => 'Finish and get today’s homework checked first.']);
        $this->pick('theo', ['addition' => 1]);
        $this->post('/modules/homework/kids/theo/check/2026-11-25/t1', ['pin' => '4821']);

        $this->post('/modules/homework/kids/theo/rewards/movie')->assertForbidden()->assertJson(['pin' => true]);
        $this->post('/modules/homework/kids/theo/rewards/movie', ['pin' => '4821'])->assertOk()->assertSee('Enjoy! Movie in the evening is yours.')->assertSee('<b>170</b>', false);
        $this->post('/modules/homework/kids/theo/rewards/movie', ['pin' => '4821'])->assertStatus(422)->assertJson(['error' => 'Already taken today.']);
        $this->post('/modules/homework/kids/theo/rewards/habs-3rd', ['pin' => '4821'])->assertStatus(422)->assertJson(['error' => 'Take the first 2 periods first.']);
        $this->post('/modules/homework/kids/theo/rewards/weekend-treat', ['pin' => '4821'])->assertStatus(422)->assertJson(['error' => 'Weekend prizes are for Saturday and Sunday.']);

        $this->get(self::SHEET.'?screen=history')->assertSee('This week: <b>1</b> sheet, <b>+10</b> points', false)
            ->assertSeeInOrder(['Today', '+170 points', 'Movie in the evening', '−40'])->assertSeeInOrder(['Addition', 'Approved', 'Good', '+10']);

        // Saturday: the weekend treat (150) can be bought, the outing (300) not yet.
        $this->travelTo(CarbonImmutable::parse('2026-11-28 10:00', HomeworkModule::TIMEZONE));
        $this->signIn('theo');
        $this->post('/modules/homework/kids/theo/rewards/weekend-outing', ['pin' => '4821'])->assertStatus(422)->assertJson(['error' => 'Not enough points yet.']);
        $this->post('/modules/homework/kids/theo/rewards/weekend-treat', ['pin' => '4821'])->assertOk();
        $this->assertSame(20, $store->balance('theo'));
    }

    public function test_parents_set_and_clear_kid_pins_in_settings(): void
    {
        $this->get('/modules/homework/kid-pins')->assertRedirect();
        $this->post('/settings/unlock', ['pin' => '4821']);
        $this->get('/settings')->assertSee('Kids');
        $this->get('/modules/homework/kid-pins')->assertOk()->assertSee('No PIN yet');

        $this->post('/modules/homework/kids/theo/pin', ['pin' => '12a4'])->assertSessionHasErrors('pin');
        $this->post('/modules/homework/kids/theo/pin', ['pin' => '2468'])->assertRedirect('/modules/homework/kid-pins');
        $this->assertTrue(app(KidPinStore::class)->matches('theo', '2468'));
        $this->get('/modules/homework/kid-pins')->assertSee('PIN set')->assertDontSee('2468');

        $this->post('/modules/homework/kids/theo/pin', ['clear' => '1'])->assertRedirect();
        $this->assertFalse(app(KidPinStore::class)->has('theo'));
    }

    public function test_worksheets_print_the_same_problems_and_the_answer_key_needs_the_pin(): void
    {
        $this->pick('theo', ['addition' => 1, 'reading' => 1]);

        $sheet = $this->get('/modules/homework/kids/theo/2026-11-25/t1/worksheet')->assertOk()
            ->assertSee('Additions')->assertSee('Prénom :', false)->assertSee('mercredi 25 novembre 2026')->assertDontSee('CORRIGÉ');
        $this->get('/modules/homework/kids/theo/2026-11-25/t1/worksheet')->assertSee($this->firstProblem($sheet->getContent()), false);

        $this->get('/modules/homework/kids/theo/2026-11-25/t1/answers')->assertRedirect();
        $this->post('/settings/unlock', ['pin' => '4821']);
        $this->get('/modules/homework/kids/theo/2026-11-25/t1/answers')->assertOk()->assertSee('CORRIGÉ');

        // Reading has no worksheet; unknown days have none either.
        $this->get('/modules/homework/kids/theo/2026-11-25/t2/worksheet')->assertNotFound();
        $this->get('/modules/homework/kids/theo/2026-11-20/t1/worksheet')->assertNotFound();
    }

    public function test_parents_set_the_words_to_copy(): void
    {
        $this->post('/settings/unlock', ['pin' => '4821']);
        $this->get('/modules/homework/settings')->assertOk()->assertSee('Words to copy')->assertSee('maison');

        $this->post('/modules/homework/kids/arthur/words', ['words' => "papillon\n\nmagicien\npapillon"])->assertRedirect('/modules/homework/settings');
        $this->assertSame(['papillon', 'magicien'], app(HomeworkStore::class)->words('arthur'));

        $this->pick('arthur', ['writing' => 1]);
        $this->get('/modules/homework/kids/arthur/2026-11-25/t1/worksheet')->assertOk()->assertSee('Écriture')->assertSee('papillon')->assertSee('magicien');
    }

    public function test_the_packet_prints_ai_worksheets_and_signed_qr_approval_awards_points_once(): void
    {
        config(['services.openai.key' => 'test-key', 'dashboard.allowed_ips' => ['192.0.2.1']]);
        $url = 'https://api.openai.com/v1/responses';
        $exercise = ['question' => 'Combine 12 et 15.', 'answer' => '27'];
        $worksheets = [
            ['topic' => 'Divisions', 'exercises' => array_fill(0, 4, $exercise)],
            ['topic' => 'Lecture', 'exercises' => array_fill(0, 4, ['question' => 'Que lit le personnage?', 'answer' => 'Un livre.'])],
        ];
        Http::preventStrayRequests();
        Http::fake([$url => Http::sequence()
            ->pushStatus(503)
            ->push(['status' => 'completed', 'output' => [
                ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode(['worksheets' => $worksheets])]]],
            ]])]);

        $packet = '/modules/homework/kids/theo/2026-11-25/packet';
        $this->pick('theo', ['division' => 1, 'reading' => 1]);
        $this->get($packet)->assertOk()->assertSee('Generating 2 worksheets');
        $this->postJson($packet)->assertUnprocessable();
        $this->assertArrayNotHasKey('worksheet', app(HomeworkStore::class)->tasks('theo', '2026-11-25')[0]);

        $this->postJson($packet)->assertNoContent();
        $this->postJson($packet)->assertNoContent(); // A reprint uses the persisted questions.
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->url() === $url
            && ! $request['store']
            && json_decode($request['input'], true) === ['grade' => 5, 'topics' => ['Divisions', 'Lecture'], 'copy_words' => []]);

        $html = $this->get($packet)->assertOk()
            ->assertSee('Print all worksheets')->assertSee('Combine 12 et 15.')->assertSee('Que lit le personnage?')
            ->assertDontSee('<strong>27</strong>', false)
            ->getContent();
        $this->assertSame(2, substr_count($html, 'aria-label="Code QR de confirmation"'));
        preg_match_all('~href="([^"]+/confirm/[^"]+)"~', $html, $matches);
        $this->assertCount(2, $matches[1]);
        $spool = $this->spool();
        config(['dashboard.homework_print_spool' => $spool]);
        $job = $packet.'/print/21bbd273-5214-4dd5-b17d-f87a92461330';
        $this->postJson($job)->assertAccepted()->assertJsonPath('state', 'queued');
        $this->postJson($job)->assertAccepted()->assertJsonPath('state', 'queued');
        $files = glob($spool.'/*.pdf');
        $this->assertCount(1, $files);
        $this->assertStringStartsWith('%PDF-', file_get_contents($files[0]));
        rename($files[0], $spool.'/21bbd273-5214-4dd5-b17d-f87a92461330.sent');
        $this->getJson($job)->assertJsonPath('state', 'sent');
        $link = html_entity_decode($matches[1][0]);
        $link = parse_url($link, PHP_URL_PATH).'?'.parse_url($link, PHP_URL_QUERY);

        $this->get('/modules/homework/confirm/theo/2026-11-25/t1')->assertForbidden();
        $this->get($link.'&tampered=1')->assertForbidden();
        $headers = ['CF-Connecting-IP' => '198.51.100.10'];
        $this->get($link, $headers)->assertOk()->assertSee('Parent PIN');
        $this->post($link, ['pin' => '0000'], $headers)->assertForbidden()->assertSee('Incorrect parent PIN.');
        $this->assertSame(0, app(HomeworkStore::class)->balance('theo'));

        $this->post($link, ['pin' => '4821'], $headers)->assertOk()->assertSee('Homework confirmed');
        $this->post($link, ['pin' => '4821'], $headers)->assertOk()->assertSee('15 points awarded.');
        $this->assertSame(15, app(HomeworkStore::class)->balance('theo'));
        $this->assertSame('todo', app(HomeworkStore::class)->tasks('theo', '2026-11-25')[1]['status']);

        $this->get('/modules/homework/kids/theo/2026-11-25/t1/answers')->assertRedirect();
        $this->post('/settings/unlock', ['pin' => '4821']);
        $this->get('/modules/homework/kids/theo/2026-11-25/t1/answers')->assertOk()->assertSee('<strong>27</strong>', false);
    }

    public function test_unknown_kids_are_not_found(): void
    {
        $this->get('/modules/homework/kids/nobody/sheet')->assertNotFound();
    }

    /** @param  array<string, int>  $counts */
    private function pick(string $kid, array $counts): void
    {
        $profile = app(ModuleRegistry::class)->get('homework')->kid($kid);
        app(AddHomeworkTasks::class)->handle($profile, CarbonImmutable::now(HomeworkModule::TIMEZONE), $counts);
    }

    private function signIn(string $kid, string $pin = '4821'): void
    {
        $this->post("/modules/homework/kids/{$kid}/unlock", ['pin' => $pin])->assertOk();
    }

    private function spool(): string
    {
        $spool = Storage::disk('local')->path('spool');
        is_dir($spool) || mkdir($spool, recursive: true);

        return $spool;
    }

    /**
     * @param  list<string>  $topics
     * @return array<string, mixed>
     */
    private function packet(array $topics): array
    {
        $worksheets = array_map(fn (string $topic): array => ['topic' => $topic, 'exercises' => array_fill(0, 4, ['question' => 'Combien font 6 × 7?', 'answer' => '42'])], $topics);

        return ['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode(['worksheets' => $worksheets])]]]]];
    }

    private function firstProblem(string $html): string
    {
        preg_match('/<div class="stack"><div>([^<]+)<\/div>/', $html, $match);

        return $match[0];
    }
}
