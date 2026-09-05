{{-- dashboard/partials/mini-card.blade.php --}}
@props(['type', 'data'])
@php
use Illuminate\Support\Carbon;

$bgColor = $type === 'conge' ? 'bg-red-100' : 'bg-yellow-100';
$icon = $type === 'conge' ? 'calendar-x' : 'clock';

$texte = '';
$badge = '';

if ($type === 'conge') {
    try {
        $start = Carbon::parse($data->start);
        $end = Carbon::parse($data->end);

        if ($start->equalTo($end)) {
            $texte = "Le " . $start->format('d/m');
            $badge = '<span class="badge bg-yellow text-white">Congé</span>';
        } else {
            $texte = "Du " . $start->format('d/m') . " au " . $end->format('d/m');
            $badge = '<span class="badge bg-yellow text-white">Congé</span>';
        }
    } catch (Exception $e) {
        $texte = "Congé (dates inconnues)";
        $badge = '<span class="badge bg-yellow text-white">Congé</span>';
    }
} else {
    try {
        $typeChgmt = $data->type_chgmt;

        $newStart = Carbon::parse($data->new_start);
        $newEnd = Carbon::parse($data->new_end);

        if ($typeChgmt === 'add') {
            $texte = $newStart->format('d/m') . " de " . $newStart->format('H:i') . " à " . $newEnd->format('H:i');
            $badge = '<span class="badge bg-blue text-white">Ajout</span>';

        } elseif ($typeChgmt === 'change') {
            $oldStart = Carbon::parse($data->old_start);
            $oldEnd = Carbon::parse($data->old_end);
            $badge = '<span class="badge bg-blue text-white">Changement</span>';
            $texte = "{$oldStart->format('d/m H:i')} à {$oldEnd->format('d/m H:i')} → {$newStart->format('d/m H:i')} à {$newEnd->format('d/m H:i')}";

        } else {
            $texte = "Changement le " . $newStart->format('d/m H:i');
            $badge = '<span class="badge bg-muted text-white">Inconnu</span>';
        }
    } catch (Exception $e) {
        $texte = "Changement (dates invalides)";
        $badge = '<span class="badge bg-muted text-white">Erreur</span>';
    }
}
@endphp

<div class="inline-block p-3 rounded-lg shadow-sm {{ $bgColor }}" style="min-width:170px;max-width:240px;">
    <div class="flex items-center space-x-2">
        <svg class="icon icon-tabler text-gray-700" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            @if ($icon === 'calendar-x')
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M13 21h-7a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v6.5" />
            <path d="M16 3v4" />
            <path d="M8 3v4" />
            <path d="M4 11h16" />
            <path d="M22 22l-5 -5" />
            <path d="M17 22l5 -5" />
            @else
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
            <path d="M12 7v5l3 3" />
            @endif
        </svg>

        <div class="font-semibold text-sm">
            {{ $data->user->name ?? 'Utilisateur inconnu' }}
        </div>

    </div>

    <div class="text-xs mt-1 whitespace-pre-line">
        {!! $badge !!} {!!$texte !!}
    </div>
</div>
