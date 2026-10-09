@include('projets._styles')

@php
    $couleur = $projet->couleur ?: '#4299e1';
    $etat = $projet->etat;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2 w-full">
            <h2 class="page-title text-xl font-semibold">{{ __('Projets & tâches') }}</h2>
            @include('projets._onglets', ['actif' => 'projets'])
        </div>
    </x-slot>

    <div class="container mx-auto py-4 px-4">
        <a href="{{ route('projets.index') }}" class="d-inline-flex align-items-center gap-1 mb-3" style="font-size: 12.5px; font-weight: 600;">
            <x-icone name="arrow-left" :size="14" /> Tous les projets
        </a>

        <div class="pt-fiche">
            <div class="pt-fiche__entete" style="background: {{ $couleur }}1f;">
                <span class="pt-picto" style="width: 44px; height: 44px; background: #fff; color: {{ $couleur }};">
                    <x-icone :name="$projet->icone ?: 'folder'" :size="24" />
                </span>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 19px; font-weight: 700; color: {{ $couleur }};">{{ $projet->nom }}</div>
                    @if($projet->description)<div style="font-size: 12.5px; color: #495057;">{{ $projet->description }}</div>@endif
                </div>
                <span class="pt-badge" style="background: {{ $etat->couleur() }}; color: #fff;">
                    <x-icone :name="$etat->icone()" :size="13" /> {{ $etat->label() }}
                </span>
            </div>

            @if($projet->raison && $etat->exigeRaison())
                <div class="mb-3" style="padding: 8px 12px; border-radius: 8px; background: {{ $etat->couleur() }}1f; color: {{ $etat->couleur() }}; font-size: 12.5px;">
                    <x-icone name="message-2" :size="14" /> {{ $projet->raison }}
                </div>
            @endif

            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 22px;">
                <x-anneau :resume="$resume" :couleur="$couleur" :size="92" />
                <div style="flex: 1; min-width: 220px;">
                    <div class="pt-compteurs">
                        @foreach($statutsCarte as $statut)
                            @if($n = $resume['par_statut'][$statut->value] ?? 0)
                                <span class="pt-compteur"><x-statut-carre :statut="$statut" :size="28" :tip="$n.' '.$statut->accorde($n)" />{{ $n }}</span>
                            @endif
                        @endforeach
                    </div>
                    <div style="margin-top: 8px; font-size: 12.5px; color: #495057;">{{ $resume['texte'] }}</div>

                    <div class="pt-meta">
                        <span class="pt-meta__k">Référent{{ $referents->count() > 1 ? 's' : '' }}</span>
                        <span class="pt-meta__k">Impliqués</span>
                        <span class="pt-meta__k">Date limite</span>
                        <span>
                            @forelse($referents as $u)
                                <x-avatars :users="[$u]" :size="26" /> <b>{{ $u->name }}</b>@if(!$loop->last), @endif
                            @empty
                                <span style="color: #9aa0ac;">—</span>
                            @endforelse
                        </span>
                        <span>
                            @if($impliques->isEmpty())<span style="color: #9aa0ac;">—</span>@else<x-avatars :users="$impliques" :size="26" :max="6" />@endif
                        </span>
                        <span><x-echeance :item="$projet" :jours="true" /></span>
                    </div>

                    <div style="margin-top: 8px; font-size: 11.5px; color: #9aa0ac;">
                        Créé{{ $projet->createur ? ' par '.$projet->createur->name : '' }} le {{ $projet->created_at->format('d/m/Y') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-2">
            <span style="font-weight: 700; font-size: 15px;">Tâches</span>
            @can('create', \App\Models\Tache::class)
                <button type="button" class="btn btn-sm btn-primary" data-tache-nouvelle data-projet="{{ $projet->id }}">+ Ajouter une tâche</button>
            @endcan
        </div>
        @if($taches->isEmpty())
            <div class="pt-vide">Aucune tâche dans ce projet.</div>
        @else
            <div class="pt-grille-mini">
                @foreach($taches as $tache)
                    @include('taches._mini', ['tache' => $tache])
                @endforeach
            </div>
        @endif

        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <div class="pt-panneau">
                    <h3><x-icone name="messages" :size="16" /> Commentaires</h3>
                    @forelse($projet->commentaires as $commentaire)
                        <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                            @if($commentaire->user)<x-avatars :users="[$commentaire->user]" :size="26" />@endif
                            <div style="flex: 1; padding: 6px 9px; border-radius: 0 10px 10px 10px; background: #f6f8fb; font-size: 12px;">
                                <b>{{ $commentaire->user?->name ?? 'Ancien utilisateur' }}</b>
                                <span style="color: #9aa0ac;">{{ $commentaire->created_at->format('d/m/Y') }}</span><br>
                                {{ $commentaire->contenu }}
                            </div>
                        </div>
                    @empty
                        <div style="font-size: 12px; color: #9aa0ac;">Aucun commentaire.</div>
                    @endforelse
                </div>
            </div>
            <div class="col-md-6">
                <div class="pt-panneau">
                    <h3><x-icone name="paperclip" :size="16" /> Liens et documents utiles</h3>
                    @forelse($projet->liens as $lien)
                        <div style="margin-bottom: 6px; font-size: 12.5px;">
                            <a href="{{ $lien->url }}" target="_blank" rel="noopener noreferrer">{{ $lien->libelle ?: $lien->url }}</a>
                        </div>
                    @empty
                        <div style="font-size: 12px; color: #9aa0ac;">Aucun lien.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @include('taches._modal-host')
</x-app-layout>
