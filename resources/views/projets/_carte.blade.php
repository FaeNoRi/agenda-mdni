{{--
    Carte d'un projet. Variables : $projet (taches.responsables + membres chargés), $mienne (bool), $statutsCarte.
    Les attributs data-* servent au filtrage instantané côté navigateur.
--}}
@php
    $resume = $projet->resume();
    $couleur = $projet->couleur ?: '#4299e1';
    // Référents d'abord, puis les responsables des tâches (hors annulées).
    $personnes = $projet->personnesImpliquees();
@endphp
<a href="{{ route('projets.show', $projet) }}"
   class="pt-carte {{ $projet->etat->estOuvert() ? '' : 'pt-carte--clos' }}"
   style="--pc: {{ $couleur }};"
   data-projet data-etat="{{ $projet->etat->value }}" data-mien="{{ $mienne ? 1 : 0 }}" data-retard="{{ $projet->estEnRetard() ? 1 : 0 }}">
    <div style="display: flex; align-items: flex-start; gap: 12px;">
        <span class="pt-picto" style="width: 40px; height: 40px; background: {{ $couleur }}1f; color: {{ $couleur }};">
            <x-icone :name="$projet->icone ?: 'folder'" :size="22" />
        </span>
        <div style="flex: 1; min-width: 0;">
            <div class="pt-carte__titre">{{ $projet->nom }}</div>
            @if($projet->description)<div class="pt-carte__desc">{{ $projet->description }}</div>@endif
        </div>
    </div>

    <div class="pt-carte__corps">
        <x-anneau :resume="$resume" :couleur="$couleur" :size="80" />
        <div style="flex: 1;">
            <div class="pt-compteurs">
                @foreach($statutsCarte as $statut)
                    @if($n = $resume['par_statut'][$statut->value] ?? 0)
                        <span class="pt-compteur">
                            <x-statut-carre :statut="$statut" :size="26" :tip="$n.' '.$statut->accorde($n)" />{{ $n }}
                        </span>
                    @endif
                @endforeach
                @if($resume['actives'] > 0 && $resume['terminees'] === $resume['actives'])
                    <span style="font-size: 12px; font-weight: 600; color: #2fb344;">Tout est terminé</span>
                @elseif($resume['total'] === 0)
                    <span style="font-size: 12px; color: #9aa0ac;">Aucune tâche</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Deux lignes fixes : les personnes seules sur la première, échéance à gauche et état à droite sur la seconde --}}
    <div class="pt-carte__pied">
        <div class="pt-carte__ligne">
            <x-avatars :users="$personnes" :size="28" :max="7" />
        </div>
        <div class="pt-carte__ligne pt-carte__ligne--ecarte">
            <x-echeance :item="$projet" :jours="true" />
            <span title="État du projet" class="pt-badge" style="background: {{ $projet->etat->couleur() }}1f; color: {{ $projet->etat->couleur() }};">
                <x-icone :name="$projet->etat->icone()" :size="13" /> {{ $projet->etat->label() }}
            </span>
        </div>
    </div>
</a>
