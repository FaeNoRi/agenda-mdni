<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserThemeController extends Controller
{
    public function updateThemeColor(Request $request)
    {
        $request->validate([
            'color' => ['required', Rule::in(['blue', 'purple', 'green'])]
        ]);

    /** @var \App\Models\User $user */
    $user = Auth::user();

    $user->update(['theme' => $request->color]);
    $user->fresh(); // utile si tu veux utiliser $user ensuite

    return response()->json(['status' => 'ok']);
    }
}