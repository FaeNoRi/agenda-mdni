{{-- Carré de statut (tâche ou état de projet) : couleur pleine + icône blanche, infobulle au survol. --}}
@props(['statut', 'size' => 24, 'tip' => null])
<span class="pt-carre" data-tip="{{ $tip ?? $statut->label() }}" style="width: {{ $size }}px; height: {{ $size }}px; background: {{ $statut->couleur() }};" role="img" aria-label="{{ $tip ?? $statut->label() }}">
    <x-icone :name="$statut->icone()" :size="(int) round($size * 0.6)" />
</span>
