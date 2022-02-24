
<ul>
    @foreach($paginatedProducts as $product)
        <li>{{ $product->title }}</li>
    @endforeach
</ul>

{{ $paginatedProducts->links() }}
