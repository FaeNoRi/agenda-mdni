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

@foreach($presents as $adh)
  @php
    $clr = $colors[$adh->situation_adh] ?? 'gray';
    $isExpired = $adh->date_adh && Carbon::today()->gt($adh->date_adh);
    $borderClass = $isExpired ? 'border-danger-thick' : 'border-success-thick';
  @endphp
  <div class="col adh-card"
       data-id="{{ $adh->id }}"
       data-nom="{{ $adh->nom_adh }}"
       data-situation="{{ $adh->situation_adh }}"
       data-dom="{{ $adh->dom_adh }}"
       data-date="{{ $adh->date_adh ? $adh->date_adh->format('Y-m-d') : '' }}"
       data-photo="{{ $adh->photo_adh ? asset($adh->photo_adh) : asset('assets/img/default-avatar.png') }}"
       data-present="1"
       data-cgu="{{ $adh->isCGU ? 1 : 0 }}"
       data-color="{{ $clr }}">
    <div class="card text-center shadow-sm">
      <div class="card-body p-3">
        <img src="{{ asset($adh->photo_adh ?? 'assets/img/default-avatar.png') }}"
             class="avatar avatar-xl mb-2 rounded-circle {{ $borderClass }}" alt="photo">
        <h3 class="fw-bold mb-0">{{ $adh->nom_adh }}</h3>
        <small class="text-muted">
          <span class="badge bg-{{ $clr }}-lt text-{{ $clr }}">{{ $adh->situation_adh }}</span>
          - {{ $adh->dom_adh }}
        </small>
      </div>
    </div>
  </div>
@endforeach
