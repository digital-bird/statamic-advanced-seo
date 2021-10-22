<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImageJob;
use Aerni\AdvancedSeo\Repositories\CollectionDefaultsRepository;
use Aerni\AdvancedSeo\Repositories\TaxonomyDefaultsRepository;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use Statamic\Events;
use Statamic\Events\Event;

class OnPageSeoBlueprintSubscriber
{
    protected array $events = [
        Events\EntryBlueprintFound::class => 'addFieldsToBlueprint',
        Events\EntrySaving::class => 'removeDefaultDataFromEntry',
        Events\TermBlueprintFound::class => 'addFieldsToBlueprint',
        Events\CollectionSaved::class => 'createOrDeleteLocalizations',
        Events\TaxonomySaved::class => 'createOrDeleteLocalizations',
        Events\CollectionDeleted::class => 'deleteDefaults',
        Events\TaxonomyDeleted::class => 'deleteDefaults',
        Events\EntrySaved::class => 'generateSocialImage',
    ];

    public function subscribe(Dispatcher $events): void
    {
        foreach ($this->events as $event => $method) {
            $events->listen($event, [self::class, $method]);
        }
    }

    public function addFieldsToBlueprint(Event $event): void
    {
        if (Str::contains(request()->path(), '/blueprints/' . $event->blueprint->handle()) || app()->runningInConsole()) {
            return;
        }

        $event->blueprint->ensureFieldsInSection(OnPageSeoBlueprint::make()->items(), 'SEO');

        $this->addDefaultDataToEntry($event);
    }

    /**
     * TODO: This has to also work with Terms.
     * Makes sure that we only save data that is different to the default data.
     * This ensures that the blueprint always loads the latest default data if no other value has been set on the entry.
     */
    public function removeDefaultDataFromEntry(Event $event): void
    {
        if ($event->entry) {
            $collection = $event->entry->collection();
            $defaults = (new CollectionDefaultsRepository($collection->handle(), $collection->sites()))->set()->in($event->entry->locale())->data();

            $diffed = $event->entry->data()->filter(function ($value, $key) use ($defaults) {
                return $value !== $defaults->get($key);
            });

            $event->entry->data($diffed);
        }
    }

    /**
     * TODO: This has to also work with Terms.
     * Adds the content defaults to the entry.
     * It only adds values if they have not already been set on the entry.
     */
    protected function addDefaultDataToEntry(Event $event): void
    {
        if ($event->entry) {
            $collection = $event->entry->collection();
            $defaults = (new CollectionDefaultsRepository($collection->handle(), $collection->sites()))->set()->in($event->entry->locale())->data();

            // We only want to set the defaults that were not changed on the entry.
            $entryData = $event->entry->data();
            $defaultsToSet = $defaults->diffKeys($entryData);
            $newValues = $entryData->merge($defaultsToSet)->toArray();

            $event->entry->data($newValues);
        }
    }

    /**
     * Create or delete a localization when the corresponding site
     * was added or removed from a Collection or Taxonomy.
     */
    public function createOrDeleteLocalizations(Event $event): void
    {
        $property = $this->determineProperty($event);
        $repository = $this->determineRepository($event);

        $handle = $property->handle();
        $sites = $property->sites();

        (new $repository($handle, $sites))->createOrDeleteLocalizations($sites);
    }

    public function deleteDefaults(Event $event): void
    {
        $repository = $this->determineRepository($event);

        $repository->delete();
    }

    public function generateSocialImage(Event $event): void
    {
        GenerateSocialImageJob::dispatch($event->entry);
    }

    protected function determineRepository(Event $event): mixed
    {
        return property_exists($event, 'taxonomy')
            ? TaxonomyDefaultsRepository::class
            : CollectionDefaultsRepository::class;
    }

    protected function determineProperty(Event $event): mixed
    {
        return property_exists($event, 'taxonomy')
            ? $event->taxonomy
            : $event->collection;
    }
}
