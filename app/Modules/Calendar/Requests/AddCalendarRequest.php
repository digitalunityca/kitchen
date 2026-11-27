<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Requests;

use App\Modules\Calendar\CalendarStore;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddCalendarRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:40'],
            'icon' => ['nullable', 'string', 'max:8'],
            'reminder' => ['nullable', Rule::in(array_keys(CalendarStore::REMINDERS))],
            'speak' => ['sometimes', 'boolean'],
            'color' => ['required', Rule::in(CalendarStore::COLORS)],
            'url' => ['required', 'string', 'max:2000', 'regex:#^(https?|webcals?)://#i'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['url.regex' => __('Paste the full iCal link, starting with https:// or webcal://.')];
    }
}
