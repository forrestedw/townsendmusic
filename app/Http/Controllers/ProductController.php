<?php

namespace App\Http\Controllers;

use App\Models\StoreProduct;
use App\Repositories\ProductRepository;
use App\store_products;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductController extends Controller
{
    public $storeId;

    private $products;

    public function __construct(ProductRepository $products)
    {
        /* As the system manages multiple stores a storeBuilder instance would
        normally be passed here with a store object. The id of the example
        store is being set here for the purpose of the test */
        $this->storeId = 3;

        $this->perPage = $products->getPerPage();

        $this->products = $products
            ->with('artist')
            ->inStore($this->storeId);
    }

    public function __invoke(Request $request, $section = 'all')
    {
        $products = $this->products->inSection($section)

            // make use of laravel pagination
            ->paginate($this->perPage)

            // append query, if we want $_GET request data in pagination links
            // as we are passing json back aren't making use of this data,
            // but it is straight forward to add in if we want
            ->appends($request->query());

        return $this->toJson($products);

        // optional view output
        return view('store-product.index', compact('products'));
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
