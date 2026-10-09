{{--
    Pile d'avatars à initiales. Seul l'avatar de l'utilisateur connecté est en couleur (thème, ton clair) ;
    les autres sont en gris, pour repérer d'un coup d'œil ce qui nous concerne.
--}}
@props(['users', 'size' => 26, 'max' => 4])
@php
    $users = collect($users)->values();
    $visibles = $users->take($max);
    $reste = $users->count() - $visibles->count();
    $initiales = function ($nom) {
        $mots = preg_split('/\s+/', trim((string) $nom)) ?: [];
        $i = count($mots) > 1
            ? mb_substr($mots[0], 0, 1).mb_substr($mots[1], 0, 1)
            : mb_substr($mots[0] ?? '?', 0, 2);

        return mb_strtoupper($i);
    };
@endphp
<span class="pt-avatars">
    @foreach($visibles as $u)
        <span class="pt-avatar {{ $u->id === auth()->id() ? 'pt-avatar--moi' : '' }}" title="{{ $u->name }}" style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ (int) round($size * 0.38) }}px;">{{ $initiales($u->name) }}</span>
    @endforeach
    @if($reste > 0)
        <span class="pt-avatar" title="{{ $users->slice($max)->pluck('name')->join(', ') }}" style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ (int) round($size * 0.38) }}px;">+{{ $reste }}</span>
    @endif
</span>
