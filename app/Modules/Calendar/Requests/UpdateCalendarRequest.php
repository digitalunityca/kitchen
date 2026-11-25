<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Requests;

use App\Modules\Calendar\CalendarStore;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCalendarRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:40'],
            'icon' => ['nullable', 'string', 'max:8'],
            'color' => ['sometimes', Rule::in(CalendarStore::COLORS)],
            'visible' => ['sometimes', 'boolean'],
        ];
    }
}
