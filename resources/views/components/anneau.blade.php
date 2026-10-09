{{-- Anneau « terminées / total » d'un projet (les tâches annulées sont exclues du total). --}}
@props(['resume', 'couleur', 'size' => 80])
@php
    $fait = $resume['terminees'];
    $total = $resume['actives'];
    $rayon = $size / 2 - 6;
    $perimetre = 2 * M_PI * $rayon;
    $part = $total > 0 ? $fait / $total : 0;
@endphp
<div class="pt-anneau" style="width: {{ $size }}px; height: {{ $size }}px;" title="{{ $fait }} terminée(s) sur {{ $total }}">
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" aria-hidden="true">
        <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $rayon }}" fill="none" stroke="{{ $couleur }}1f" stroke-width="8" />
        <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $rayon }}" fill="none" stroke="{{ $couleur }}" stroke-width="8" stroke-linecap="round"
                stroke-dasharray="{{ round($perimetre * $part, 2) }} {{ round($perimetre, 2) }}" transform="rotate(-90 {{ $size / 2 }} {{ $size / 2 }})" />
    </svg>
    <div class="pt-anneau__texte">
        <span style="font-size: {{ (int) round($size * 0.25) }}px;" class="pt-anneau__nb">{{ $fait }}<span class="pt-anneau__sur">/{{ $total }}</span></span>
        <span class="pt-anneau__lib">terminées</span>
    </div>
</div>
