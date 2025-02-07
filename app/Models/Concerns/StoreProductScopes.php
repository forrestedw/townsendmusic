<?php

namespace App\Models\Concerns;

use App\Exceptions\StoreProductSortNotRecognised;
use App\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

trait StoreProductScopes
{
    protected $imagesDomain = "https://img.tmstor.es/";

    protected function scopeInSection(Builder $query, $section = null)
    {
        // for use in scopeSortBy()
        $this->querySection = $section;

        if ($this->sectionIsAll($section)) {
            return $query;
        }

        // default to id search
        $column = 'sections.id';
        $operator = '=';

        // if we passed a Section model through, get the id to search
        if ($this->sectionIsSectionClass($section)) {
            $section = $section->id;
        }

        // if not numeric, then we go off the desciption, eg t-shirt
        if (!$this->sectionIsNumeric($section)) {
            $column = 'sections.description';
            $operator = 'like';
        }

        return $query->whereHas('sections', fn (Builder $query) =>
            $query->where($column, $operator, $section)
        );
    }

    protected function scopeInStore(Builder $query, int $storeId)
    {
        return $query->whereStoreId($storeId);
    }

    protected function scopeSortBy(Builder $query, string $sort)
    {
        if (!$this->sortIsPosition($sort)) {
            return $query->orderBy($this->getSortAttribute($sort), $this->getSortDirection($sort));
        }

        return $query->orderByPosition($this->querySection)->orderByDesc('release_date');
    }

    protected function scopeAvailableProductsOnly(Builder $query)
    {
        return $query
            ->isNotInDisabledCountry()
            ->isAfterLaunchDate()
            ->isBeforeRemoveDate()

            // I noticed that some products don't have any data to
            // get a title from (no name, no display_name).
            // This next time removes those from the results.
            // Not sure if this is what you want, but thought
            // I'd suggest it!
            ->canBeGivenANonBlankTitle()

            ->whereAvailable(1);
    }

    protected function scopeIsNotInDisabledCountry(Builder $query)
    {
        return $query->where('disabled_countries', 'not like', "%{$this->getGeocode()['country']}%");
    }

    protected function scopeIsBeforeRemoveDate(Builder $query)
    {
        // nesting required to constrain orWhere to just vis a vis date.
        return $query->where(function (Builder $query) {
            $query->whereDate('remove_date', '>', Carbon::today()->toDateString())
                ->orWhere('remove_date', '=', '0000-00-00 00:00:00');
        });
    }

    protected function scopeIsAfterLaunchDate(Builder $query)
    {
        // show pre-launch products in preview mode
        if (session()->has('preview_mode')) {
            return $query;
        }

        // nesting required to constrain orWhere to just vis a vis date.
        return $query->where(function (Builder $query) {
            $query->whereDate('launch_date', '<', Carbon::today()->toDateString())
                ->orWhere('launch_date', '=', '0000-00-00 00:00:00');
        });
    }

    protected function scopeCanBeGivenANonBlankTitle(Builder $query)
    {
        // The title attribute is derived from these two name attributes.
        // If they are both blank, then the title is blank, which wouldn't look good?
        return $query->where(function(Builder $query) {
            $query->where('name', '!=', '')
                  ->orWhere('display_name', '!=', '');
        });
    }


    /**
     * @param mixed $sort
     * @return string
     */
    protected function getSortDirection(mixed $sort): string
    {
        switch ($sort) {
            case 'za': case 'high': case 'new': return 'desc';
        }
        return 'asc';
    }

    protected function getSortAttribute(mixed $sort): string
    {
        switch ($sort) {
            case 'az': case 'za': return 'name';
            case 'low': case 'high': return 'price';
            case 'old': case 'new': return 'release_date';
        }

        throw new StoreProductSortNotRecognised("{$sort} is not recognised as sort");
    }

    protected function sectionIsAll($section): bool
    {
        return isset($section) && Str::lower($section) === 'all';
    }

    protected function sectionIsPercent($section): bool
    {
        return isset($section) && $section === '%';
    }

    protected function sectionIsSectionClass($section): bool
    {
        return $section instanceof Section;
    }

    protected function sectionIsNumeric($section): bool
    {
        return is_numeric($section);
    }

    protected function sortIsPosition($sort): bool
    {
        return Str::lower($sort) === 'position';
    }

    protected function scopeOrderByPosition(Builder $query, $section)
    {
        if ($this->sectionIsAll($section) || $this->sectionIsPercent($section)) {
            return $query->orderBy('store_products.position');
        }

        return $query->whereHas('sections', function (Builder $query) {
            $query->orderBy('store_products_section.position');
        });
    }

    protected function getGeocode()
    {
        //Return GB default for the purpose of the test
        return ['country' => 'GB'];
    }
}
