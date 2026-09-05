<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('horaire')->whereNotIn('id', [0, 16, 17])->orderBy('name')->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $horaire = new \App\Models\Horaire(['nom' => 'Nouveau planning temporaire']);
        $horaire->setRelation('jours', collect());

        if (request()->ajax()) {
            return view('users._form', compact('jours', 'horaire'))->render();
        }

        return redirect()->route('users.index');
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
            'is_admin' => 'nullable|boolean',
            'is_equipe' => 'nullable|boolean',
            'is_email' => 'nullable|boolean',
        ]);

        $horaire = \App\Models\Horaire::create([
            'nom' => 'Planning_' . $validated['name'],
        ]);

        foreach ($request->jours as $jour => $data) {
            $horaire->jours()->create([
                'jour' => $jour,
                'debut' => $data['debut'] ?? null,
                'pause_debut' => $data['pause_debut'] ?? null,
                'pause_fin' => $data['pause_fin'] ?? null,
                'fin' => $data['fin'] ?? null,
                'matin' => isset($data['matin']),
                'aprem' => isset($data['aprem']),
                'repos' => isset($data['repos']),
            ]);
        }

        $validated['password'] = bcrypt($validated['password']);
        $validated['is_admin'] = $request->boolean('is_admin');
        $validated['is_equipe'] = $request->boolean('is_equipe');
        $validated['is_email'] = $request->boolean('is_email');
        $validated['id_horaire'] = $horaire->id;

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'Utilisateur créé.');
    }

    public function edit(User $user)
    {
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

        $horaire = $user->horaire;

        if (!$horaire) {
            $horaire = new \App\Models\Horaire(['nom' => 'Planning vide']);
            $horaire->setRelation('jours', collect());
        } else {
            $horaire->load('jours');
        }

        if (request()->ajax()) {
            return view('users._form', compact('user', 'horaire', 'jours'))->render();
        }

        return redirect()->route('users.index');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'nullable|confirmed|min:6',
            'is_admin' => 'nullable|boolean',
            'is_equipe' => 'nullable|boolean',
            'is_email' => 'nullable|boolean',
            'theme' => 'in:blue,azure,indigo,purple,pink,red,orange,yellow,lime,green',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = bcrypt($request->password);
        } else {
            unset($validated['password']);
        }

        $validated['is_admin'] = $request->boolean('is_admin');
        $validated['is_equipe'] = $request->boolean('is_equipe');
        $validated['is_email'] = $request->boolean('is_email');

        // Si l'utilisateur n'a pas de planning, on en crée un
        $horaire = $user->horaire;
        if (!$horaire) {
            $horaire = \App\Models\Horaire::create([
                'nom' => 'Planning_' . $user->name,
            ]);
            $user->id_horaire = $horaire->id;
            $user->save();
        } else {
            $horaire->update(['nom' => 'Planning_' . $user->name]);
        }

        // Mise à jour ou création des jours
        foreach ($request->jours as $jour => $data) {
            $jourModel = $horaire->jours()->firstOrNew(['jour' => $jour]);

            $jourModel->fill([
                'debut' => $data['debut'] ?? null,
                'pause_debut' => $data['pause_debut'] ?? null,
                'pause_fin' => $data['pause_fin'] ?? null,
                'fin' => $data['fin'] ?? null,
                'matin' => isset($data['matin']),
                'aprem' => isset($data['aprem']),
                'repos' => isset($data['repos']),
            ])->save();
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Utilisateur modifié.');
    }

    public function destroy(User $user)
    {
        if ($user->horaire) {
            $user->horaire->jours()->delete();
            $user->horaire->delete();          
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé avec son planning.');
    }
}
