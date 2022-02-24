
<ul>
    @foreach($products as $product)
        <li>{{ $product->name }}</li>
    @endforeach
</ul>

{{ $products->links() }}
