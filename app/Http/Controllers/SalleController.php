<?php

namespace App\Http\Controllers;

use App\Models\Salles;
use Illuminate\Http\Request;

class SalleController extends Controller
{
    public function index()
    {
        $salles = Salles::orderBy('nom_salle')->whereNotIn('id', [0])->get();

        return view('salles.index', compact('salles'));
    }

    public function create()
    {
        if (request()->ajax()) {
            return view('salles._form', ['salle' => null])->render();
        }

        return redirect()->route('salles.index');
    }

    public function edit(Salles $salle)
    {
        if (request()->ajax()) {
            return view('salles._form', compact('salle'))->render();
        }

        return redirect()->route('salles.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_salle' => 'required|string|max:255',
            'type_salle' => 'required|string|max:255',
        ]);

        Salles::create($validated);

        return redirect()->route('salles.index')->with('success', 'Salle ajoutée avec succès.');
    }

    public function update(Request $request, Salles $salle)
    {
        $validated = $request->validate([
            'nom_salle' => 'required|string|max:255',
            'type_salle' => 'required|string|max:255',
        ]);

        $salle->update($validated);

        return redirect()->route('salles.index')->with('success', 'Salle modifiée avec succès.');
    }

    public function destroy(Salles $salle)
    {
        $salle->delete();

        return redirect()->route('salles.index')->with('success', 'Salle supprimée avec succès.');
    }
}
