<?php

namespace App\Http\Controllers;

use App\Models\Commentaire;
use App\Models\Projet;
use App\Models\Tache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Commentaires des tâches et des projets : ajout (sur la tâche ou le projet), modification par
 * l'auteur, suppression par l'auteur ou un administrateur. Tout se fait en Ajax depuis la fiche.
 */
class CommentaireController extends Controller
{
    public function storeTache(Request $request, Tache $tache): JsonResponse
    {
        Gate::authorize('comment', $tache);

        return $this->enregistrer($request, $tache);
    }

    public function storeProjet(Request $request, Projet $projet): JsonResponse
    {
        Gate::authorize('comment', $projet);

        return $this->enregistrer($request, $projet);
    }

    public function update(Request $request, Commentaire $commentaire): JsonResponse
    {
        Gate::authorize('update', $commentaire);

        $commentaire->update(['contenu' => $this->contenu($request)]);

        return response()->json(['ok' => true, 'id' => $commentaire->commentable_id]);
    }

    public function destroy(Commentaire $commentaire): JsonResponse
    {
        Gate::authorize('delete', $commentaire);

        $commentaire->delete();

        return response()->json(['ok' => true, 'id' => $commentaire->commentable_id]);
    }

    private function enregistrer(Request $request, Model $cible): JsonResponse
    {
        $cible->commentaires()->create([
            'user_id' => $request->user()->id,
            'contenu' => $this->contenu($request),
        ]);

        return response()->json(['ok' => true, 'id' => $cible->getKey()]);
    }

    private function contenu(Request $request): string
    {
        return trim($request->validate([
            'contenu' => ['required', 'string', 'max:2000'],
        ], [
            'contenu.required' => 'Écrivez votre commentaire.',
            'contenu.max' => 'Le commentaire est trop long (2000 caractères au maximum).',
        ])['contenu']);
    }
}
