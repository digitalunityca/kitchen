<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Homework\BuildAdditionSheet;
use App\Actions\Homework\BuildMultiplicationSheet;
use App\Actions\Homework\CheckDivisionItems;
use App\Actions\Homework\GenerateWritingWords;
use App\Actions\Homework\SheetPage;
use App\Actions\Learning\PickExerciseVariant;
use App\Actions\Learning\SeedKidProgress;
use App\Actions\Learning\SyncCurriculum;
use App\Actions\Learning\UpdateSkillProgress;
use App\Models\ExerciseVariant;
use App\Models\HomeworkTask;
use App\Models\Kid;
use App\Models\KidSkillProgress;
use App\Models\Skill;
use App\Modules\Homework\HomeworkModule;
use App\Modules\Homework\HomeworkStore;
use App\Support\PdfText;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class LearningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['dashboard.pin' => '4821', 'dashboard.homework_print_spool' => Storage::disk('local')->path('spool')]);
        is_dir(Storage::disk('local')->path('spool')) || mkdir(Storage::disk('local')->path('spool'), recursive: true);
        // Wednesday 25 November 2026, after school.
        $this->travelTo(CarbonImmutable::parse('2026-11-25 16:00', HomeworkModule::TIMEZONE));
    }

    public function test_the_catalogue_comes_from_the_progression_files(): void
    {
        $this->assertSame(70, Skill::query()->count());
        $this->assertSame(48, ExerciseVariant::query()->where('active', true)->count());

        $product = Skill::query()->where('code', 'MATH.OPS.A.7b')->firstOrFail();
        $this->assertSame('Multiplication en colonnes : 3 chiffres × 2 chiffres', $product->name_fr);
        $this->assertSame(['apprend', 11, 6], [$product->grades->firstWhere('grade', 5)->expectation, $product->grades->firstWhere('grade', 5)->window_start, $product->grades->firstWhere('grade', 5)->window_end]);
        $this->assertSame('maitrise', $product->grades->firstWhere('grade', 6)->expectation);
        $this->assertSame(['MATH.OPS.A.7a'], $product->prerequisites->pluck('code')->all());
        $this->assertTrue($product->grades->firstWhere('grade', 5)->covers(1)); // November → June runs over New Year
        $this->assertFalse($product->grades->firstWhere('grade', 5)->covers(9));

        // Running it again changes nothing; a prerequisite cycle is refused.
        $this->artisan('hub:sync-curriculum')->expectsOutputToContain('Skills: 70')->assertSuccessful();
        $this->assertSame(70, Skill::query()->count());
        $file = tempnam(sys_get_temp_dir(), 'skills');
        file_put_contents($file, json_encode(['skills' => [
            ['code' => 'A', 'subject' => 'math', 'domain' => 'x', 'name' => 'A', 'marks' => '>*....', 'prereqs' => ['B']],
            ['code' => 'B', 'subject' => 'math', 'domain' => 'x', 'name' => 'B', 'marks' => '>*....', 'prereqs' => ['A']],
        ]]));
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Prerequisite cycle: A → B → A');
            app(SyncCurriculum::class)->handle($file);
        } finally {
            unlink($file);
        }
    }

    public function test_kids_start_from_their_grade_and_the_sheets_already_approved(): void
    {
        KidSkillProgress::query()->delete();
        $theo = Kid::idFor('theo');
        HomeworkTask::query()->create(['kid_id' => $theo, 'date' => '2026-11-24', 'task_id' => 't1', 'position' => 0, 'template' => 'multiplication', 'status' => 'approved', 'data' => ['id' => 't1', 'template' => 'multiplication', 'points' => 15, 'status' => 'approved', 'seed' => 1]]);

        app(SeedKidProgress::class)->handle(CarbonImmutable::parse('2026-11-25'));

        // Theo, 5e: column addition was ★ in 4e → mastered (upkeep); his old 3-digit × 2-digit sheet → level 2 of 7b.
        $this->assertSame(['maitrise', 4], $this->progress('theo', 'MATH.OPS.A.4b'));
        $this->assertSame(['en_cours', 2], $this->progress('theo', 'MATH.OPS.A.7b'));
        $this->assertSame(1, KidSkillProgress::query()->where('kid_id', $theo)->where('skill_id', Skill::query()->where('code', 'MATH.OPS.A.7b')->value('id'))->value('attempts'));
        // Arthur, 2e: written addition is ★ this year after → in 1re: a little into the year in late November.
        $this->assertSame(['en_cours', 2], $this->progress('arthur', 'MATH.OPS.A.4a'));
        $this->assertSame(['nouveau', 1], $this->progress('arthur', 'FR.ACC.A.3'));
    }

    public function test_each_sheet_gets_a_skill_and_level_from_this_month_and_the_numbers_follow_the_level(): void
    {
        $this->signIn('arthur');
        $this->get('/modules/homework/kids/arthur/sheet?screen=homework')->assertOk()
            ->assertSee('+10 points · Level 2')->assertSee('<small class="kz-choice-level">2 chiffres + 2 chiffres, sans retenue</small>', false)->assertSee('Grammar');

        $this->postJson('/modules/homework/kids/arthur/print', ['counts' => ['addition' => 2, 'multiplication' => 1]])->assertOk();
        $tasks = app(HomeworkStore::class)->tasks('arthur', '2026-11-25');

        // First addition: this month's ★ skill at his level, numbers without carrying; the second one a different skill (variety).
        $this->assertSame(['MATH.OPS.A.4a', 2, 'month'], [$tasks[0]['skill'], $tasks[0]['level'], $tasks[0]['reason']]);
        foreach ($tasks[0]['worksheet'] as $p) {
            $this->assertTrue($p['top'] >= 10 && $p['bottom'] >= 10 && $p['top'] <= 99 && $p['bottom'] <= 99);
            $this->assertFalse(BuildAdditionSheet::carries($p['top'], $p['bottom']));
        }
        $this->assertNotSame('MATH.OPS.A.4a', $tasks[1]['skill']);
        $this->assertSame('MATH.SOP.A.3a', $tasks[2]['skill']);
        $this->assertSame(Skill::query()->where('code', 'MATH.OPS.A.4a')->value('id'), HomeworkTask::query()->where('task_id', 't1')->value('skill_id'));
    }

    public function test_the_parents_score_moves_the_level_up_or_back_to_the_prerequisite(): void
    {
        $this->signIn('arthur');
        $kid = Kid::idFor('arthur');
        $skill = (int) Skill::query()->where('code', 'MATH.OPS.A.4a')->value('id');
        $update = app(UpdateSkillProgress::class);
        $pick = fn (string $date): array => app(PickExerciseVariant::class)->handle($kid, 2, 'addition', CarbonImmutable::parse($date));

        // A scored sheet on the tablet moves the kid on that sheet's skill.
        $this->assertSame('MATH.OPS.A.4a', $this->print('arthur', 1)[0]['skill']);
        $this->post('/modules/homework/kids/arthur/check/2026-11-25/t1', ['pin' => '4821', 'decision' => 'bien'])->assertOk();
        $this->assertSame(['en_cours', 2], $this->progress('arthur', 'MATH.OPS.A.4a'));

        // A second "Bien" in a row: level 3 (with carrying) for the next addition.
        $update->handle($kid, $skill, 2, 'bien', CarbonImmutable::parse('2026-11-30'));
        $this->assertSame(['en_cours', 3], $this->progress('arthur', 'MATH.OPS.A.4a'));
        $this->assertSame(['MATH.OPS.A.4a', 3, 'month'], [$pick('2026-12-01')['variant']->skill->code, $pick('2026-12-01')['level'], $pick('2026-12-01')['reason']]);
        $this->assertSame('2 chiffres + 2 chiffres, avec retenue', $pick('2026-12-01')['variant']->label_fr);

        // "Quelques erreurs" keeps the level; "À revoir" steps down, and twice in a row leaves a note for the parent.
        $update->handle($kid, $skill, 2, 'erreurs', CarbonImmutable::parse('2026-12-01'));
        $this->assertSame(['en_cours', 3], $this->progress('arthur', 'MATH.OPS.A.4a'));
        $update->handle($kid, $skill, 2, 'revoir', CarbonImmutable::parse('2026-12-02'));
        $this->assertSame(['a_revoir', 2], $this->progress('arthur', 'MATH.OPS.A.4a'));
        $this->assertSame('review', $pick('2026-12-03')['reason']);
        $update->handle($kid, $skill, 2, 'revoir', CarbonImmutable::parse('2026-12-03'));
        $this->assertSame(['a_revoir', 1], $this->progress('arthur', 'MATH.OPS.A.4a'));
        $this->get('/modules/homework/kids/arthur/sheet?screen=history')
            ->assertSee('For a parent: Arthur had to go over this twice in a row.')->assertSee('Addition et soustraction écrites, nombres inférieurs à 1000');

        // Stuck at level 1: the next addition practises its prerequisite (the number facts).
        $this->assertSame(['MATH.OPS.A.2c', 'prerequisite'], [$pick('2026-12-04')['variant']->skill->code, $pick('2026-12-04')['reason']]);

        // A "Bien" clears the note.
        $update->handle($kid, $skill, 2, 'bien', CarbonImmutable::parse('2026-12-04'));
        $this->get('/modules/homework/kids/arthur/sheet?screen=history')->assertDontSee('For a parent:');

        $this->post('/settings/unlock', ['pin' => '4821']);
        $this->get('/settings')->assertSee('Progress');
        $this->get('/modules/homework/progress')->assertOk()->assertSee('This month in 2 grade')->assertSee('Addition et soustraction écrites')->assertSee('Theo');
    }

    public function test_mastery_brings_reviews_after_7_then_21_days(): void
    {
        $kid = Kid::idFor('theo');
        $skill = (int) Skill::query()->where('code', 'MATH.OPS.A.7b')->value('id');
        KidSkillProgress::query()->where('kid_id', $kid)->where('skill_id', $skill)->update(['level' => 2, 'status' => 'en_cours', 'bien_streak' => 0]);
        $update = app(UpdateSkillProgress::class);
        $day = CarbonImmutable::parse('2026-11-25');

        $update->handle($kid, $skill, 5, 'bien', $day);
        $p = $update->handle($kid, $skill, 5, 'bien', $day); // level 2 is the top for 5e → mastered
        $this->assertSame(['maitrise', '2026-12-02'], [$p->status, $p->next_review_on->toDateString()]);

        // Due on 2 December: the multiplication sheet is that review.
        $pick = app(PickExerciseVariant::class)->handle($kid, 5, 'multiplication', CarbonImmutable::parse('2026-12-02'));
        $this->assertSame(['MATH.OPS.A.7b', 'review'], [$pick['variant']->skill->code, $pick['reason']]);

        $p = $update->handle($kid, $skill, 5, 'bien', CarbonImmutable::parse('2026-12-02'));
        $this->assertSame(['maitrise', '2026-12-23'], [$p->status, $p->next_review_on->toDateString()]);
        $p = $update->handle($kid, $skill, 5, 'bien', CarbonImmutable::parse('2026-12-23'));
        $this->assertNull($p->next_review_on); // upkeep from now on

        // In July only reviews and upkeep: no new skills.
        $july = app(PickExerciseVariant::class)->handle($kid, 5, 'multiplication', CarbonImmutable::parse('2027-07-07'));
        $this->assertSame('upkeep', $july['reason']);
    }

    public function test_builders_follow_level_ranges_and_carrying(): void
    {
        $sums = app(BuildAdditionSheet::class)->handle(2, 7, ['top' => [10, 99], 'bottom' => [10, 99], 'carry' => true]);
        $this->assertCount(25, $sums); // 2 + 2 digits: 5 × 5 sums fill the page
        $this->assertTrue(collect($sums)->every(fn (array $p): bool => BuildAdditionSheet::carries($p['top'], $p['bottom'])));
        $small = app(BuildAdditionSheet::class)->handle(2, 7, ['top' => [100, 499], 'bottom' => [100, 499], 'sum_max' => 999]);
        $this->assertTrue(collect($small)->every(fn (array $p): bool => $p['top'] + $p['bottom'] <= 999));
        $grid = app(BuildMultiplicationSheet::class)->handle(5, 3, ['top' => [2, 10], 'bottom' => [2, 5]]);
        $this->assertTrue(collect($grid)->every(fn (array $p): bool => $p['top'] <= 10 && $p['bottom'] <= 5));
        // Without a level, the grade's ranges as before (same numbers for the same seed).
        $this->assertSame(app(BuildAdditionSheet::class)->handle(5, 42), app(BuildAdditionSheet::class)->handle(5, 42, null));
    }

    public function test_addition_sheets_fill_the_page_with_room_to_write(): void
    {
        // Small sums → many problems; 4 digits or 3 numbers → fewer, bigger cells.
        $facts = BuildAdditionSheet::grid(3, 2);
        $twoDigits = BuildAdditionSheet::grid(4, 2);
        $fourDigits = BuildAdditionSheet::grid(6, 2);
        $threeNumbers = BuildAdditionSheet::grid(4, 3);
        $this->assertSame([[6, 5], [5, 5], [3, 5], [5, 4]], array_map(fn (array $g): array => [$g['columns'], $g['rows']], [$facts, $twoDigits, $fourDigits, $threeNumbers]));

        // Each digit gets 7 mm across and 9.5 mm down, and the sheet never runs past one page.
        foreach ([$facts, $twoDigits, $fourDigits, $threeNumbers] as $i => $g) {
            $this->assertEqualsWithDelta(7 * SheetPage::MM, $g['digit'], .01);
            $height = $g['carry'] + ([2, 2, 2, 3][$i] + 1) * $g['row'];
            $this->assertLessThanOrEqual(SheetPage::WIDTH, $g['columns'] * $g['chars'] * $g['digit'] + ($g['columns'] - 1) * $g['gapX']);
            $this->assertLessThanOrEqual(SheetPage::HEIGHT, $g['rows'] * $height + ($g['rows'] - 1) * $g['gapY']);
        }

        $sheet = app(BuildAdditionSheet::class)->handle(3, 5, ['top' => [10, 99], 'bottom' => [10, 99], 'addends' => 3]);
        $this->assertCount(20, $sheet);
        $this->assertCount(3, $sheet[0]['terms']);
        $this->assertSame((string) array_sum($sheet[0]['terms']), $sheet[0]['answer']);
        $this->assertSame('Additions de 3 nombres : 2 chiffres', BuildAdditionSheet::layout($sheet)['title']);

        // An old sheet with more sums than fit is drawn smaller instead of on two pages.
        $old = array_fill(0, 30, ['top' => 9999, 'bottom' => 9999]);
        $layout = BuildAdditionSheet::layout($old);
        $this->assertLessThan(7 * SheetPage::MM, $layout['digit']);
        $this->assertGreaterThanOrEqual(30, $layout['columns'] * BuildAdditionSheet::grid($layout['chars'], 2, $layout['digit'] / (7 * SheetPage::MM))['rows']);
    }

    public function test_multiplication_grids_have_a_row_per_step_and_fill_the_page(): void
    {
        // Tables (1 digit × 1 digit): carry, number, multiplier, result.
        $tables = BuildMultiplicationSheet::grid(2, 1, 3);
        $this->assertSame([4, 4, false, 4, 5], [$tables['columns'], $tables['height'], $tables['total'], $tables['across'], $tables['down']]);
        // 3 digits × 2 digits: 2 carry rows, number, multiplier, 2 partial products, a carry row for the sum, the total.
        $product = BuildMultiplicationSheet::grid(3, 2, 6);
        $this->assertSame([7, 8, true, 3, 3], [$product['columns'], $product['height'], $product['total'], $product['across'], $product['down']]);
        $this->assertEqualsWithDelta(8 * SheetPage::MM, $product['cell'], .01);

        foreach ([$tables, $product] as $g) {
            $this->assertLessThanOrEqual(SheetPage::WIDTH, $g['across'] * $g['columns'] * $g['cell'] + ($g['across'] - 1) * $g['gap']);
            $this->assertLessThanOrEqual(SheetPage::HEIGHT, $g['down'] * $g['height'] * $g['cell'] + ($g['down'] - 1) * $g['gap']);
        }

        $sheet = app(BuildMultiplicationSheet::class)->handle(5, 9, ['top' => [100, 999], 'bottom' => [10, 99]]);
        $this->assertCount(9, $sheet);
        $this->assertSame('Multiplications : 3 chiffres × 2 chiffres', BuildMultiplicationSheet::layout($sheet)['title']);
        $this->assertSame('Tables de multiplication', BuildMultiplicationSheet::layout([['top' => 10, 'bottom' => 3], ['top' => 7, 'bottom' => 2]])['title']);
        $this->assertSame('Multiplications : 2 chiffres × 1 chiffre', BuildMultiplicationSheet::layout([['top' => 34, 'bottom' => 7]])['title']);
    }

    public function test_writing_sheets_get_ai_words_for_the_working_grade_in_a_copy_table(): void
    {
        config(['services.openai.key' => 'test-key']);
        Kid::query()->where('slug', 'arthur')->update(['working_grade' => 3]);
        $rows = GenerateWritingWords::layout()['rows'];
        $this->assertSame(16, $rows); // 12 mm handwriting rows fill the page
        $words = ['école', 'Fenêtre', 'bébé', 'école', 'Lucie', 'deux mots', 'château', 'forêt'];
        Http::fake(['https://api.openai.com/v1/responses' => Http::response(['output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode(['words' => $words])]]]]])]);

        $this->signIn('arthur');
        $this->postJson('/modules/homework/kids/arthur/print', ['counts' => ['writing' => 1]])->assertOk();
        $sheet = app(HomeworkStore::class)->tasks('arthur', '2026-11-25')[0]['worksheet'];

        // Valid AI words first (lower case, no duplicates, no two-word answers), topped up from the built-in list.
        $this->assertCount($rows, $sheet);
        $this->assertSame(['école', 'fenêtre', 'bébé', 'lucie', 'château', 'forêt'], array_slice(array_column($sheet, 'question'), 0, 6));
        Http::assertSent(fn ($r): bool => json_decode($r['input'], true)['grade'] === 3 && json_decode($r['input'], true)['count'] === $rows);

        // The next sheet avoids these words; the printed sheet is a copy table.
        Http::fake(['https://api.openai.com/v1/responses' => Http::response([], 500)]);
        $this->postJson('/modules/homework/kids/arthur/print', ['counts' => ['writing' => 1]])->assertOk();
        $second = array_column(app(HomeworkStore::class)->tasks('arthur', '2026-11-25')[1]['worksheet'], 'question');
        $this->assertSame([], array_intersect($second, array_column($sheet, 'question')));

        $html = view('modules.homework.print', ['kid' => ['id' => 'arthur', 'name' => 'A', 'grade' => 2], 'date' => '2026-11-25', 'tasks' => [app(HomeworkStore::class)->tasks('arthur', '2026-11-25')[0]]])->render();
        $this->assertStringContainsString('<table class="copy"', $html);
        $this->assertSame($rows, substr_count($html, 'border-bottom: .5pt dashed #aaa') / 3);
    }

    public function test_a_parent_raises_the_working_grade_without_stepping_back(): void
    {
        $this->post('/settings/unlock', ['pin' => '4821']);
        $this->post('/modules/homework/progress/arthur/grade', ['grade' => 3])->assertRedirect('/modules/homework/progress');
        $arthur = Kid::query()->where('slug', 'arthur')->firstOrFail();
        $this->assertSame([2, 3], [$arthur->grade, $arthur->workGrade()]);

        // 2e skills that 3e builds on are known; 3e skills start at level 1 (2 when already started in 2e).
        $this->assertSame(['maitrise', 5], $this->progress('arthur', 'MATH.OPS.A.4a'));
        $this->assertSame(['maitrise', 3], $this->progress('arthur', 'MATH.OPS.A.2c'));
        $this->assertSame(['nouveau', 1], $this->progress('arthur', 'MATH.OPS.A.4b'));
        $this->assertSame(['en_cours', 2], $this->progress('arthur', 'FR.ACC.A.3'));

        // The sheets now follow 3e: column addition, and the tile says so.
        $pick = app(PickExerciseVariant::class)->handle($arthur->id, 3, 'addition', CarbonImmutable::parse('2026-11-25'));
        $this->assertSame(['MATH.OPS.A.4b', 1, 'month'], [$pick['variant']->skill->code, $pick['level'], $pick['reason']]);
        $this->signIn('arthur');
        $this->get('/modules/homework/kids/arthur/sheet?screen=homework')->assertSee('<small class="kz-choice-level">3 chiffres + 2 chiffres</small>', false);

        $this->get('/modules/homework/progress')->assertSee('School: 2 grade')->assertSee('<option value="3" selected>', false);
        $this->artisan('hub:working-grade', ['kid' => 'arthur', 'grade' => 2])->expectsOutputToContain('works at grade 2 (school grade 2)')->assertSuccessful();
        $this->assertNull($arthur->fresh()->working_grade);
    }

    public function test_parents_move_a_skill_by_hand(): void
    {
        $this->post('/settings/unlock', ['pin' => '4821']);
        $skill = Skill::query()->where('code', 'MATH.OPS.A.4a')->value('id');
        $this->post("/modules/homework/progress/arthur/skills/{$skill}", ['action' => 'up'])->assertRedirect();
        $this->assertSame(['en_cours', 3], $this->progress('arthur', 'MATH.OPS.A.4a'));
        $this->post("/modules/homework/progress/arthur/skills/{$skill}", ['action' => 'down'])->assertRedirect();
        $this->assertSame(['en_cours', 2], $this->progress('arthur', 'MATH.OPS.A.4a'));
        $this->post("/modules/homework/progress/arthur/skills/{$skill}", ['action' => 'master'])->assertRedirect();
        $this->assertSame(['maitrise', 5], $this->progress('arthur', 'MATH.OPS.A.4a'));
        $this->post("/modules/homework/progress/arthur/skills/{$skill}", ['action' => 'delete'])->assertSessionHasErrors('action');
        $this->get('/modules/homework/progress')->assertSee('aria-label="One level up"', false)->assertSee('>Mastered</button>', false);

        $this->post('/settings/lock');
        $this->post("/modules/homework/progress/arthur/skills/{$skill}", ['action' => 'up'])->assertRedirect();
        $this->assertSame(['maitrise', 5], $this->progress('arthur', 'MATH.OPS.A.4a')); // needs the settings PIN
    }

    public function test_skills_under_one_already_in_progress_are_upkeep_not_this_months_work(): void
    {
        // Theo works on 3 digits × 2 digits at level 2: the tables (two steps under it) are known.
        $theo = Kid::idFor('theo');
        KidSkillProgress::query()->where('kid_id', $theo)->where('skill_id', Skill::query()->where('code', 'MATH.OPS.A.7b')->value('id'))->update(['level' => 2, 'status' => 'en_cours']);
        KidSkillProgress::query()->where('kid_id', $theo)->where('skill_id', Skill::query()->where('code', 'MATH.OPS.A.6')->value('id'))->update(['level' => 1, 'status' => 'en_cours']);

        $pick = app(PickExerciseVariant::class)->handle($theo, 5, 'multiplication', CarbonImmutable::parse('2026-11-25'));
        $this->assertSame(['MATH.OPS.A.7b', 2, 'month'], [$pick['variant']->skill->code, $pick['level'], $pick['reason']]);
    }

    public function test_ai_division_items_are_checked_against_the_level(): void
    {
        $check = ['divisors' => [2, 5, 10], 'quotient' => [1, 10], 'remainder' => false];
        $items = app(CheckDivisionItems::class)->handle([
            ['question' => 'Calcule : 40 ÷ 5', 'answer' => '8'],
            ['question' => 'Calcule : 56 ÷ 8', 'answer' => '7'],          // divisor outside ÷ 2, 5, 10
            ['question' => 'Combien font 30 : 10 ?', 'answer' => '4'],    // wrong answer
            ['question' => 'Vérifie que c’est une division des tables.', 'answer' => 'oui'], // no calculation
        ], $check, 3);

        $this->assertSame(['question' => 'Calcule : 40 ÷ 5', 'answer' => '8'], $items[0]);
        $this->assertSame('3', $items[2]['answer']);
        foreach ([$items[1], $items[3]] as $made) {
            $this->assertMatchesRegularExpression('/^Calcule : (\d+) ÷ (2|5|10)$/', $made['question']);
            preg_match('/(\d+) ÷ (\d+)/', $made['question'], $m);
            $this->assertSame((string) intdiv((int) $m[1], (int) $m[2]), $made['answer']);
            $this->assertSame(0, (int) $m[1] % (int) $m[2]);
        }
    }

    public function test_print_text_keeps_arrows_and_symbols_printable(): void
    {
        $this->assertSame('un livre <span style="font-family: \'DejaVu Sans\'">→</span> des livres &amp; ça', PdfText::html('un livre → des livres & ça'));
        $this->assertSame('Écris l’article « le »', PdfText::html('Écris l’article « le »'));
    }

    /** @return array{string, int} */
    private function progress(string $kid, string $code): array
    {
        $p = KidSkillProgress::query()->where('kid_id', Kid::idFor($kid))->where('skill_id', Skill::query()->where('code', $code)->value('id'))->firstOrFail();

        return [$p->status, $p->level];
    }

    /** @return list<array<string, mixed>> the day's tasks after printing this many additions */
    private function print(string $kid, int $additions): array
    {
        Http::preventStrayRequests();
        if ($additions > 0) {
            $this->postJson("/modules/homework/kids/{$kid}/print", ['counts' => ['addition' => $additions]])->assertOk();
        }

        return app(HomeworkStore::class)->tasks($kid, '2026-11-25');
    }

    private function signIn(string $kid): void
    {
        $this->post("/modules/homework/kids/{$kid}/unlock", ['pin' => '4821'])->assertOk();
    }
}
