<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;

class SectionProductController extends Controller
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

    public function __invoke(Request $request, $section)
    {
        $products = $this->products
            ->inSection($section)
            ->paginate($this->perPage)
            ->appends($request->query());

        return view('store-product.index', compact('products'));
    }
}
