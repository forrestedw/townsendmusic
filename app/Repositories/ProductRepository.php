<?php

namespace App\Repositories;

use App\Models\StoreProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductRepository extends Builder
{
    public function __construct()
    {
        $this->query = StoreProduct::query();

//        $this->isAvailable();
    }

    public function inStore(int $storeId)
    {
        $this->query->whereStoreId($storeId);

        return $this;
    }

    public function inSection($section = 'ALL')
    {
        if (Str::upper($section) === 'ALL') {
            return $this;
        }

        if (is_numeric($section)) {
            $section_field = 'section_id';
            $section_compare = '=';
        } else {
            $section_field = 'description';
            $section_compare = 'LIKE';
        }

        $this->query->whereHas('sections', fn (Builder $query) =>
            $query->where($section_field, $section_compare, $section)
        );

        return $this;
    }

    protected function isAvailable($bool = 1)
    {
//        $this->query->where('available', '=', $bool);

        return $this;
    }
}
