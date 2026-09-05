@php
  use Carbon\Carbon;
  $colors = [
    'MDNI' => 'info',
    'Étudiant' => 'success',
    'Bénévole' => 'secondary',
    'Porteur de projet' => 'warning',
    'Chef d\'entreprise' => 'danger',
    'Salarié' => 'secondary',
    'Particulier' => 'warning',
  ];
@endphp

@foreach($equipe as $adh)
  @php
    $clr = $colors[$adh->situation_adh] ?? 'gray';
    $isExpired = $adh->date_adh && Carbon::today()->gt($adh->date_adh);
    $borderClass = $isExpired ? 'border-danger-thick' : ($adh->isPresent ? 'border-success-thick' : 'border-secondary-thick');
  @endphp
  <div class="col adh-card"
       data-id="{{ $adh->id }}"
       data-nom="{{ $adh->nom_adh }}"
       data-situation="{{ $adh->situation_adh }}"
       data-dom="{{ $adh->dom_adh }}"
       data-date="{{ $adh->date_adh ? $adh->date_adh->format('Y-m-d') : '' }}"
       data-photo="{{ $adh->photo_adh ? asset($adh->photo_adh) : asset('assets/img/default-avatar.png') }}"
       data-present="{{ $adh->isPresent ? 1 : 0 }}"
       data-cgu="{{ $adh->isCGU ? 1 : 0 }}"
       data-color="{{ $clr }}">
    <div class="card shadow-sm h-100">
      <div class="card-body d-flex align-items-center">
        <img src="{{ asset($adh->photo_adh ?? 'assets/img/default-avatar.png') }}"
             class="rounded-circle {{ $borderClass }}"
             style="width: 5rem; height: 5rem; flex-shrink: 0; object-fit: cover;" alt="photo">
        <div class="text-start ms-3 flex-grow-1">
          <div class="fw-bold">{{ $adh->nom_adh }}</div>
          <div class="text-muted small">
            <span class="badge bg-{{ $clr }}-lt text-{{ $clr }}">{{ $adh->situation_adh }}</span>
            – {{ $adh->dom_adh }}
          </div>
        </div>
      </div>
    </div>
  </div>
@endforeach
