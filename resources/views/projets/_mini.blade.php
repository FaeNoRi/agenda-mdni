{{--
    Mini-carte d'un projet (projets terminés ou annulés) : plus compacte que la carte, cliquable pour consulter le détail.
    Variables : $projet (taches.responsables + membres chargés), $mienne (bool).
    Mêmes attributs data-* que la carte : le filtrage de la page fonctionne à l'identique.
--}}
@php
    $resume = $projet->resume();
    $couleur = $projet->couleur ?: '#4299e1';
    $personnes = $projet->personnesImpliquees();
@endphp
<a href="{{ route('projets.show', $projet) }}"
   class="pt-mini-projet pt-carte--clos"
   style="--pc: {{ $couleur }};"
   data-projet data-id="{{ $projet->id }}" data-etat="{{ $projet->etat->value }}" data-mien="{{ $mienne ? 1 : 0 }}" data-retard="{{ $projet->aDuRetard() ? 1 : 0 }}"
   data-titre="{{ mb_strtolower($projet->nom) }}" data-pers="{{ $personnes->pluck('id')->join(',') }}"
   data-date="{{ $projet->date_limite?->toDateString() }}">
    <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
        <span class="pt-picto" style="width: 28px; height: 28px; background: {{ $couleur }}1f; color: {{ $couleur }};">
            <x-icone :name="$projet->icone ?: 'folder'" :size="16" />
        </span>
        <span class="pt-mini-projet__nom" title="{{ $projet->nom }}">{{ $projet->nom }}</span>
    </div>
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-top: 8px;">
        <span title="État du projet" class="pt-badge" style="background: {{ $projet->etat->couleur() }}1f; color: {{ $projet->etat->couleur() }};">
            <x-icone :name="$projet->etat->icone()" :size="13" /> {{ $projet->etat->label() }}
        </span>
        <span style="font-size: 11.5px; color: var(--c-muted); white-space: nowrap;">
            @if($projet->date_limite)
                {{ $projet->date_limite->format('d/m/Y') }}
            @endif
            @if($resume['actives'] > 0)
                · {{ $resume['terminees'] }}/{{ $resume['actives'] }}
            @endif
        </span>
    </div>
</a>
