<?php

namespace App\Repositories;

use App\Models\StoreProduct;

class ProductRepository extends StoreProduct
{
    public function __construct()
    {
        parent::__construct();

        return StoreProduct::query();
    }

    public function getPerPage(): int
    {
        return $this->perPageIsNumeric() ? (int) $_GET['perPage'] : 8;
    }

    public function getSort(): string
    {
        return isset($_GET['sort']) ? $_GET['sort'] : 'position';
    }

    protected function perPageIsNumeric(): bool
    {
        return isset($_GET['perPage']) && is_int((int) $_GET['perPage']) && (int)$_GET['perPage'] !== 0;
    }
}
