<?php

namespace App\Support;

use Illuminate\Support\Str;

final class PersonName
{
    public const FIELDS = ['first_name', 'middle_name', 'last_name', 'name_extension'];

    public const EXTENSIONS = ['Jr.', 'Sr.', 'II', 'III', 'IV', 'V', 'VI'];

    public static function capitalize(?string $value): string
    {
        $value = Str::squish((string) $value);

        return preg_replace_callback(
            "/(^|[\s'\x{2019}-])\p{L}/u",
            fn (array $match) => mb_strtoupper($match[0], 'UTF-8'),
            mb_strtolower($value, 'UTF-8')
        );
    }

    public static function extension(?string $value): string
    {
        $value = strtoupper(trim((string) $value, " \t\n\r\0\x0B."));

        return match ($value) {
            'JR' => 'Jr.',
            'SR' => 'Sr.',
            default => $value,
        };
    }

    public static function full(?string $value): string
    {
        $parts = explode(' ', Str::squish((string) $value));
        $suffix = self::extension(end($parts));
        if (in_array($suffix, self::EXTENSIONS, true)) {
            array_pop($parts);

            return trim(self::capitalize(implode(' ', $parts)).' '.$suffix);
        }

        return self::capitalize($value);
    }

    public static function join(array $parts): string
    {
        return implode(' ', array_filter(
            array_map(fn (string $field) => $parts[$field] ?? null, self::FIELDS),
            fn ($value) => $value !== null && $value !== ''
        ));
    }
}
