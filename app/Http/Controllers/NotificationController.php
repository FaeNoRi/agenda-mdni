<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\NotificationPreference;
use App\Support\NotificationTypes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Gate;

/**
 * Tiroir de notifications : liste (JSON), marquer comme lue / non lue, tout marquer comme lu,
 * et préférences du Profil. Chaque personne ne voit et ne modifie que ses propres notifications.
 */
class NotificationController extends Controller
{
    /** Les lues sont conservées 30 jours ; les non lues, tant qu'elles ne sont pas traitées. */
    public const CONSERVATION_JOURS = 30;

    public function index(Request $request): JsonResponse
    {
        $liste = $request->user()->notificationsApp()
            ->where(fn ($q) => $q->whereNull('vue_at')->orWhere('vue_at', '>=', now()->subDays(self::CONSERVATION_JOURS)))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->limit(200)
            ->get();

        return response()->json([
            'non_lues' => $liste->whereNull('vue_at')->count(),
            'notifications' => $liste->map(fn (AppNotification $n) => [
                'id' => $n->id,
                'categorie' => $n->categorie,
                'icone' => NotificationTypes::icone($n->type),
                'titre' => $n->titre,
                'contexte' => $n->contexte,
                'projet' => $n->projet_nom,
                'jalon' => $n->jalon,
                'jours' => $n->echeance ? (int) today()->diffInDays($n->echeance->copy()->startOfDay(), false) : null,
                'url' => $n->url,
                'vue' => $n->vue_at !== null,
                'cree' => $n->created_at->toIso8601String(),
            ])->values(),
        ]);
    }

    public function compte(Request $request): JsonResponse
    {
        return response()->json(['non_lues' => $request->user()->notificationsApp()->nonVues()->count()]);
    }

    public function vue(Request $request, AppNotification $notification): JsonResponse
    {
        Gate::authorize('update', $notification);

        $vue = $request->boolean('vue', true);
        $notification->forceFill(['vue_at' => $vue ? ($notification->vue_at ?? now()) : null])->save();

        return response()->json(['ok' => true, 'non_lues' => $request->user()->notificationsApp()->nonVues()->count()]);
    }

    public function toutVu(Request $request): JsonResponse
    {
        $request->user()->notificationsApp()->nonVues()->update(['vue_at' => now()]);

        return response()->json(['ok' => true, 'non_lues' => 0]);
    }

    /** Préférences du Profil : cases cochées = types désactivables que la personne veut recevoir. */
    public function preferences(Request $request): RedirectResponse
    {
        $voulus = collect((array) $request->input('types', []))
            ->filter(fn ($t) => is_string($t) && in_array($t, NotificationTypes::desactivables(), true));

        foreach (NotificationTypes::desactivables() as $type) {
            NotificationPreference::updateOrCreate(
                ['user_id' => $request->user()->id, 'type' => $type],
                ['actif' => $voulus->contains($type)],
            );
        }

        return Redirect::route('profile.edit')->with('status', 'notifications-updated');
    }
}
