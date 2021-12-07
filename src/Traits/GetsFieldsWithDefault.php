<?php

namespace Aerni\AdvancedSeo\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\Fields\Blueprint;

trait GetsFieldsWithDefault
{
    use GetsContentDefaults;

    /**
     * Use the $localized argument to return the localized blueprint values.
     */
    public function getFieldsWithDefault(Blueprint $blueprint, $localized = false, string $locale = null): Collection
    {
        // Get the fields that have a default value set.
        $fieldsWithDefaults = $blueprint->fields()->resolveFields()->mapWithKeys(function ($field) {
            return [$field->handle() => Arr::get($field->config(), 'default')];
        })->filter(function ($value) {
            return $value !== null;
        });

        if (! $localized) {
            return $fieldsWithDefaults;
        }

        // Get the content defaults for the entry linked to the blueprint.
        $contentDefaults = collect($this->getContentDefaults($blueprint->getParent()))->map->raw();

        // Return the defaults for the current entry.
        return $contentDefaults->intersectByKeys($fieldsWithDefaults);
    }
}
