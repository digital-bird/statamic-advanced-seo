<?php

namespace Aerni\AdvancedSeo\Concerns;

use Statamic\Events\Event;
use Statamic\Fields\Blueprint;

trait GetsEventData
{
    protected function determineRepositoryType(Event $event): string
    {
        return property_exists($event, 'taxonomy')
            ? 'taxonomies'
            : 'collections';
    }

    protected function determineProperty(Event $event): string
    {
        return collect(['collection', 'entry', 'taxonomy', 'term', 'defaults'])
            ->first(function ($property) use ($event) {
                return property_exists($event, $property);
            });
    }

    protected function getProperty(Event $event): mixed
    {
        $property = $this->determineProperty($event);

        return $event->$property;
    }

    protected function getBlueprintFromEvent(Event $event): Blueprint
    {
        return property_exists($event, 'blueprint')
            ? $event->blueprint
            : $this->getProperty($event)->blueprint();
    }
}