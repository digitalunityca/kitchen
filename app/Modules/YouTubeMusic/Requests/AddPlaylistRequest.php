<?php

declare(strict_types=1);

namespace App\Modules\YouTubeMusic\Requests;

use App\Actions\YouTubeMusic\ParsePlaylistLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AddPlaylistRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'link' => ['required', 'string', 'max:500'],
            'name' => ['nullable', 'string', 'max:60'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isEmpty() && $this->playlistId() === null) {
                    $validator->errors()->add('link', 'That link has no playlist in it. Open the playlist, tap Share, Copy link, and paste that.');
                }
            },
        ];
    }

    public function playlistId(): ?string
    {
        return app(ParsePlaylistLink::class)->handle((string) $this->input('link', ''));
    }
}
