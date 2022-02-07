<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blink;
use Statamic\Fields\Value;

trait GetsSiteDefaults
{
    use GetsLocale;

    /**
     * Get the augmented site defaults and filter out any values that shouldn't be there,
     * like features that were disabled in the config.
     */
    public function getSiteDefaults(Entry|Term|Collection $data = null): Collection
    {
        $locale = $this->getLocale($data);

        return Blink::once($this->getSiteCacheKey($locale), function () use ($locale) {
            return Seo::allOfType('site')->flatMap(function ($defaults) use ($locale) {
                return $defaults->in($locale)?->toAugmentedArray();
            })->filter(function ($item) {
                // Only return values that have a corresponding field in the blueprint.
                return $item instanceof Value && $item->raw() !== null;
            });
        });
    }

    protected function getSiteCacheKey(string $locale): string
    {
        return "advanced-seo::site::all::$locale";
    }
}