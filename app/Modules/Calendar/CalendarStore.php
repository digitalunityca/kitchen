<?php

declare(strict_types=1);

namespace App\Modules\Calendar;

use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * The connected calendars, kept in storage/app/private/calendars.json (never in
 * git). Each has an id, name, colour, optional icon (an emoji shown before its events), iCal feed URL and a visible flag. The
 * URLs are secret links, so they are never logged and are masked in the UI.
 */
class CalendarStore
{
    private const FILE = 'calendars.json';

    /** Icons offered on the settings page (any emoji can be typed too). */
    public const ICONS = ['⚽', '🏒', '🎵', '🏫', '💼', '🩺', '🎂'];

    /** Reminder choices in minutes before an event (null = none). */
    public const REMINDERS = [10 => '10 minutes', 15 => '15 minutes', 30 => '30 minutes', 60 => '1 hour', 1440 => '1 day'];

    public const COLORS = ['#0071E3', '#34C759', '#FF9500', '#AF52DE', '#FF3B30', '#5AC8FA', '#FF2D55', '#8E8E93'];

    public function __construct(private readonly Filesystem $disk) {}

    /** @return list<array{id: string, name: string, color: string, url: string, visible: bool}> */
    public function all(): array
    {
        if (! $this->disk->exists(self::FILE)) {
            return [];
        }

        $calendars = json_decode((string) $this->disk->get(self::FILE), true);

        return is_array($calendars) ? array_values($calendars) : [];
    }

    /** @return array{id: string, name: string, color: string, url: string, visible: bool}|null */
    public function find(string $id): ?array
    {
        return collect($this->all())->firstWhere('id', $id);
    }

    /** @param  array<int, array{id: string, name: string, color: string, url: string, visible: bool}>  $calendars */
    public function save(array $calendars): void
    {
        $this->disk->put(self::FILE, (string) json_encode(array_values($calendars), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /** "calendar.google.com/…" — just the host, never the secret part of the link. */
    public static function mask(string $url): string
    {
        return parse_url($url, PHP_URL_HOST).'/…';
    }
}
