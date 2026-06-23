<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Lawyer;
use App\Models\Traits\UploadImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LawyerController extends Controller
{
    use UploadImage;

    public function index()
    {
        $lawyers = Lawyer::with([
            'user',
            'feedbacks.user',
            'posts.comments',
            'services'
        ])->latest()->get();

        return response()->json($lawyers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'specialty' => 'nullable|string|max:255',
            'license_code' => 'nullable|string|max:50',
            'services' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'university' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'schedule' => 'nullable|string',
        ]);

        $imageUrl = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/lawyers';
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $file->move($destinationPath, $filename);
            $imageUrl = 'https://apiapk.tudealer.app/imagenes_app/lawyers/' . $filename;
        }

        $lawyer = Lawyer::create([
            'user_id' => Auth::id(),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'description' => $request->description,
            'specialty' => $request->specialty,
            'license_code' => $request->license_code,
            'services' => $request->services,
            'city' => $request->city,
            'university' => $request->university,
            'image' => $imageUrl,
            'schedule' => $request->schedule,
        ]);

        return response()->json([
            'message' => 'Lawyer created.',
            'data' => $lawyer
        ], 201);
    }

    public function show($id)
    {
        $lawyer = Lawyer::with([
            'user',
            'feedbacks.user',
            'posts.comments',
            'services'
        ])->findOrFail($id);

        return response()->json($lawyer);
    }

    public function me()
    {
        $lawyer = Lawyer::with([
            'user',
            'feedbacks.user',
            'posts.comments',
            'services'
        ])
        ->where('user_id', Auth::id())
        ->firstOrFail();

        return response()->json($lawyer);
    }

    public function update(Request $request, $id)
    {
        $lawyer = Lawyer::findOrFail($id);

        if ($lawyer->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'specialty' => 'nullable|string|max:255',
            'license_code' => 'nullable|string|max:50',
            'services' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'university' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'schedule' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            $this->deleteImageFromProduction($lawyer->image);
            
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/lawyers';
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $file->move($destinationPath, $filename);
            $imageUrl = 'https://apiapk.tudealer.app/imagenes_app/lawyers/' . $filename;
            
            $lawyer->image = $imageUrl;
        }

        if ($request->has('image') && is_string($request->image)) {
            $lawyer->image = $request->image;
        }

        $lawyer->update($request->only([
            'first_name',
            'last_name',
            'description',
            'specialty',
            'license_code',
            'services',
            'city',
            'university',
            'schedule',
        ]));

        return response()->json([
            'message' => 'Lawyer updated.',
            'data' => $lawyer
        ]);
    }

    public function latest()
    {
        $lawyers = Lawyer::with([
            'user',
            'services'
        ])
        ->latest()
        ->limit(5)
        ->get();

        return response()->json($lawyers);
    }

    public function destroy($id)
    {
        $lawyer = Lawyer::findOrFail($id);

        if ($lawyer->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->deleteImageFromProduction($lawyer->image);

        $lawyer->delete();

        return response()->json(['message' => 'Lawyer deleted.']);
    }

    public function updateImage(Request $request)
    {
        $lawyer = Lawyer::where('user_id', Auth::id())->firstOrFail();

        return $this->uploadImageToProduction(
            $request,
            $lawyer,
            'lawyers'
        );
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (empty($query)) {
            return response()->json([]);
        }

        $lawyers = Lawyer::with([
            'user',
            'services'
        ])
        ->where(function($q) use ($query) {
            $q->where('first_name', 'LIKE', "%{$query}%")
              ->orWhere('last_name', 'LIKE', "%{$query}%")
              ->orWhere('specialty', 'LIKE', "%{$query}%")
              ->orWhere('city', 'LIKE', "%{$query}%")
              ->orWhere('university', 'LIKE', "%{$query}%")
              ->orWhere('description', 'LIKE', "%{$query}%");
        })
        ->latest()
        ->get();

        return response()->json($lawyers);
    }
}