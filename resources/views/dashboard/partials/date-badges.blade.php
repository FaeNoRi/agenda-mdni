
@foreach($notifications as $n)
    @php
        $isConge    = $n['type'] === 'conge';
        $isConflict = $n['conflict'] ?? false;
    @endphp
    <button type="button"
            class="btn btn-sm date-badge-btn d-inline-flex align-items-center gap-2 rounded-pill border px-2 py-1 shadow-sm
                   {{ $isConflict ? 'bg-red text-white date-badge-conflict' : 'bg-white' }}"
            style="font-size:.75rem;"
            data-bs-toggle="popover"
            data-bs-trigger="click"
            data-bs-placement="bottom"
            data-bs-html="true"
            data-bs-custom-class="date-badge-popover"
            data-bs-content="{{ $isConflict ? $n['summary'] . ' — <strong class=&quot;text-red&quot;>Attention</strong>, cette personne est aussi affectée à un événement ce jour.' : $n['summary'] }}"
            title="{{ $isConflict ? 'Conflit — ' . $n['user_name'] : $n['badge_label'] . ' — ' . $n['user_name'] }}">
        <span class="badge rounded-pill d-inline-flex align-items-center gap-1
                     {{ $isConflict ? 'bg-white text-red' : ($isConge ? 'bg-orange-lt' : 'bg-blue-lt') }}">
            <i class="ti {{ $isConflict ? 'ti-alert-triangle' : ($isConge ? 'ti-calendar-x' : 'ti-clock') }}" style="font-size:13px;"></i>
            {{ $isConflict ? 'Congé · Conflit' : $n['badge_label'] }}
        </span>
        <span class="{{ $isConflict ? 'text-white' : 'text-dark' }} fw-normal">{{ $n['user_name'] }}</span>
    </button>
@endforeach
