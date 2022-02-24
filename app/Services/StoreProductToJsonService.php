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
        $products = $this->getProtectedItemsCollectionFromPaginator($products)
            ->each(function(StoreProduct $product) {
                $product = $this->setComputedValues($product);
                $product = $this->tidyCurrencies($product);
            })->toArray();

        return json_encode($products, JSON_PRETTY_PRINT);
    }

    /**
     * We're using the paginator to ease, but the collection is protected.
     * This allows us to get the Collection off the paginator so we can
     * return is as json.
     *
     * @param LengthAwarePaginator $paginator
     * @return mixed
     */
    protected function getProtectedItemsCollectionFromPaginator(LengthAwarePaginator $paginator) {
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
     */
    protected function tidyCurrencies(StoreProduct $product): StoreProduct
    {
        unset($product->dollar_price);
        unset($product->euro_price);

        return $product;
    }

    /**
     * these are all computed with getXProperty magic method
     * setting these like this make them available to toArray(),
     * the precursor to json encoding
     * @param StoreProduct $product
     *
     */
    protected function setComputedValues(StoreProduct $product): StoreProduct
    {
        $product->price = $product->price;
        $product->title = $product->title;
        $product->image_url = $product->image_url;

        return $product;
    }
}
