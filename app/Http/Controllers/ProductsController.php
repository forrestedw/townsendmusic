<?php

namespace App\Http\Controllers;

use App\Models\StoreProduct;
use App\Repositories\ProductRepository;
use App\store_products;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public $storeId;
    private $products;

    public function __construct(ProductRepository $products)
    {
        /* As the system manages multiple stores a storeBuilder instance would
        normally be passed here with a store object. The id of the example
        store is being set here for the purpose of the test */
        $this->storeId = 3;

        $this->products = $products;
    }

    public function index(Request $request)
    {
        $products = StoreProduct::query()
            ->isAvailable()
            ->inSection('T-Shirts')
            ->inStore($this->storeId)
            ->sort($this->getSort())
            ->paginate(100)
//            ->paginate($this->getProductsPerPage())
            ->appends($request->query());

        return view('store-product.index', compact('products'));
    }

    public function old()
    {
        $service = new store_products();

        $results = $service->sectionProducts(3, 'ALL');

        dd($results);
    }

    /**
     * @return bool
     */
    protected function perPageIsNumeric(): bool
    {
        return isset($_GET['perPage']) && is_int((int) $_GET['perPage']) && (int)$_GET['perPage'] !== 0;
    }

    /**
     * @return int
     */
    protected function getProductsPerPage(): int
    {
        return $this->perPageIsNumeric() ? (int) $_GET['perPage'] : 8;
    }

    /**
     * @return string
     */
    protected function getSort(): string
    {
        return isset($_GET['sort']) ? $_GET['sort'] : 'position';
    }
}
