<?php

namespace App\Support;

class Utf8
{
    /**
     * Replace any byte sequence that isn't valid UTF-8 with the mbstring
     * substitute character. Legacy/pasted-in text (old Windows-1252
     * imports, copy-pasted Word content, etc.) sometimes carries invalid
     * bytes; left as-is they make json_encode() fail wherever that value
     * is later serialized — a Livewire response, a JSON-cast Eloquent
     * attribute (see Auditable) — turning an otherwise harmless field into
     * a 500 for the whole request.
     */
    public static function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_check_encoding($value, 'UTF-8')
            ? $value
            : mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    /**
     * Apply clean() to every string value in an array (recursively), for
     * sanitizing a whole set of model attributes at once.
     */
    public static function cleanArray(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_string($value)) {
                $values[$key] = static::clean($value);
            } elseif (is_array($value)) {
                $values[$key] = static::cleanArray($value);
            }
        }

        return $values;
    }
}
