@php
$grouped = $events->groupBy(fn($e) => $e->date_heure_debut->format('Y-m-d'))->sortKeys();
@endphp

<style>
    @keyframes dateBadgeBounce {
        0%        { transform: scale(1); }
        4%        { transform: scale(1.18); }
        8%        { transform: scale(0.92); }
        11%       { transform: scale(1.06); }
        14%, 100% { transform: scale(1); }
    }
    .date-badge-conflict {
        animation: dateBadgeBounce 3s ease infinite;
    }
    @media (prefers-reduced-motion: reduce) {
        .date-badge-conflict {
            animation: none;
        }
    }
    .date-badge-popover .popover-body {
        text-align: center;
    }
</style>

@if($events->isEmpty())
<p class="text-center text-muted">Aucun événement à afficher.</p>
@else
@foreach($grouped as $date => $dayEvents)
<section class="mb-12">
    @php
        $today = now()->toDateString();

        $hasActiveDateFilter = request()->filled('from')
            && request()->filled('to')
            && request('from') === request('to')
            && request('from') !== $today;

        $dayUserIds = $dayEvents->pluck('users')->flatten()->pluck('id')->unique();

        $dayNotifications = ($notifications ?? collect())
            ->filter(fn($n) => in_array($date, $n['dates'], true))
            ->map(function ($n) use ($dayUserIds) {
                $n['conflict'] = $n['type'] === 'conge'
                    && $n['user_id']
                    && $dayUserIds->contains($n['user_id']);
                return $n;
            });
    @endphp

    <div class="d-flex align-items-center flex-wrap gap-2 mb-4">
        <h2 class="text-2xl font-semibold mb-0">
            {{ ucfirst(\Carbon\Carbon::parse($date)->translatedFormat('l d F Y')) }}
        </h2>

        <span class="badge bg-success-lt text-success js-filter-active-badge d-none" data-section-date="{{ $date }}">
            Filtrage actif
        </span>

        @include('dashboard.partials.date-badges', ['notifications' => $dayNotifications])
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="eventsGrid-{{ $date }}">
        @foreach($dayEvents as $event)
        <div class="col">
            @include('dashboard.partials.card', ['event' => $event])
        </div>
        @endforeach
    </div>
</section>
@endforeach
@endif
