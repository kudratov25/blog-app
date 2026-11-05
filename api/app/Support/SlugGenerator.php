<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugGenerator
{
    /**
     * Generate a unique slug for a given model.
     *
     * @param string $value      The string to slugify
     * @param string $modelClass Fully-qualified model class name
     * @param string $column     The slug column name (default: 'slug')
     * @param int|null $ignoreId  When updating, ignore this record's ID
     */
    public static function generate(
        string $value,
        string $modelClass,
        string $column = 'slug',
        ?int $ignoreId = null
    ): string {
        $base = Str::slug($value);
        $slug = $base;
        $count = 1;

        /** @var Model $modelClass */
        while (true) {
            $query = $modelClass::where($column, $slug);

            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }

            if (!$query->exists()) {
                break;
            }

            $count++;
            $slug = "{$base}-{$count}";
        }

        return $slug;
    }
}
