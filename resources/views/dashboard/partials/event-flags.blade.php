{{--
    Pastilles d'information d'un événement : participation, photos, objets à remettre.
    Variables : $event (relations users/objets chargées), $clr (couleur du type),
                $size (côté en px, 28 par défaut), $only (optionnel : restreint aux clés
                'participation' | 'photos' | 'objets').
    Les pastilles reprennent la couleur du type ; "objets" passe en rouge tant qu'un objet reste à faire.
--}}
@php
    $size = $size ?? 28;
    $only = $only ?? ['participation', 'photos', 'objets'];
    $participation = $event->participationFor(auth()->user());
    $objets = $event->objetsARemettre();

    $flags = collect([
        'participation' => $participation ? [
            'icon' => 'icon-user-check',
            'bg' => 'bg-'.$clr,
            'label' => $participation === 'team' ? 'Vous participez — via « Toute l\'équipe »' : 'Vous participez',
        ] : null,
        'photos' => $event->wantsPhotos() ? [
            'icon' => 'icon-camera',
            'bg' => 'bg-'.$clr,
            'label' => 'Des photos doivent être prises',
        ] : null,
        'objets' => $objets ? [
            'icon' => 'icon-cube',
            'bg' => $objets === 'pending' ? 'bg-danger' : 'bg-'.$clr,
            'label' => $objets === 'pending'
                ? 'Un ou plusieurs objets sont à remettre — certains restent à préparer'
                : 'Un ou plusieurs objets sont à remettre',
        ] : null,
    ])->only($only)->filter();
@endphp
@if($flags->isNotEmpty())
<span class="event-flags">
    @foreach($flags as $flag)
    <span class="event-flag {{ $flag['bg'] }}" style="width: {{ $size }}px; height: {{ $size }}px;" data-tip="{{ $flag['label'] }}" role="img" aria-label="{{ $flag['label'] }}">
        @include('dashboard.partials.'.$flag['icon'], ['size' => (int) round($size * 0.62)])
    </span>
    @endforeach
</span>
@endif
