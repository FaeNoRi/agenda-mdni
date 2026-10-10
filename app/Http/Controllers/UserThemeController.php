<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserThemeController extends Controller
{
    /** Les 10 pastilles du sélecteur de la barre de navigation. */
    public const COULEURS = ['blue', 'azure', 'indigo', 'purple', 'pink', 'red', 'orange', 'yellow', 'lime', 'green'];

    public function updateThemeColor(Request $request)
    {
        $request->validate([
            'color' => ['required', Rule::in(self::COULEURS)]
        ]);

    /** @var \App\Models\User $user */
    $user = Auth::user();

    $user->update(['theme' => $request->color]);
    $user->fresh(); // utile si tu veux utiliser $user ensuite

    return response()->json(['status' => 'ok']);
    }
}