<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ChangementHoraire;
use Illuminate\Http\Request;

class ChangementHoraireController extends Controller
{
    public function index()
    {
        $changements_horaires = ChangementHoraire::with('user')->orderByDesc('id')->get();
        return view('changements_horaires.index', compact('changements_horaires'));
    }

    public function create()
    {
        if (request()->ajax()) {
            $changements_horaire = new ChangementHoraire(); 
            $users = User::whereNotIn('id', [0, 16, 17])->orderBy('name')->get();
            return view('changements_horaires._form', compact('changements_horaire','users'));
        }

        abort(404);
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type_chgmt' => 'required|string|max:255',
            'old_start' => 'required',
            'old_end' => 'required',
            'new_start' => 'required',
            'new_end' => 'required',
        ]);

        ChangementHoraire::create($validated);

        return redirect()->route('changements_horaires.index')->with('success', 'Changement horaire ajouté.');
    }

    public function edit(ChangementHoraire $changements_horaire)
    {
        if (request()->ajax()) {
            $users = User::whereNotIn('id', [0, 16, 17])->orderBy('name')->get();
            return view('changements_horaires._form', compact('changements_horaire','users'));
        }

        abort(404);
    }
    
    public function update(Request $request, ChangementHoraire $changements_horaire)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type_chgmt' => 'required|string|max:255',
            'old_start' => 'required',
            'old_end' => 'required',
            'new_start' => 'required',
            'new_end' => 'required',
        ]);

        $changements_horaire->update($validated);

        return redirect()->route('changements_horaires.index')->with('success', 'Changement horaire modifié.');
    }

    public function destroy(ChangementHoraire $changements_horaire)
    {
        $changements_horaire->delete();
        return redirect()->route('changements_horaires.index')->with('success', 'Changement horaire supprimé.');
    }
}
