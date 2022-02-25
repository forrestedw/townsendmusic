<?php

namespace App\Services;

use App\Models\StoreProduct;
use Illuminate\Pagination\LengthAwarePaginator;

class StoreProductToJsonService
{
    public function products(LengthAwarePaginator $products)
    {
        return $this->toJson($products);
    }

    protected function toJson($products)
    {
        return $this->getProtectedItemsCollectionFromPaginator($products)
            ->each(function(StoreProduct $product) {
                $this->setComputedValuesSoNativeToJsonCanParseThem($product);
                $this->tidyCurrenciesToReduceAmbiguity($product);
            })->toJson(JSON_PRETTY_PRINT);
    }

    /**
     * We're using the paginator to ease, but the Collection of
     * StoreProducts is protected. This allows us to get the
     * Collection off the paginator, so we can return it as json.
     *
     * @param LengthAwarePaginator $paginator
     * @return mixed
     */
    protected function getProtectedItemsCollectionFromPaginator(LengthAwarePaginator $paginator): mixed
    {
        $reflection = new \ReflectionClass($paginator);
        $property = $reflection->getProperty('items');
        $property->setAccessible(true);
        return $property->getValue($paginator);
    }

    /**
     * we can unset these, so that the computed price
     * doesn't get confused. A currency attribute has been
     * added for clarity
     *
     * @param StoreProduct $product
     * @return StoreProduct
     */
    protected function tidyCurrenciesToReduceAmbiguity(StoreProduct $product): StoreProduct
    {
        unset($product->dollar_price);
        unset($product->euro_price);
        return $product;
    }

    /**
     * @param StoreProduct $product
     * @return StoreProduct
     */
    protected function setComputedValuesSoNativeToJsonCanParseThem(StoreProduct $product): StoreProduct
    {
        // these are all computed with Laravel's getXAttribute() magic method.
        // Although the left and right side of each look the same, the right
        // side is computing the value, and setting it as a fixed value to the
        // same attribute. toJson() won't include computed values, so this is
        // how we can make them available.

        $product->price = $product->price;
        $product->title = $product->title;
        $product->image_url = $product->image_url;

        return $product;
    }
}
