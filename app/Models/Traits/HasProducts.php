<?php
namespace App\Models\Traits;

use App\Models\Product;

trait HasProducts
{
    public function products()
    {
        return $this->morphMany(Product::class, 'productable');
    }

    public function addProduct(array $data): Product
{
    return $this->products()->create([
        'name'        => $data['name'],
        'description' => $data['description'] ?? null,
        'price'       => $data['price'],
        'image'       => $data['image'] ?? null,
        'stock'       => $data['stock'] ?? 0,
    ]);
}

}
