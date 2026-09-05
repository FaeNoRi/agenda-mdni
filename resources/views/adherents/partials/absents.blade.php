@php
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

@forelse($absents as $adh)
  @php
    $clr   = $colors[$adh->situation_adh] ?? 'gray';
    $photo = $adh->photo_adh ? asset($adh->photo_adh) : asset('assets/img/default-avatar.png');
    $blob  = mb_strtolower($adh->nom_adh.' '.$adh->situation_adh.' '.$adh->dom_adh, 'UTF-8');
  @endphp

  <li class="list-group-item adh-card" role="button"
      data-id="{{ $adh->id }}"
      data-nom="{{ $adh->nom_adh }}"
      data-situation="{{ $adh->situation_adh }}"
      data-dom="{{ $adh->dom_adh }}"
      data-date="{{ $adh->date_adh ? $adh->date_adh->format('Y-m-d') : '' }}"
      data-photo="{{ $photo }}"
      data-present="0"
      data-cgu="{{ $adh->isCGU ? 1 : 0 }}"
      data-color="{{ $clr }}"
      data-searchblob="{{ $blob }}">
    <div class="d-flex align-items-center py-2 gap-2">
      <span class="avatar flex-shrink-0" style="background-image:url('{{ $photo }}'); width:3rem; height:3rem;"></span>
      <div class="min-w-0">
        <div class="fw-semibold text-truncate">{{ $adh->nom_adh }}</div>
        <div class="text-muted small text-truncate">
          <span class="badge bg-{{ $clr }}-lt text-{{ $clr }}">{{ $adh->situation_adh }}</span>
          — {{ $adh->dom_adh }}
        </div>
      </div>
    </div>
  </li>
@empty
  <li class="list-group-item text-muted">Aucun absent 🎉</li>
@endforelse
