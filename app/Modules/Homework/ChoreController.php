<?php

declare(strict_types=1);

namespace App\Modules\Homework;

use App\Http\Controllers\Controller;
use App\Models\Chore;
use App\Models\Kid;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Settings › Aider à la maison: the chores, their points, how many times a day
 * they count, on which days and for which kid. Chores are turned off, never
 * deleted, so History keeps its names.
 */
class ChoreController extends Controller
{
    public function index(): View
    {
        return view('modules.homework.chores', [
            'chores' => Chore::query()->with('kids:id')->orderBy('position')->orderBy('id')->get(),
            'kids' => Kid::query()->orderBy('position')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $chore = Chore::query()->create($this->attributes($data) + [
            'slug' => $this->uniqueSlug($data['name']),
            'position' => (int) Chore::query()->max('position') + 1,
        ]);
        $chore->kids()->sync($data['kids'] ?? []);

        return redirect()->route('modules.homework.chores')->with('status', __('Chore added.'));
    }

    public function update(Request $request, int $chore): RedirectResponse
    {
        $model = Chore::query()->findOrFail($chore);
        $data = $this->validated($request);
        $model->update($this->attributes($data));
        $model->kids()->sync($data['kids'] ?? []);

        return redirect()->route('modules.homework.chores')->with('status', __('Saved.'));
    }

    /** @return array{name: string, emoji: string, points: int, per_day: int, days: string, active?: bool, kids?: list<int>} */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'emoji' => ['required', 'string', 'max:16'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
            'per_day' => ['required', 'integer', 'min:1', 'max:5'],
            'days' => ['required', Rule::in(Chore::DAYS)],
            'active' => ['sometimes', 'boolean'],
            'kids' => ['sometimes', 'array'],
            'kids.*' => ['integer', Rule::exists('kids', 'id')],
        ]);
    }

    /** @param  array<string, mixed>  $data */
    private function attributes(array $data): array
    {
        return [
            'name_fr' => trim((string) $data['name']), 'emoji' => trim((string) $data['emoji']), 'points' => (int) $data['points'],
            'per_day' => (int) $data['per_day'], 'days' => (string) $data['days'], 'active' => (bool) ($data['active'] ?? false),
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'tache';
        $slug = $base;
        for ($i = 2; Chore::query()->where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
