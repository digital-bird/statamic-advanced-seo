<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Contracts\Entries\Entry;

class ShouldGenerateSocialImages
{
    public static function handle(Entry $entry): bool
    {
        // Shouldn't generate if the generator was disabled in the config.
        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return false;
        }

        // Shouldn't generate if it was configured to generate on demand.
        if (! config('advanced-seo.social_images.generator.generate_on_save', true)) {
            return false;
        }

        // Get the collections that are allowed to generate social images.
        $enabledCollections = Seo::find('site', 'social_media')
            ?->in($entry->site()->handle())
            ?->value('social_images_generator_collections') ?? [];

        // Shouldn't generate if the entry's collection is not selected.
        if (! in_array($entry->collectionHandle(), $enabledCollections)) {
            return false;
        }

        return $entry->seo_generate_social_images;
    }
}
