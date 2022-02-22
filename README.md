## Townsend Music Laravel coding test

Refactor the `sectionProducts()` method in `app/store_products.php` along with all of it's functionality into Laravel.

Two routes should be created `/products` and `/products/sectionname` that return all the products and then just the products for the selected section.

A ProductsController is in place to set these up, and they should return JSON of the same info passed by the original method

The models and relationships have already been created.

### Sample Data
You'll be able to install the application by running `php artisan migrate --seed`
