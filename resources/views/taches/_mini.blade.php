{{--
    Mini-carte d'une tâche, cliquable (ouvre la fiche dans une fenêtre modale).
    Variables : $tache (relation responsables chargée).
    Les attributs data-* servent au filtrage instantané de la page Tâches.
--}}
@php
    $s = $tache->statut;
    $annule = $s === \App\Enums\TacheStatut::Annule;
    $ids = $tache->responsables->pluck('id');
@endphp
<div class="pt-mini pt-mini--clic {{ $s->estOuvert() ? '' : 'pt-mini--clos' }}" style="--sc: {{ $s->couleur() }};"
     role="button" tabindex="0"
     data-tache="{{ $tache->id }}" data-statut="{{ $s->value }}" data-projet="{{ $tache->projet_id ?? 0 }}"
     data-retard="{{ $tache->estEnRetard() ? 1 : 0 }}" data-resp="{{ $ids->join(',') }}"
     data-mien="{{ $ids->contains(auth()->id()) ? 1 : 0 }}" data-date="{{ $tache->date_limite->toDateString() }}">
    <div style="display: flex; align-items: flex-start; gap: 8px;">
        <x-statut-carre :statut="$s" :size="24" />
        <div class="pt-mini__titre" @if($annule) style="text-decoration: line-through; color: #9aa0ac;" @endif>{{ $tache->titre }}@if($tache->recurrence_id) <span title="Tâche récurrente" style="color: #9aa0ac;"><x-icone name="repeat" :size="13" /></span>@endif</div>
    </div>
    @if($tache->raison && $s->exigeRaison())
        <div class="pt-mini__raison" style="background: {{ $s->couleur() }}1f; color: {{ $s->couleur() }};">
            <x-icone name="message-2" :size="12" /> {{ $tache->raison }}
        </div>
    @endif
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-top: auto;">
        <x-avatars :users="$tache->responsables" :size="24" />
        <x-echeance :item="$tache" />
    </div>
</div>
