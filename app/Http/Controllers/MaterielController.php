<?php

namespace App\Http\Controllers;

use App\Models\Materiels;
use Illuminate\Http\Request;

class MaterielController extends Controller
{
    public function index()
    {
        $materiels = Materiels::orderBy('nom_mat')->whereNotIn('id', [0])->get();
        return view('materiels.index', compact('materiels'));
    }

    public function create()
    {
        if (request()->ajax()) {
            return view('materiels._form', ['materiel' => null])->render();
        }

        return redirect()->route('materiels.index');
    }

    public function edit(Materiels $materiel)
    {
        if (request()->ajax()) {
            return view('materiels._form', compact('materiel'))->render();
        }

        return redirect()->route('materiels.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_mat' => 'required|string|max:255',
            'nb_stock' => 'required|integer|min:0',
        ]);

        Materiels::create($validated);

        return redirect()->route('materiels.index')->with('success', 'Matériel ajouté.');
    }

    public function update(Request $request, Materiels $materiel)
    {
        $validated = $request->validate([
            'nom_mat' => 'required|string|max:255',
            'nb_stock' => 'required|integer|min:0',
        ]);

        $materiel->update($validated);

        return redirect()->route('materiels.index')->with('success', 'Matériel modifié.');
    }

    public function destroy(Materiels $materiel)
    {
        $materiel->delete();
        return redirect()->route('materiels.index')->with('success', 'Matériel supprimé.');
    }
}
