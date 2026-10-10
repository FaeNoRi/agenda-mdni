<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'different:current_password'],
        ], [
            'password.different' => 'Le nouveau mot de passe doit être différent de l\'ancien.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Déconnecte les autres appareils : leur session ne correspond plus au nouveau mot de passe
        // (middleware AuthenticateSession) ; la session courante reste ouverte.
        Auth::logoutOtherDevices($validated['password']);

        return back()->with('status', 'password-updated');
    }
}
