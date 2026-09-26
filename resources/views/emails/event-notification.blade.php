@php
$color = $color ?? '#888888';
$mode = $mode ?? 'created';
$changes = $changes ?? [];

// [fond, texte, accent, titre, précision, pied de mail]
[$bg, $fg, $accent, $bannerTitle, $bannerText, $footer] = match ($mode) {
    'updated' => ['#fff3cd', '#664d03', '#ffc107', 'Événement modifié',
        count($changes) ? 'les changements sont indiqués ci-dessous.' : 'consultez les informations ci-dessous.',
        'Ouvrez la pièce jointe pour mettre à jour votre agenda.'],
    'added' => ['#d1e7dd', '#0f5132', '#198754', 'Vous avez été ajouté(e) à cet événement',
        'il vient de vous être affecté.',
        'Ouvrez la pièce jointe pour l\'ajouter à votre agenda.'],
    'removed' => ['#e9ecef', '#343a40', '#6c757d', 'Vous n\'êtes plus affecté(e) à cet événement',
        'il reste programmé pour les autres participants, inutile de vous y rendre.',
        'Ouvrez la pièce jointe pour le retirer de votre agenda.'],
    'cancelled' => ['#f8d7da', '#842029', '#dc3545', 'Événement annulé',
        'il n\'aura pas lieu.',
        'Ouvrez la pièce jointe pour le retirer de votre agenda.'],
    'reinstated' => ['#d1e7dd', '#0f5132', '#198754', 'Événement rétabli',
        'il aura bien lieu.',
        'Ouvrez la pièce jointe pour le remettre dans votre agenda.'],
    default => ['#d1e7dd', '#0f5132', '#198754', 'Nouvel événement',
        'vous y êtes affecté(e).',
        'Ouvrez la pièce jointe pour l\'ajouter à votre agenda.'],
};

$inactive = in_array($mode, ['cancelled', 'removed'], true);
$titleColor = $inactive ? '#6c757d' : $color;
$start = \Carbon\Carbon::parse($event->date_heure_debut)->locale('fr');
$end = \Carbon\Carbon::parse($event->date_heure_fin)->locale('fr');
$day = fn ($d) => mb_convert_case($d->translatedFormat('l d F Y'), MB_CASE_TITLE, 'UTF-8');
$dateText = $start->isSameDay($end) ? $day($start) : $day($start) . ' → ' . $day($end);
$timeText = ($start->format('H:i') === '00:00' && $end->format('H:i') === '00:00')
    ? 'Journée entière'
    : $start->format('H:i') . ' – ' . $end->format('H:i');
$font = "'Segoe UI', 'Helvetica Neue', Arial, sans-serif";
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f5f7; padding:24px 12px; font-family:{!! $font !!};">
<tr><td align="center">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:560px; background:#ffffff; border:1px solid #e5e7eb; border-radius:12px;">

    <tr><td style="padding:28px 28px 16px; font-size:15px; color:#212529;">Bonjour,</td></tr>

    <tr><td style="padding:0 28px 22px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr><td style="background:{{ $bg }}; color:{{ $fg }}; border-left:4px solid {{ $accent }}; border-radius:6px; padding:12px 16px; font-size:14px; line-height:1.5;">
            <strong style="display:block; font-size:15px;">{{ $bannerTitle }}</strong>
            {{ $bannerText }}
        </td></tr>
        </table>
    </td></tr>

    <tr><td style="padding:0 28px;">
        <h2 style="margin:0 0 8px; font-size:22px; line-height:1.3; font-weight:600; color:{{ $titleColor }};{{ $mode === 'cancelled' ? ' text-decoration:line-through;' : '' }}">{{ $event->nom_event }}</h2>
        <span style="display:inline-block; background:{{ $titleColor }}; color:#ffffff; border-radius:6px; font-size:12px; font-weight:600; padding:3px 10px;">{{ $event->type_event }}</span>
    </td></tr>

    @if($mode === 'updated' && count($changes))
    <tr><td style="padding:20px 28px 0;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb; border-radius:8px; font-size:14px;">
            @foreach($changes as $row)
            <tr>
                <td style="padding:10px 14px; width:110px; color:#6c757d; vertical-align:top;{{ !$loop->first ? ' border-top:1px solid #e5e7eb;' : '' }}">{{ $row['label'] }}</td>
                <td style="padding:10px 14px; color:#212529; vertical-align:top;{{ !$loop->first ? ' border-top:1px solid #e5e7eb;' : '' }}">
                    <span style="color:#9ca3af; text-decoration:line-through;">{{ $row['old'] }}</span><br>
                    <strong>{{ $row['new'] }}</strong>
                </td>
            </tr>
            @endforeach
        </table>
    </td></tr>
    @endif

    <tr><td style="padding:22px 28px 0;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #e5e7eb; font-size:14px; color:#212529; {{ $inactive ? 'opacity:.75;' : '' }}">
            <tr><td style="padding:16px 0 4px; font-weight:600;">Date</td></tr>
            <tr><td style="padding:0 0 14px; color:#495057;">{{ $dateText }}<br>{{ $timeText }}</td></tr>
            <tr><td style="padding:0 0 4px; font-weight:600;">Animateur(s)</td></tr>
            <tr><td style="padding:0 0 14px; color:#495057;">{{ $event->users->pluck('name')->join(', ') ?: 'Non précisé' }}</td></tr>
            <tr><td style="padding:0 0 4px; font-weight:600;">Salle(s)</td></tr>
            <tr><td style="padding:0 0 14px; color:#495057;">{{ $event->salles->pluck('nom_salle')->join(', ') ?: 'Aucune' }}</td></tr>
            @if(!empty($event->desc_event))
            <tr><td style="padding:0 0 4px; font-weight:600;">Description</td></tr>
            <tr><td style="padding:0 0 14px; color:#6c757d;">{!! nl2br(e($event->desc_event)) !!}</td></tr>
            @endif
        </table>
    </td></tr>

    <tr><td style="padding:6px 28px 26px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #e5e7eb;">
        <tr><td style="padding-top:14px; font-size:12px; line-height:1.6; color:#9ca3af;">
            {{ $footer }}<br>
            <span style="display:inline-block; margin-top:6px; border:1px solid #d1d5db; border-radius:6px; padding:3px 9px; color:#6b7280;">Pièce jointe : event.ics</span>
        </td></tr>
        </table>
    </td></tr>

</table>
</td></tr>
</table>
