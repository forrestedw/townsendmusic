<?php

namespace App\Models;

use App\Exceptions\CurrencyNotRecognisedException;
use App\Exceptions\StoreProductSortNotRecognised;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * App\Models\StoreProduct
 *
 * @property int $id
 * @property int $store_id
 * @property int $artist_id
 * @property string $type
 * @property string|null $launch_date
 * @property string|null $remove_date
 * @property string|null $release_date
 * @property string $description
 * @property int $available
 * @property string $price
 * @property string $euro_price
 * @property string $dollar_price
 * @property string $image_format
 * @property int $deleted
 * @property string $disabled_countries
 * @property string $display_name
 * @property string $name
 * @property int|null $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Artist|null $artist
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Section[] $sections
 * @property-read int|null $sections_count
 * @method static Builder|StoreProduct inSection($section)
 * @method static Builder|StoreProduct inStore(int $storeId)
 * @method static Builder|StoreProduct newModelQuery()
 * @method static Builder|StoreProduct newQuery()
 * @method static Builder|StoreProduct query()
 * @method static Builder|StoreProduct sorting($sort = 0)
 * @method static Builder|StoreProduct whereArtistId($value)
 * @method static Builder|StoreProduct whereAvailable($value)
 * @method static Builder|StoreProduct whereCreatedAt($value)
 * @method static Builder|StoreProduct whereDeleted($value)
 * @method static Builder|StoreProduct whereDescription($value)
 * @method static Builder|StoreProduct whereDisabledCountries($value)
 * @method static Builder|StoreProduct whereDisplayName($value)
 * @method static Builder|StoreProduct whereDollarPrice($value)
 * @method static Builder|StoreProduct whereEuroPrice($value)
 * @method static Builder|StoreProduct whereId($value)
 * @method static Builder|StoreProduct whereImageFormat($value)
 * @method static Builder|StoreProduct whereLaunchDate($value)
 * @method static Builder|StoreProduct whereName($value)
 * @method static Builder|StoreProduct wherePosition($value)
 * @method static Builder|StoreProduct wherePrice($value)
 * @method static Builder|StoreProduct whereReleaseDate($value)
 * @method static Builder|StoreProduct whereRemoveDate($value)
 * @method static Builder|StoreProduct whereStoreId($value)
 * @method static Builder|StoreProduct whereType($value)
 * @method static Builder|StoreProduct whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class StoreProduct extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $table = 'store_products';

    protected $imagesDomain = "https://img.tmstor.es/";

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(
            Section::class,
            'store_products_section',
            'store_product_id',
            'section_id',
            'id',
            'id'
        )
            ->withPivot('position')
            ->orderBy('position', 'ASC');
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class, 'artist_id', 'id');
    }

    public function scopeInSection(Builder $query, $section = null)
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

    public function scopeInStore(Builder $query, int $storeId)
    {
        return $query->whereStoreId($storeId);
    }

    public function scopeSortBy(Builder $query, string $sort)
    {
        if (!$this->sortIsPosition($sort)) {
            $query->orderBy($this->getSortAttribute($sort), $this->getSortDirection($sort));
        }

         $this->orderByPosition($query, $this->querySection)->orderByDesc('release_date');
    }

    public function scopeAvailableProductsOnly(Builder $query)
    {
        $query->isNotInDisabledCountry()
              ->isAfterLaunchDate()
              ->isBeforeRemoveDate()
              ->whereAvailable(1);
    }

    public function scopeIsNotInDisabledCountry(Builder $query)
    {
        $query->where('disabled_countries', 'not like', "%{$this->getGeocode()['country']}%");
    }

    public function scopeIsBeforeRemoveDate(Builder $query)
    {
        // nesting required to constrain orWhere to just vis a vis date.
        $query->where(function (Builder $query) {
            $query->whereDate('remove_date', '>', Carbon::today()->toDateString())
                ->orWhere('remove_date', '=', '0000-00-00 00:00:00');
        });
    }

    public function scopeIsAfterLaunchDate(Builder $query)
    {
        // show pre-launch products in preview mode
        if (session()->has('preview_mode')) {
            return;
        }

        // nesting required to constrain orWhere to just vis a vis date.
        $query->where(function (Builder $query) {
            $query->whereDate('launch_date', '<', Carbon::today()->toDateString())
                ->orWhere('launch_date', '=', '0000-00-00 00:00:00');
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

    protected function orderByPosition(Builder $query, $section)
    {
        if ($this->sectionIsAll($section) || $this->sectionIsPercent($section)) {
            return $query->orderBy('store_products.position');
        }

        return $query->whereHas('sections', function (Builder $query) {
            $query->orderBy('store_products_section.position');
        });
    }

    public function getImageUrlAttribute(): string
    {
        return $this->imagesDomain . (
            strlen($this->image_format) > 2
                ? sprintf('%s.%s', $this->id, $this->image_format)
                : 'noimage.jpg'
            );
    }

    public function getTitleAttribute(): string
    {
        return strlen($this->display_name) > 3 ? $this->display_name : $this->name;
    }

    public function getPriceAttribute()
    {
        $currency = session()->has('currency')
            ? strtolower(session()->get('currency'))
            : 'gbp';

        $this->attributes['currency'] = $currency;

        switch ($currency) {
            case 'gbp':
                $price = $this->attributes['price'];
                break;
            case 'usd':
                $price = $this->dollar_price;
                break;
            case 'eur':
                $price = $this->euro_price;
                break;
            default:
                throw new CurrencyNotRecognisedException("{$currency} is not a valid currency");
        }

        return $price;
    }

    public function getGeocode()
    {
        //Return GB default for the purpose of the test
        return ['country' => 'GB'];
    }
}
