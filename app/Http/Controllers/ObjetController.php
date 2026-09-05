<?php

namespace App\Http\Controllers;

use App\Models\Objets;
use Illuminate\Http\Request;

class ObjetController extends Controller
{
    public function index()
    {
        $objets = Objets::whereNotIn('id', [0])->orderBy('nom_obj')->get();
        return view('objets.index', compact('objets'));
    }

    public function create()
    {
        if (request()->ajax()) {
            return view('objets._form', ['objet' => null])->render();
        }

        return redirect()->route('objets.index');
    }

    public function edit(Objets $objet)
    {
        if (request()->ajax()) {
            return view('objets._form', compact('objet'))->render();
        }

        return redirect()->route('objets.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_obj' => 'required|string|max:255',
        ]);

        Objets::create($validated);

        return redirect()->route('objets.index')->with('success', 'Objet ajouté.');
    }

    public function update(Request $request, Objets $objet)
    {
        $validated = $request->validate([
            'nom_obj' => 'required|string|max:255',
        ]);

        $objet->update($validated);

        return redirect()->route('objets.index')->with('success', 'Objet modifié.');
    }

    public function destroy(Objets $objet)
    {
        $objet->delete();
        return redirect()->route('objets.index')->with('success', 'Objet supprimé.');
    }
}
