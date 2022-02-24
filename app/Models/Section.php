<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\Section
 *
 * @property int $id
 * @property int $store_id
 * @property string $description
 * @property int $order
 * @property int $parent
 * @property int $ppr_override
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\StoreProduct[] $products
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder|Section newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Section newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Section query()
 * @method static \Illuminate\Database\Eloquent\Builder|Section whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Section whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Section whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Section whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Section whereParent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Section wherePprOverride($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Section whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Section whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Section extends Model
{
    use HasFactory;

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            StoreProduct::class,
            'store_products_section',
            'section_id',
            'store_product_id',
            'id',
            'id'
        )->withPivot('position')
        ->orderBy('position', 'ASC');
    }
}
