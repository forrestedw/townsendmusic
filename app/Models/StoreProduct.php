<?php

namespace App\Models;

use App\Models\Concerns\StoreProductScopes;
use App\Exceptions\CurrencyNotRecognisedException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use StoreProductScopes;

    public $table = 'store_products';

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
}
