<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopController extends Controller
{
    public function index()
    {
      $shops = Shop::with(['user', 'feedbacks', 'products', 'services', 'news', 'posts.comments'])
        ->latest()
        ->get();
        return response()->json($shops);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'image' => 'nullable|string',
            'schedule' => 'nullable|string',
        ]);

        $shop = Shop::create(array_merge($request->all(), ['user_id' => Auth::id()]));

        return response()->json(['message' => 'Shop created.', 'data' => $shop], 201);
    }

    public function show($id)
    {
        $shop = Shop::with([
                'user',
                'products',  
                'feedbacks.user',
                'posts.comments',
                'news'
             ])->findOrFail($id);
        return response()->json($shop);
    }

    public function update(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);

        if ($shop->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $shop->update($request->all());
        return response()->json(['message' => 'Shop updated.', 'data' => $shop]);
    }

    // GET /api/shops/me
public function me()
{
    $shop = Shop::with(['user', 'feedbacks', 'products', 'services'])
        ->where('user_id', Auth::id())
        ->firstOrFail();

    return response()->json($shop);
}


    public function destroy($id)
    {
        $shop = Shop::findOrFail($id);

        if ($shop->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $shop->delete();
        return response()->json(['message' => 'Shop deleted.']);
    }

    // GET /api/shops/latest
public function latest()
{
    $shops = Shop::with([
        'user',
        'services',
        'products'
    ])
    ->latest()
    ->limit(5)
    ->get();

    return response()->json($shops);
}

}
