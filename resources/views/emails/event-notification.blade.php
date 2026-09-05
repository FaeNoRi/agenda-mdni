@php
$color = $color ?? '#888888';
@endphp

<div style="font-family: 'AvenirNextCondensed-Regular', 'Avenir Next Condensed', 'Segoe UI', 'Helvetica Neue', 'Arial', sans-serif; max-width:600px; margin:auto; border:1px solid #ddd; padding:20px; border-radius:8px;">
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
    <p style="font-size:12px; color:#999;">Ajoutez cet événement à votre agenda via la pièce jointe (.ics)</p>
</div>
