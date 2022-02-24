<?php

namespace App\Repositories;

use App\Models\StoreProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductRepository extends StoreProduct
{
    public function __construct()
    {
        return StoreProduct::query()
            ->availableProductsOnly()
            ->sortBy($this->getSort());
    }

    protected function getSort(): string
    {
        return isset($_GET['sort']) ? $_GET['sort'] : 'position';
    }

    public function getPerPage(): int
    {
        return $this->perPageIsNumeric() ? (int) $_GET['perPage'] : 8;
    }

    protected function perPageIsNumeric(): bool
    {
        return isset($_GET['perPage']) && is_int((int) $_GET['perPage']) && (int)$_GET['perPage'] !== 0;
    }
}
