@php
    $clr = $event->type_color ?? 'secondary';
@endphp

{{-- --- HEADER --- --}}
<div class="modal-header">
    <h5 class="modal-title">Détail événement</h5>
    <!-- Croix de fermeture (Bootstrap) -->
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
</div>

{{-- --- BODY dynamique --- --}}
<div class="modal-body">
    {{-- Bandeau titre & type pleine largeur --}}

    <div class="w-full p-3 mb-4 rounded bg-{{ $clr }}-lt">
        <span class="badge bg-{{ $clr }}-lt text-{{ $clr }} me-2 align-text-top">
            ID:{{ $event->id }}
        </span>

        <span class="badge bg-{{ $clr }} text-white me-2 align-text-top">
            {{ $event->type_event }}
        </span>

        <span class="h4 mb-0 text-{{ $clr }}">
            {{ $event->nom_event }}
        </span>
    </div>

    {{-- Réduire l’écart en mettant mb-2 ici au lieu mb-4 --}}
    <div class="row gx-4 mb-2">
        <div class="col-md-6">

            <p class="mb-1"><strong>Client</strong></p>
            <p class="mb-0"> {{ $event->commanditaire_event }} </p>

        </div>
        <div class="col-md-6">
            <p class="mb-1"><strong>Participants</strong></p>
            <p class="mb-0">{{ $event->nbpart ?: '–' }}</p>
        </div>
    </div>

    <div class="row gx-4 mb-4">
        <div class="col-md-6">
            <p class="mb-1"><strong>Animateur{{ $event->users->count()>1?'s':'' }}</strong></p>
            <p class="mb-0">{{ $event->users->pluck('name')->join(', ') ?: '–' }}</p>
        </div>
        <div class="col-md-6">
            <p class="mb-1"><strong>Salle{{ $event->salles->count()>1?'s':'' }}</strong></p>
            <p class="mb-0">{{ $event->salles->pluck('nom_salle')->join(', ') ?: 'Aucune' }}</p>
        </div>
    </div>

    <div class="row gx-4 mb-4">
        <div class="col-md-3">
            <p class="mb-1"><strong>Date</strong></p>
            {{-- @php
            $date = $event->date_heure_debut->translatedFormat('l d F Y');
            @endphp --}}
            @php
                if ($event->date_heure_debut->format('Y-m-d') === $event->date_heure_fin->format('Y-m-d')) {
                    $date = $event->date_heure_debut->translatedFormat('d F Y');
                } else {
                    $date = $event->date_heure_debut->translatedFormat('l d F Y')
                        . ' → '
                        . $event->date_heure_fin->translatedFormat('l d F Y');
                }
            @endphp


            <p class="mb-0">{{ mb_convert_case($date, MB_CASE_TITLE, 'UTF-8') }}</p>
                @if($event->date_heure_debut->format('H:i') == '00:00' && $event->date_heure_fin->format('H:i') == '00:00')
                    Journée entière
                @else
                    {{ $event->date_heure_debut->format('H:i') }} – {{ $event->date_heure_fin->format('H:i') }}
                @endif
        </div>
        <div class="row col-md-9" style="margin:auto;">
            @foreach([
            'Devis' => ['v'=>$event->devis, 'n'=>$event->numdevis],
            'Facture' => ['v'=>$event->facture, 'n'=>$event->numfact],
            'Règlement' => ['v'=>$event->reglement,'n'=>$event->num_reglement],
            ] as $label => $info)
            <div class="col-auto">
                <span class="badge bg-{{ $clr }}-lt text-{{ $clr }}">
                    {{ $label }} : {{ $info['v'] }} @if($info['n'] != '-' && $info['n'] != '') (#{{ $info['n'] }})@endif
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @if($event->desc_event)
    <div class="mb-4">
        <p class="mb-1"><strong>Description</strong></p>
        <p class="text-muted">{{ $event->desc_event }}</p>
    </div>
    @endif

    <div class="row gx-4 mb-2">
        <div class="col-md-6">
            <div>
                <p class="mb-2"><strong>Matériel{{ $event->materiels->count()>1?'s':'' }} spécifique</strong></p>
                <div class="d-flex flex-wrap gap-2">
                    @if($event->objets->isNotEmpty())
                    @foreach($event->materiels as $m)
                    <span class="badge bg-{{ $clr }}-lt text-{{ $clr }}">
                        {{ $m->nom_mat }} × {{ $m->pivot->quantite }}
                    </span>
                    @endforeach
                    @else
                    <span class="badge bg-{{ $clr }}-lt text-{{ $clr }}">
                        Aucun
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div>
                <p class="mb-2"><strong>Objet{{ $event->objets->count()>1?'s':'' }} à remettre</strong></p>
                <div class="d-flex flex-wrap gap-2">
                    @if($event->objets->isNotEmpty())
                    @foreach($event->objets as $o)
                    <span class="badge bg-{{ $clr }}-lt text-{{ $clr }}">
                        {{ $o->nom_obj }} - {{ $o->pivot->etat }}
                    </span>
                    @endforeach
                    @else
                    <span class="badge bg-{{ $clr }}-lt text-{{ $clr }}">
                        Aucun
                    </span>
                    @endif
                </div>
            </div>

        </div>
    </div>

</div>

{{-- --- FOOTER fixe --- --}}
<div class="modal-footer">
    <p class="text-muted small" style="margin: auto;padding-left: 85px;">
        Dernière modification par : <b>{{ $event->auteur ?? 'Inconnu' }}</b>, le {{ $event->updated_at->translatedFormat('d F Y') ?? 'Inconnu' }}
    </p>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
</div>
