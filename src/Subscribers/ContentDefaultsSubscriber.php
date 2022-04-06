<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Concerns\GetsEventData;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Events\Dispatcher;
use Statamic\Events;
use Statamic\Events\Event;

class ContentDefaultsSubscriber
{
    use GetsEventData;

    public function subscribe(Dispatcher $events): array
    {
        return [
            Events\CollectionSaved::class => 'createOrDeleteLocalizations',
            Events\TaxonomySaved::class => 'createOrDeleteLocalizations',
            Events\CollectionDeleted::class => 'deleteDefaults',
            Events\TaxonomyDeleted::class => 'deleteDefaults',
        ];
    }

    /**
     * Create or delete a localization when the corresponding site
     * was added or removed from a Collection or Taxonomy.
     */
    public function createOrDeleteLocalizations(Event $event): void
    {
        $property = $this->getProperty($event);
        $type = $this->determineRepositoryType($event);

        $handle = $property->handle();
        $sites = $property->sites();

        Seo::findOrMake($type, $handle)->createOrDeleteLocalizations($sites);
    }

    /**
     * Deletes a whole Seo Defaults Set when a Collection or Taxonomy is deleted.
     */
    public function deleteDefaults(Event $event): void
    {
        $property = $this->getProperty($event);
        $type = $this->determineRepositoryType($event);

        Seo::find($type, $property->handle())?->delete();
    }
}
