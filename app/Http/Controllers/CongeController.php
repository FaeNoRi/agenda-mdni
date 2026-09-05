<?php

namespace App\Http\Controllers;

use App\Models\Conge;
use App\Models\User;
use Illuminate\Http\Request;

class CongeController extends Controller
{
    public function index()
    {
        $conges = Conge::with('user')->latest()->get();
        return view('conges.index', compact('conges'));
    }

    public function create()
    {
        $users = User::whereNotIn('id', [0, 16, 17])->orderBy('name')->get();
        return view('conges._form', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'start' => 'required',
            'end' => 'required|after_or_equal:start',
        ]);

        Conge::create($validated);
        return redirect()->route('conges.index')->with('success', 'Congé ajouté.');
    }

public function edit(Conge $conge)
{
    $users = User::all();

    return view('conges._form', [
        'isEdit' => true,
        'conge' => $conge,
        'users' => $users,
    ]);
}
    public function update(Request $request, Conge $conge)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'start' => 'required',
            'end' => 'required|after_or_equal:date_debut',
        ]);

        $conge->update($validated);
        return redirect()->route('conges.index')->with('success', 'Congé mis à jour.');
    }

    public function destroy(Conge $conge)
    {
        $conge->delete();
        return redirect()->route('conges.index')->with('success', 'Congé supprimé.');
    }
}
