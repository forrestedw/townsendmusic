<?php

namespace App\Http\Controllers;

use App\Repositories\ProductRepository;
use App\Services\StoreProductToJsonService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public $storeId;
    private Builder $productsQuery;
    private StoreProductToJsonService $toJsonService;

    public function __construct(ProductRepository $products, StoreProductToJsonService $toJsonService)
    {
        /* As the system manages multiple stores a storeBuilder instance would
        normally be passed here with a store object. The id of the example
        store is being set here for the purpose of the test */
        $this->storeId = 3;

        $this->perPage = $products->getPerPage();

        $this->sort = $products->getSort();

        $this->toJsonService = $toJsonService;

        $this->productsQuery = $products
            ->availableProductsOnly()
            ->inStore($this->storeId)
            ->with('artist')
            ->sortBy($this->sort);
    }

    public function __invoke(Request $request, $section = 'all')
    {
        $paginatedProducts = $this->productsQuery

            ->inSection($section)

            // make use of laravel pagination so that we don't have to do our
            // own page numbers etc. Laravel provides ?page=x in this way,
            // which is the same language used as in the original code.
            ->paginate($this->perPage)

            // Append query, if we want $_GET request data in pagination links.
            // (e.g. ?page=x&perPage=y&sort=az <-- the perPage & sort
            // are our custom $_GET data, and will be appended to pagination links).
            // The current json being passed back is just of the Collection,
            // but it is easy enough to include the links data as well if needed.
            ->appends($request->query());

        return $this->toJsonService->products($paginatedProducts);

        // Optional view output. Links etc work. No css provided.
        return view('store-product.index', compact('paginatedProducts'));
    }
}
