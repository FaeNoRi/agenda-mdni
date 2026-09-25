@php
$color = $color ?? '#888888';
$mode = $mode ?? 'created';
@endphp

<div style="font-family: 'AvenirNextCondensed-Regular', 'Avenir Next Condensed', 'Segoe UI', 'Helvetica Neue', 'Arial', sans-serif; max-width:600px; margin:auto; border:1px solid #ddd; padding:20px; border-radius:8px;">
    @if($mode === 'updated')
    <p style="background:#fff3cd; color:#664d03; padding:8px 12px; border-radius:6px; margin:0 0 15px;"><strong>Événement modifié</strong> — la pièce jointe met à jour l'événement déjà présent dans votre agenda.</p>
    @elseif($mode === 'cancelled')
    <p style="background:#f8d7da; color:#842029; padding:8px 12px; border-radius:6px; margin:0 0 15px;"><strong>Événement annulé</strong> — la pièce jointe le retire de votre agenda.</p>
    @endif
    <h2 style="color:{{ $color }}; margin-bottom:5px;">{{ $event->nom_event }}</h2>
    <span style="background-color:{{ $color }}; color:#fff; border-radius:6px; font-size:13px; font-weight:600;">
        &nbsp;{{ $event->type_event }}&nbsp;
    </span>

    <hr style="margin:20px 0;">

    <p><strong>Date :</strong><br>
        {{ \Carbon\Carbon::parse($event->date_heure_debut)->translatedFormat('l d F Y') }}<br>
        {{ \Carbon\Carbon::parse($event->date_heure_debut)->format('H:i') }} – {{ \Carbon\Carbon::parse($event->date_heure_fin)->format('H:i') }}</p>

    <p><strong>Animateur(s) :</strong><br>
        {{ $event->users->pluck('name')->join(', ') ?: 'Non précisé' }}</p>

    <p><strong>Salle(s) :</strong><br>
        {{ $event->salles->pluck('nom_salle')->join(', ') ?: 'Aucune' }}</p>

    @if(!empty($event->desc_event))
    <p><strong>Description :</strong><br>
        <span style="color:#666;">{{ $event->desc_event }}</span></p>
    @endif

    <hr style="margin-top:30px;">
    @if($mode === 'cancelled')
    <p style="font-size:12px; color:#999;">Ouvrez la pièce jointe (.ics) pour retirer cet événement de votre agenda.</p>
    @elseif($mode === 'updated')
    <p style="font-size:12px; color:#999;">Ouvrez la pièce jointe (.ics) pour mettre à jour cet événement dans votre agenda.</p>
    @else
    <p style="font-size:12px; color:#999;">Ajoutez cet événement à votre agenda via la pièce jointe (.ics)</p>
    @endif
</div>
