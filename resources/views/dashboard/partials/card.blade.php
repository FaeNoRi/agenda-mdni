@php
    $clr = $event->type_color ?? 'secondary';
@endphp


<div x-data="{ dropdownOpen: false }" @click="openEvenementDetails({{ $event->id }})" class="card flex flex-col h-full w-full border-l-4 border-{{ $clr }} shadow-sm hover:shadow-md transition cursor-pointer">
    {{-- HEADER --}}
    <div class="card-header relative overflow-visible flex justify-between items-center bg-{{ $clr }}-lt text-{{ $clr }}">
        {{-- Conteneur Alpine + marquee --}}
        <div x-data="marquee" x-init="init()" class="w-60 marquee">
            <div x-ref="inner" class="marquee__inner text-lg font-semibold">
                {{ $event->nom_event }}
            </div>
        </div>

        <div class="relative" @click.stop>
            <div class="dropdown" style="padding-left: 8px;">
                <button class="btn btn-md btn-outline-{{ $clr }} btn-icon" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="1" />
                        <circle cx="19" cy="12" r="1" />
                        <circle cx="5" cy="12" r="1" />
                    </svg>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button
                            type="button"
                            class="dropdown-item text-info"
                            onclick="openEvenementForm({{ $event->id }}, event)"
                        >
                            Modifier
                        </button>
                    </li>

                    <li>
                        <button
                            type="button"
                            class="dropdown-item text-success"
                            onclick="openEvenementDuplicateForm({{ $event->id }}, event)"
                        >
                            Copier
                        </button>
                    </li>
                    @if(auth()->user()->is_admin)
                    <li>
                        <button class="dropdown-item text-danger" onclick="confirmEvenementDelete({{ $event->id }}, '{{ addslashes($event->nom_event) }}')">
                            Supprimer
                        </button>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    {{-- BODY --}}
    <div class="card-body flex-1 p-4">
        {{-- Ligne badges --}}
        <div class="flex items-center mb-2 space-x-2">
            <span class="badge bg-{{ $clr }}-lt text-{{ $clr }}">
                {{ $event->type_event }}
            </span>
            {{-- Badge Règlement à faire, aligné sur la même ligne --}}
            @if($event->reglement === 'A faire')
            <span class="badge bg-danger text-white">
                Règlement à faire !
            </span>
            @endif
        </div>

        {{-- Commanditaire --}}
        <p class="text-sm text-gray-700 mb-2 break-words">
            {{ Str::limit($event->commanditaire_event, 60) }}
        </p>

        {{-- Date & heure --}}
        <p class="flex items-start text-sm text-gray-600 gap-2">
            <svg class="w-4 h-4 shrink-0 mt-0.5 text-gray-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-week">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                <path d="M16 3v4" />
                <path d="M8 3v4" />
                <path d="M4 11h16" />
                <path d="M7 14h.013" />
                <path d="M10.01 14h.005" />
                <path d="M13.01 14h.005" />
                <path d="M16.015 14h.005" />
                <path d="M13.015 17h.005" />
                <path d="M7.01 17h.005" />
                <path d="M10.01 17h.005" />
            </svg>
            @php
            $date = $event->date_heure_debut->translatedFormat('l d F Y');
            @endphp
            {{ mb_convert_case($date, MB_CASE_TITLE, 'UTF-8') }}<br>
            @if($event->date_heure_debut->format('H:i') == '00:00' && $event->date_heure_fin->format('H:i') == '00:00')
                Journée entière
            @else
                {{ $event->date_heure_debut->format('H:i') }} – {{ $event->date_heure_fin->format('H:i') }}
            @endif
        </p>

        {{-- Salle --}}
        @if($event->salles->isNotEmpty())
        <p class="flex items-start text-sm text-gray-600 gap-2 break-words">
            <svg class="w-4 h-4 shrink-0 mt-0.5 text-gray-500" xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
            </svg>
            {{ $event->users->pluck('name')->join(' - ') }}
        </p>

        <p class="flex items-start text-sm text-gray-600 gap-2 break-words">
            <svg class="w-4 h-4 shrink-0 mt-0.5 text-gray-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-door">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M14 12v.01" />
                <path d="M3 21h18" />
                <path d="M6 21v-16a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v16" />
            </svg>
            {{ $event->salles->pluck('nom_salle')->join(', ') }}
        </p>
        @endif
    </div>
</div>
