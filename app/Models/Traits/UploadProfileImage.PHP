<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait UploadProfileImage
{
    public function uploadImageByRole(Request $request, $model)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        $path = $request->file('image')->store('profiles', 'public');

        $model->update([
            'image' => $path
        ]);

        return response()->json([
            'message' => 'Imagen actualizada correctamente',
            'image' => $path
        ]);
    }
}