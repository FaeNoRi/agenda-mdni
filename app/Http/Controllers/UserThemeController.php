<?php

namespace App\Http\Controllers;

use App\Support\ThemeColors;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserThemeController extends Controller
{
    /** Pastilles « classiques » de l'ancien sélecteur (conservé pour les tests et la compatibilité). */
    public const COULEURS = ['blue', 'azure', 'indigo', 'purple', 'pink', 'red', 'orange', 'yellow', 'lime', 'green'];

    /** Modes d'affichage : « clair » par défaut, ou « sombre ». */
    public const MODES = ['clair', 'sombre'];

    /** Enregistre le mode d'affichage (clair / sombre) de l'utilisateur. */
    public function updateMode(Request $request): JsonResponse
    {
        $data = $request->validate(['mode' => ['required', Rule::in(self::MODES)]]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update(['mode' => $data['mode']]);

        return response()->json(['status' => 'ok', 'mode' => $user->mode]);
    }

    /**
     * Enregistre la couleur du thème : un nom de la palette (classique ou pastel) ou une couleur
     * libre #rrggbb. Renvoie les valeurs résolues (couleur, texte, variantes) pour l'aperçu.
     */
    public function updateThemeColor(Request $request): JsonResponse
    {
        $request->validate([
            'color' => ['required', 'string', function (string $attribut, mixed $valeur, \Closure $echec) {
                if (!ThemeColors::estValide($valeur)) {
                    $echec('Cette couleur n\'est pas valide.');
                }
            }],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update(['theme' => $request->string('color')->toString()]);

        return response()->json(['status' => 'ok', 'couleur' => ThemeColors::resolve($user->theme)]);
    }
}
