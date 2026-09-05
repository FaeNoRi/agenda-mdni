<?php

namespace App\Http\Controllers;

use App\Models\Adherents;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdherentController extends Controller{

    public function index(){
        $adherents = Adherents::all();
        return view('adherents.index', compact('adherents'));
    }

    public function create(){
        if (request()->ajax()) {
            return view('adherents._form', ['adherent' => null])->render();
        }
        return redirect()->route('adherents.index');
    }

    public function store(Request $request){
        $validated = $request->validate([
            'nom_adh'        => 'required|string|max:255',
            'situation_adh'  => 'required|string|max:255',
            'dom_adh'        => 'required|string|max:255',
            'date_adh'       => 'required|date',
            'photo_adh'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'isCGU'          => 'boolean',
            'isPresent'      => 'boolean',
        ]);

        // On crée l'adhérent sans la photo d'abord
        $adherent = Adherents::create([
            'nom_adh'       => $validated['nom_adh'],
            'situation_adh' => $validated['situation_adh'],
            'dom_adh'       => $validated['dom_adh'],
            'type_adh'      => '',
            'date_adh'      => $validated['date_adh'],
            'isCGU'         => (bool)($validated['isCGU'] ?? false),
            'isPresent'     => (bool)($validated['isPresent'] ?? false),
        ]);

        // Upload si présent
        if ($request->hasFile('photo_adh')) {
            $file   = $request->file('photo_adh');
            $ext    = strtolower($file->getClientOriginalExtension());
            $name   = Str::uuid().'.'.$ext;
            $dest   = public_path('assets/adherents');

            if (!is_dir($dest)) { @mkdir($dest, 0755, true); }

            $file->move($dest, $name);

            $adherent->update(['photo_adh' => 'assets/adherents/'.$name]);
        }

        return redirect()->route('adherents.index')->with('success', 'Adhérent ajouté avec succès.');
    }

    public function show(Adherents $adherent){
        return view('adherents.show', compact('adherent'));
    }

    public function edit(Adherents $adherent){
        if (request()->ajax()) {
            return view('adherents._form', compact('adherent'))->render();
        }
        return redirect()->route('adherents.index');
    }

    public function update(Request $request, Adherents $adherent){
        $validated = $request->validate([
            'nom_adh'        => 'required|string|max:255',
            'situation_adh'  => 'required|string|max:255',
            'dom_adh'        => 'required|string|max:255',
            'date_adh'       => 'required|date',
            'photo_adh'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'isCGU'          => 'boolean',
            'isPresent'      => 'boolean',
        ]);

        $adherent->fill([
            'nom_adh'       => $validated['nom_adh'],
            'situation_adh' => $validated['situation_adh'],
            'dom_adh'       => $validated['dom_adh'],
            'date_adh'      => $validated['date_adh'],
            'isCGU'         => (bool)($validated['isCGU'] ?? $adherent->isCGU),
            'isPresent'     => (bool)($validated['isPresent'] ?? $adherent->isPresent),
        ]);

        // Nouvelle photo ?
        if ($request->hasFile('photo_adh')) {
            $adherent->deletePhotoFile();                 // supprime l’ancienne si présente

            $file   = $request->file('photo_adh');
            $ext    = strtolower($file->getClientOriginalExtension());
            $name   = Str::uuid().'.'.$ext;
            $dest   = public_path('assets/adherents');

            if (!is_dir($dest)) { @mkdir($dest, 0755, true); }

            $file->move($dest, $name);

            $adherent->photo_adh = 'assets/adherents/'.$name;
        }

        $adherent->save();

        return redirect()->route('adherents.index')->with('success', 'Adhérent modifié avec succès.');
    }

    public function destroy(Adherents $adherent){
        $adherent->deletePhotoFile();
        $adherent->delete();
        return redirect()->route('adherents.index')->with('success', 'Adhérent supprimé.');
    }
}
