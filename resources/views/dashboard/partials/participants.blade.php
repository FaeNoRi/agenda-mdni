@php
    $me = auth()->user();
    $participation = $event->participationFor($me);
    $sep = $sep ?? ' - ';

    $parts = $event->users->map(function ($u) use ($participation, $me) {
        $active = ($participation === 'named' && $u->id === $me->id)
            || ($participation === 'team' && $u->id === 0);

        if (!$active) {
            return e($u->name);
        }

        return '<span class="badge bg-success-lt text-success participant-me">'
            . view('dashboard.partials.icon-user-check')->render()
            . ' ' . e($u->name) . '</span>';
    });
@endphp
@if($parts->isEmpty())–@else{!! $parts->implode(e($sep)) !!}@endif
