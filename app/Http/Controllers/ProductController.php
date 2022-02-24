<?php

namespace App\Http\Controllers;

use App\Models\StoreProduct;
use App\Repositories\ProductRepository;
use App\Services\StoreProductToJsonService;
use App\store_products;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductController extends Controller
{
    public $storeId;
    private $productsQuery;
    private StoreProductToJsonService $toJsonService;

    public function __construct(ProductRepository $products, StoreProductToJsonService $toJsonService)
    {
        /* As the system manages multiple stores a storeBuilder instance would
        normally be passed here with a store object. The id of the example
        store is being set here for the purpose of the test */
        $this->storeId = 3;

        $this->perPage = $products->getPerPage();

        $sort = $products->getSort();

        $this->toJsonService = $toJsonService;

        $this->productsQuery = $products
            ->with('artist')
            ->inStore($this->storeId)
            ->availableProductsOnly()
            ->sortBy($sort);
    }

    public function __invoke(Request $request, $section = 'all')
    {
        $paginatedProducts = $this->productsQuery

            ->inSection($section)

            // make use of laravel pagination
            ->paginate($this->perPage)

            // append query, if we want $_GET request data in pagination links
            // as we are passing json back aren't making use of this data,
            // but it is straight forward to add in if we want
            ->appends($request->query());

        dump($paginatedProducts->count());

        dd($this->toJsonService->products($paginatedProducts));

        // optional view output
        return view('store-product.index', compact('paginatedProducts'));
    }
}
