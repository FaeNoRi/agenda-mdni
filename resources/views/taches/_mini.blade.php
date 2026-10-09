{{-- Mini-carte d'une tâche. Variables : $tache (relation responsables chargée). --}}
@php
    $s = $tache->statut;
    $annule = $s === \App\Enums\TacheStatut::Annule;
@endphp
<div class="pt-mini {{ $s->estOuvert() ? '' : 'pt-mini--clos' }}" style="--sc: {{ $s->couleur() }};" data-statut="{{ $s->value }}">
    <div style="display: flex; align-items: flex-start; gap: 8px;">
        <x-statut-carre :statut="$s" :size="24" />
        <div class="pt-mini__titre" @if($annule) style="text-decoration: line-through; color: #9aa0ac;" @endif>{{ $tache->titre }}</div>
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
