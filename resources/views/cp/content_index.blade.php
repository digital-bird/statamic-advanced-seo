@extends('statamic::layout')
@section('title', __('advanced-seo::messages.content'))

@section('content')

    <div class="mb-3">
        <h1>{{ __('advanced-seo::messages.content') }}</h1>
    </div>

    <div class="flex flex-wrap -mx-2 widgets">
        @can('view collection defaults')
            @if (Statamic\Facades\Collection::all()->count() > 0)
                <div class="w-full px-2 mb-4 lg:w-1/2 widget">
                    <div class="p-0 card content">
                        <div class="flex items-start p-3 border-b">
                            <div class="w-8 h-8 mr-3 text-blue">
                                @cp_svg('content-writing')
                            </div>
                            <div class="flex-1 mt-sm">
                                <h2 class="mb-0">{{ __('Collections') }}</h2>
                            </div>
                        </div>
                        <div class="p-1.5">
                            @foreach (Statamic\Facades\Collection::all() as $collection)
                                <a href="{{ cp_route('advanced-seo.content.collections.edit', $collection) }}" class="block px-1.5 py-1 text-sm rounded-md hover:bg-blue-100">{{ $collection->title() }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endcan

        @can('view taxonomy defaults')
            @if (Statamic\Facades\Taxonomy::all()->count() > 0)
                <div class="w-full px-2 mb-4 lg:w-1/2 widget">
                    <div class="p-0 card content">
                        <div class="flex items-start p-3 border-b">
                            <div class="w-8 h-8 mr-3 text-blue">
                                @cp_svg('tags')
                            </div>
                            <div class="flex-1 mt-sm">
                                <h2 class="mb-0">{{ __('Taxonomies') }}</h2>
                            </div>
                        </div>
                        <div class="p-1.5">
                            @foreach (Statamic\Facades\Taxonomy::all() as $taxonomy)
                                <a href="{{ cp_route('advanced-seo.content.taxonomies.edit', $taxonomy) }}" class="block px-1.5 py-1 text-sm rounded-md hover:bg-blue-100">{{ $taxonomy->title() }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endcan
    </div>

    @include('advanced-seo::cp/_docs_callout')

@stop