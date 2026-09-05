<!-- resources/views/dashboard.blade.php -->
<style>
    .offcanvas-backdrop {
        opacity: 0.5;
        pointer-events: none;
    }

    .marquee {
        position: relative;
        overflow: hidden;
        white-space: nowrap;
    }

    .marquee__inner {
        display: inline-block;
        transform: translateX(0);
    }

    .marquee--active .marquee__inner {
        animation: marqueeAnim 3s linear infinite;
        animation-delay: 0s;
        animation-fill-mode: both;
    }

    @keyframes marqueeAnim {

        0%,
        15% {
            transform: translateX(0);
        }

        75%,
        100% {
            transform: translateX(calc(-1 * var(--scroll-offset)));
        }
    }

    .marquee:not(:hover) .marquee__inner {
        animation-play-state: paused;
    }

    .marquee:hover .marquee__inner {
        animation-play-state: running;
    }

    body.modal-open #btnOpenFilters { display: none; }

    .fc .fc-event.fc-event-outline {
        border-top: 0 !important;
        border-right: 0 !important;
        border-bottom: 0 !important;
        border-left-width: 4px !important;
        border-left-style: solid !important;
        box-shadow: none !important;
        font-weight: 600;
    }

    .fc .fc-event.fc-event-outline .fc-event-main {
        color: #374151 !important;
        background-color: transparent !important;
    }

    .fc .fc-event.fc-event-outline .fc-event-time,
    .fc .fc-event.fc-event-outline .fc-event-title {
        color: #374151 !important;
    }

    .fc .fc-timegrid-event.fc-event-outline,
    .fc .fc-daygrid-event.fc-event-outline {
        border-radius: 4px;
    }
</style>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2 w-full">
            <h2 class="page-title text-xl font-semibold">
                {{ __('Tableau de bord') }}
            </h2>

            <div class="flex items-center gap-2">
                <button id="btnAddEvent" type="button" class="btn btn-primary">
                    Ajouter un événement
                </button>

                <div class="btn-group" role="group" aria-label="Switch view">
                    <button id="btn-cards-view" class="btn btn-outline-primary active" onclick="switchView('cards')" title="Vue cartes">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-layout-grid mx-auto">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M4 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                            <path d="M14 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                            <path d="M4 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                            <path d="M14 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                        </svg>
                    </button>
                    <button id="btn-calendar-view" class="btn btn-outline-primary" onclick="switchView('calendar')" title="Vue calendrier">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-clock mx-auto">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M10.5 21h-4.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v3" />
                            <path d="M16 3v4" />
                            <path d="M8 3v4" />
                            <path d="M4 11h10" />
                            <path d="M18 18m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                            <path d="M18 16.5v1.5l.5 .5" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </x-slot>
{{--
    <div class="container-xl px-4">
        @include('dashboard.partials.notif-ticker', ['notifications' => $notifications])
    </div> --}}


    <div class="container mx-auto py-4 px-4">
        <div id="cardsContainer">
            @include('dashboard.partials.cards', ['events' => $events])
        </div>
        <div id="calendarContainer" class="d-none">
            <div id="calendar"></div>
        </div>
    </div>

    <div id="evenementViewModal" class="modal modal-blur fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content" id="evenementDetailContainer">
                Chargement…
            </div>
        </div>
    </div>

    {{-- Modal édition / création --}}
    <div class="modal modal-blur fade" id="evenementModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title mb-0">
                        <span id="evenementModalTitle">Événement</span>
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body position-relative">

                    <div id="formLoader" class="position-absolute inset-0 bg-white bg-opacity-75 d-none align-items-center justify-content-center" + style="z-index:1000; display:none;">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>

                    <div id="evenementFormContainer">
                        Chargement.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de suppression --}}
    <div class="modal modal-blur fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="POST" id="deleteEvenementForm">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="source" value="dashboard">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmation de suppression</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p id="deleteModalText">Voulez-vous vraiment supprimer cet événement ?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger ms-auto">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <button id="btnOpenFilters" type="button"
        class="btn btn-primary position-fixed h2"
        style="bottom:1rem; right:1rem; z-index:1040;"
        aria-label="Ouvrir les filtres">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="icon icon-tabler icons-tabler-outline icon-tabler-filter">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M4 4h16v2.172a2 2 0 0 1 -.586 1.414l-4.414 4.414v7l-6 2v-8.5l-4.48 -4.928a2 2 0 0 1 -.52 -1.345v-2.227z" />
        </svg> Filtres
    </button>

    <!-- Offcanvas Filters -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasFilters" aria-labelledby="offcanvasFiltersLabel" data-bs-backdrop="false" data-bs-scroll="true" style="box-shadow: -10px 0px 10px -3px rgb(0 0 0 / .1);">
        <div class="offcanvas-header">
            <h4 id="offcanvasFiltersLabel">Filtres</h4>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
        </div>
        <div class="offcanvas-body">


            {{-- 1) Filtre Période --}}
            @php
                $fromFilter = request('from', now()->toDateString());
                $toFilter = request('to', $fromFilter);

                // On affiche le champ "to" uniquement si sa valeur est différente de "from"
                $showToFilter = $toFilter !== $fromFilter;
            @endphp

            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label mb-0" for="filter-from">Date</label>

                    <button
                        type="button"
                        id="toggle-filter-to"
                        class="btn btn-link btn-sm px-0 text-muted"
                        aria-expanded="{{ $showToFilter ? 'true' : 'false' }}"
                    >
                        {{ $showToFilter ? 'Retirer la période' : 'Ajouter une période' }}
                    </button>
                </div>

                <input
                    type="date"
                    id="filter-from"
                    class="form-control"
                    value="{{ $fromFilter }}"
                />

                <div id="filter-to-wrapper" class="mt-2 {{ $showToFilter ? '' : 'd-none' }}">
                    <label class="form-label" for="filter-to">Date de fin</label>

                    <input
                        type="date"
                        id="filter-to"
                        class="form-control"
                        value="{{ $toFilter }}"
                    />
                </div>
            </div>

            {{-- 2) Filtre Type --}}
            <div class="mb-3">
                <label class="form-label">Type</label>
                <select id="filter-type" class="form-select" name="type[]" multiple size="5">
                    {{-- <option value="">Tout types</option> --}}
                    @foreach($typesDisponibles as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 3) Filtre Salle --}}
            <div class="mb-3">
                <label class="form-label">Salle</label>
                <select id="filter-salle" class="form-select" name="salle[]" multiple size="5">
                    {{-- <option value="">Toutes salles</option> --}}
                    @foreach($sallesDisponibles as $salle)
                    <option value="{{ $salle->id }}">{{ $salle->nom_salle }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 4) Filtre Personne --}}
            <div class="mb-3">
                <label class="form-label">Personne</label>
                <select id="filter-user" class="form-select" name="user[]" multiple size="5">
                    {{-- <option value="">Toutes personnes</option> --}}
                    @foreach($animateursDisponibles as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <small class="form-text mb-3">
                <small class="badge bg-dark-lt text-dark align-text-top">Clic gauche</small> pour selectionner ou désélectionner un élément.
            </small>

            {{-- 5) Bouton Appliquer --}}
            <div class="offcanvas-footer p-3 d-flex justify-content-between">
                <!-- Bouton reset -->
                <button id="reset-filters" class="btn btn-outline-secondary">
                    Réinitialiser
                </button>
                <!-- Bouton appliquer -->
                <button id="apply-filters" class="btn btn-primary">
                    Appliquer
                </button>
            </div>
        </div>
    </div>

    <script>
        let calendar;
        let _filtersBusy = false;
        let _filtersPendingOnce = null;

        function closeFiltersThen(cb) {
            const ocEl = document.getElementById('offcanvasFilters');
            if (!ocEl) { cb && cb(); return; }

            if (_filtersBusy) return; // empêche les doubles séquences
            _filtersBusy = true;

            const ocIns = bootstrap.Offcanvas.getOrCreateInstance(ocEl);

            const runCb = () => {
                setTimeout(() => { // laisse le focus-trap se libérer
                try { cb && cb(); } finally { _filtersBusy = false; }
                }, 20);
            };

            if (_filtersPendingOnce) {
                ocEl.removeEventListener('hidden.bs.offcanvas', _filtersPendingOnce);
                _filtersPendingOnce = null;
            }

            if (ocEl.classList.contains('show')) {
                _filtersPendingOnce = function onHiddenOnce() {
                ocEl.removeEventListener('hidden.bs.offcanvas', onHiddenOnce);
                _filtersPendingOnce = null;
                runCb();
                };
                ocEl.addEventListener('hidden.bs.offcanvas', _filtersPendingOnce, { once: true });
                ocIns.hide();
            } else {
                runCb();
            }
        }

        function openEvenementDetails(id) {
            closeFiltersThen(() => {
                const container = document.getElementById('evenementDetailContainer');
                const modalEl = document.getElementById('evenementViewModal');
                const modal = new bootstrap.Modal(modalEl);
                container.innerHTML = 'Chargement…';
                fetch(`/evenements/${id}/details`)
                .then(r => r.text())
                .then(html => { container.innerHTML = html; modal.show(); })
                .catch(console.error);
            });
        }

        function confirmEvenementDelete(id, nom) {
            closeFiltersThen(() => {
                const form = document.getElementById('deleteEvenementForm');
                form.action = `/evenements/${id}`;
                document.getElementById('deleteModalText').innerText = `Confirmer la suppression de "${nom}" ?`;
                const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
                modal.show();
            });
        }

        function bindDisponibiliteEvents() {
            const debut = document.querySelector('[name="date_heure_debut"]');
            const fin = document.querySelector('[name="date_heure_fin"]');

            if (!debut || !fin) return;

            const trigger = () => checkDisponibilites();

            debut.addEventListener('change', trigger);
            fin.addEventListener('change', trigger);

            // Recalcule aussi quand on change un animateur / une salle déjà affecté(e)
            const form = debut.closest('form');
            if (form) {
                form.addEventListener('change', (e) => {
                    if (e.target.matches('select[data-type="user"], select[data-type="salle"]')) {
                        trigger();
                    }
                });
            }

            // Vérification initiale (utile en édition/duplication où les dates sont déjà remplies)
            trigger();
        }

        async function loadEvenementFormInModal(url, title = 'Événement', event = null) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            const modalEl = document.getElementById('evenementModal');
            const modalTitle = document.getElementById('evenementModalTitle');
            const formContainer = document.getElementById('evenementFormContainer');
            const loader = document.getElementById('formLoader');

            if (!modalEl || !formContainer) {
                console.error('Modale ou conteneur de formulaire introuvable.');
                return;
            }

            if (modalTitle) {
                modalTitle.textContent = title;
            }

            if (loader) {
                loader.classList.remove('d-none');
                loader.classList.add('d-flex');
            }

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();

            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                    },
                });

                if (!response.ok) {
                    throw new Error(`Erreur HTTP ${response.status}`);
                }

                formContainer.innerHTML = await response.text();

                const form = formContainer.querySelector('form');

                if (form && !form.querySelector('input[name="source"]')) {
                    const sourceInput = document.createElement('input');
                    sourceInput.type = 'hidden';
                    sourceInput.name = 'source';
                    sourceInput.value = 'dashboard';
                    form.appendChild(sourceInput);
                }

                if (typeof initEvenementForm === 'function') {
                    initEvenementForm();
                }

                if (typeof bindDisponibiliteEvents === 'function') {
                    bindDisponibiliteEvents();
                }

            } catch (error) {
                console.error(error);

                formContainer.innerHTML = `
                    <div class="alert alert-danger mb-0">
                        Impossible de charger le formulaire.
                    </div>
                `;
            } finally {
                if (loader) {
                    loader.classList.add('d-none');
                    loader.classList.remove('d-flex');
                }
            }
        }

        function openEvenementForm(id, event = null) {
            loadEvenementFormInModal(`/evenements/${id}/edit`, 'Modifier un événement', event);
        }

        function openEvenementDuplicateForm(id, event = null) {
            loadEvenementFormInModal(`/evenements/${id}/duplicate`, 'Copier un événement', event);
        }

        function openEvenementCreateForm(event = null) {
            loadEvenementFormInModal(`/evenements/create`, 'Créer un événement', event);
        }

        function checkDisponibilites() {
            const debutInput = document.querySelector('[name="date_heure_debut"]');
            const finInput = document.querySelector('[name="date_heure_fin"]');

            if (!debutInput || !finInput) return;

            const debut = debutInput.value;
            const fin = finInput.value;

            if (!debut || !fin) return;

            fetch(`/evenements/disponibilites?debut=${encodeURIComponent(debut)}&fin=${encodeURIComponent(fin)}`)
                .then(res => res.json())
                .then(data => appliquerDisponibilites(data))
                .catch(err => console.error('checkDisponibilites', err));
        }

        // Colore (indicatif, non bloquant) les options indisponibles et affiche la raison
        // sous le select pour l'animateur/la salle actuellement sélectionné(e).
        function appliquerDisponibilites(data) {
            const IGNORE_IDS = ['0', '16'];
            const reasonsByType = {
                user: data.users || {},
                salle: data.salles || {},
            };

            Object.entries(reasonsByType).forEach(([type, reasons]) => {
                document.querySelectorAll(`select[data-type="${type}"]`).forEach(select => {
                    select.querySelectorAll('option').forEach(opt => {
                        const busy = !IGNORE_IDS.includes(opt.value)
                            && Object.prototype.hasOwnProperty.call(reasons, opt.value);

                        opt.classList.toggle('text-danger', busy);

                        if (busy) {
                            opt.title = reasons[opt.value].join(' • ');
                        } else {
                            opt.removeAttribute('title');
                        }
                    });

                    const small = select.closest('.dispo-row')?.querySelector('.dispo-reason');
                    if (!small) return;

                    const selectedReasons = !IGNORE_IDS.includes(select.value) ? reasons[select.value] : null;

                    if (selectedReasons && selectedReasons.length) {
                        small.textContent = '⚠ ' + selectedReasons.join(' • ');
                        small.classList.remove('d-none');
                    } else {
                        small.textContent = '';
                        small.classList.add('d-none');
                    }
                });
            });
        }

        function switchView(view) {
            const cards = document.getElementById('cardsContainer');
            const cal = document.getElementById('calendarContainer');

            document.getElementById('btn-cards-view').classList.remove('active');
            document.getElementById('btn-calendar-view').classList.remove('active');

            if (view === 'calendar') {
                cards.classList.add('d-none');
                cal.classList.remove('d-none');
                document.getElementById('btn-calendar-view').classList.add('active');

                if (!calendar) {
                    initCalendar();
                }
            } else {
                cards.classList.remove('d-none');
                cal.classList.add('d-none');
                document.getElementById('btn-cards-view').classList.add('active');
            }
        }

        function initCalendar() {
            calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                initialView: getInitialCalendarView(),
                locale: 'fr',
                allDaySlot: true,
                slotMinTime: "07:00:00",
                slotMaxTime: "21:00:00",
                contentHeight: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'timeGridDay,timeGridWeek,dayGridMonth,listWeek'
                },
                events: function(info, successCallback, failureCallback) {
                    const params = new URLSearchParams();

                    // dates visibles (FullCalendar les fournit automatiquement)
                    params.append('start', info.startStr);
                    params.append('end', info.endStr);

                    // filtres utilisateurs (reprendre ta logique de bouton appliquer)
                    const typeSelect = document.getElementById('filter-type');
                    const salleSelect = document.getElementById('filter-salle');
                    const userSelect = document.getElementById('filter-user');

                    Array.from(typeSelect.selectedOptions).forEach(opt => {
                        params.append('type[]', opt.value);
                    });
                    Array.from(salleSelect.selectedOptions).forEach(opt => {
                        params.append('salle[]', opt.value);
                    });
                    Array.from(userSelect.selectedOptions).forEach(opt => {
                        params.append('user[]', opt.value);
                    });

                    fetch(`/dashboard/calendar-data?${params.toString()}`)
                        .then(response => response.json())
                        .then(events => successCallback(events))
                        .catch(error => failureCallback(error));
                },
                eventClick: function(info) {
                    openEvenementDetails(info.event.id);
                },
            });

            calendar.render();
        }

        function getInitialCalendarView() {
            if (window.innerWidth < 768) {
                return 'listWeek';
            }
            return 'timeGridWeek';
        }

        function getTodayLocalYmd() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }

        function isToFilterVisible() {
            const wrapper = document.getElementById('filter-to-wrapper');

            return wrapper && !wrapper.classList.contains('d-none');
        }

        function syncHiddenToDate() {
            const fromEl = document.getElementById('filter-from');
            const toEl = document.getElementById('filter-to');

            if (!fromEl || !toEl) {
                return;
            }

            if (!isToFilterVisible()) {
                toEl.value = fromEl.value;
            }
        }

        function updateToFilterToggleLabel() {
            const btn = document.getElementById('toggle-filter-to');

            if (!btn) {
                return;
            }

            const visible = isToFilterVisible();

            btn.textContent = visible
                ? 'Retirer la période'
                : 'Ajouter une période';

            btn.setAttribute('aria-expanded', visible ? 'true' : 'false');
        }

        function toggleToFilter() {
            const wrapper = document.getElementById('filter-to-wrapper');
            const fromEl = document.getElementById('filter-from');
            const toEl = document.getElementById('filter-to');

            if (!wrapper || !fromEl || !toEl) {
                return;
            }

            const willShow = wrapper.classList.contains('d-none');

            if (willShow) {
                wrapper.classList.remove('d-none');

                if (!toEl.value) {
                    toEl.value = fromEl.value;
                }
            } else {
                wrapper.classList.add('d-none');
                toEl.value = fromEl.value;
            }

            updateToFilterToggleLabel();
        }

        function hasSelectedValues(selectId) {
            const select = document.getElementById(selectId);

            if (!select) {
                return false;
            }

            return Array.from(select.selectedOptions).some(option => option.value !== '');
        }

        function updateFilterActiveBadges() {
            const fromEl = document.getElementById('filter-from');
            const toEl = document.getElementById('filter-to');

            const today = getTodayLocalYmd();

            const from = fromEl?.value || today;
            const to = toEl?.value || from;

            const hasDateFilter = isToFilterVisible()
                ? (from !== today || to !== today || from !== to)
                : (from !== today);

            const hasTypeFilter = hasSelectedValues('filter-type');
            const hasSalleFilter = hasSelectedValues('filter-salle');
            const hasUserFilter = hasSelectedValues('filter-user');

            const hasActiveFilter =
                hasDateFilter ||
                hasTypeFilter ||
                hasSalleFilter ||
                hasUserFilter;

            document.querySelectorAll('.js-filter-active-badge').forEach(badge => {
                badge.classList.toggle('d-none', !hasActiveFilter);
            });
        }

        function initDateBadgePopovers() {
            document.querySelectorAll('#cardsContainer [data-bs-toggle="popover"]').forEach((el) => {
                bootstrap.Popover.getOrCreateInstance(el);
            });
        }

        document.addEventListener('alpine:init', () => {
            Alpine.data('marquee', () => ({
                init() {
                    // el = <div x-data> parent, inner = child ref
                    const inner = this.$refs.inner;
                    const container = this.$el;
                    // calcule le décalage nécessaire
                    const diff = inner.scrollWidth - container.clientWidth;
                    if (diff > 0) {
                        // expose la variable CSS pour keyframes
                        container.style.setProperty('--scroll-offset', `${diff}px`);
                        // déclenche l'animation après un petit délai (ici 500ms pour laisser le render se stabiliser)
                        setTimeout(() => container.classList.add('marquee--active'), 500);
                    }
                }
            }));
        });

        document.addEventListener('DOMContentLoaded', () => {

            document.addEventListener('click', (e) => {
                document.querySelectorAll('#cardsContainer [data-bs-toggle="popover"]').forEach((el) => {
                    if (el === e.target || el.contains(e.target)) return;
                    const instance = bootstrap.Popover.getInstance(el);
                    if (instance) instance.hide();
                });
            });

            initDateBadgePopovers();
            const btnApply = document.getElementById('apply-filters');
            const btnReset = document.getElementById('reset-filters');
            const cardsCt = document.getElementById('cardsContainer');
            const btnToggleTo = document.getElementById('toggle-filter-to');

            syncHiddenToDate();
            updateToFilterToggleLabel();
            updateFilterActiveBadges();

            btnToggleTo?.addEventListener('click', toggleToFilter);

            document.getElementById('filter-from')?.addEventListener('change', () => {
                syncHiddenToDate();
            });

            document.getElementById('filter-to')?.addEventListener('change', updateFilterActiveBadges);

            document.getElementById('btnOpenFilters')?.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const ocEl = document.getElementById('offcanvasFilters');
                if (!ocEl) return;
                const ocIns = bootstrap.Offcanvas.getOrCreateInstance(ocEl);
                if (!ocEl.classList.contains('show')) {
                    ocIns.show();
                }
            });

            document.getElementById('btnAddEvent')?.addEventListener('click', (event) => {
                closeFiltersThen(() => {
                    openEvenementCreateForm(event);
                });
            });

            btnApply.addEventListener('click', () => {
                syncHiddenToDate();

                const ps = new URLSearchParams();

                ['from', 'to'].forEach(k => {
                    const v = document.getElementById(`filter-${k}`).value;
                    if (v) ps.append(k, v);
                });

                ['type', 'salle', 'user'].forEach(k => {
                    const select = document.getElementById(`filter-${k}`);
                    Array.from(select.selectedOptions).forEach(opt => {
                        ps.append(k, opt.value);
                    });
                });

                fetch(`/dashboard/cards?${ps.toString()}`)
                    .then(r => r.ok ? r.text() : Promise.reject(r.status))
                    .then(html => {
                        cardsCt.innerHTML = html;
                        initDateBadgePopovers();
                        updateFilterActiveBadges();

                        if (calendar) {
                            calendar.refetchEvents();
                        }

                        bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('offcanvasFilters')).hide();
                    })
                    .catch(console.error);
            });

            btnReset.addEventListener('click', () => {
                const today = getTodayLocalYmd();

                const fromEl = document.getElementById('filter-from');
                const toEl = document.getElementById('filter-to');
                const toWrapper = document.getElementById('filter-to-wrapper');

                fromEl.value = today;
                toEl.value = today;

                toWrapper?.classList.add('d-none');
                updateToFilterToggleLabel();

                ['type', 'salle', 'user'].forEach(k => {
                    const select = document.getElementById(`filter-${k}`);
                    if (!select) return;

                    Array.from(select.options).forEach(option => {
                        option.selected = false;
                    });
                });

                updateFilterActiveBadges();

                const ps = new URLSearchParams();
                ps.append('from', today);
                ps.append('to', today);

                fetch(`/dashboard/cards?${ps.toString()}`)
                    .then(r => r.ok ? r.text() : Promise.reject(r.status))
                    .then(html => {
                        cardsCt.innerHTML = html;
                        initDateBadgePopovers();
                        updateFilterActiveBadges();

                        if (calendar) {
                            calendar.refetchEvents();
                        }

                        bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('offcanvasFilters')).hide();
                    })
                    .catch(console.error);
            });

            ['type', 'salle', 'user'].forEach(k => {
                const select = document.getElementById(`filter-${k}`);
                if (select) {
                    Array.from(select.options).forEach(option => {
                        option.addEventListener('mousedown', function (e) {
                            e.preventDefault();
                            option.selected = !option.selected;
                            const evt = new Event('change', { bubbles: true });
                            select.dispatchEvent(evt);
                        });
                    });
                }
            });

        });

        (function () {
            const SYNC_MS = 30000; // 15s (mets 10-30s selon besoin)
            let lastSig = null;
            let syncing = false;

            async function fetchJSON(url) {
                const r = await fetch(url, { cache: 'no-store' });
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            }

            async function fetchText(url) {
                const r = await fetch(url, { cache: 'no-store' });
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            }

            async function refreshCardsIfNeeded() {
                const cardsCt = document.getElementById('cardsContainer');
                if (!cardsCt) return;

                syncHiddenToDate();
                const ps = new URLSearchParams();
                const fromEl = document.getElementById('filter-from');
                const toEl   = document.getElementById('filter-to');
                if (fromEl?.value) ps.append('from', fromEl.value);
                if (toEl?.value)   ps.append('to',   toEl.value);

                ['type', 'salle', 'user'].forEach(k => {
                const select = document.getElementById(`filter-${k}`);
                if (!select) return;
                Array.from(select.selectedOptions).forEach(opt => ps.append(k, opt.value));
                });

                const html = await fetchText(`/dashboard/cards?${ps.toString()}`);
                // Préserve la position de scroll pour éviter les "sauts"
                const prevScroll = cardsCt.scrollTop;
                cardsCt.innerHTML = html;
                initDateBadgePopovers();
                cardsCt.scrollTop = prevScroll;
                updateFilterActiveBadges();
            }

            async function poll() {
                if (syncing || document.hidden) return;
                syncing = true;

                try {
                const data = await fetchJSON('{{ route('sync.version', ['scope' => 'dashboard']) }}');
                const sig = data?.dashboard?.sig || null;

                if (sig && sig !== lastSig) {
                    lastSig = sig;

                    // Rafraîchit selon la vue visible
                    const isCalendar = !document.getElementById('calendarContainer').classList.contains('d-none');

                    if (isCalendar) {
                    if (window.calendar) {
                        // évite de rafraîchir si une modale est ouverte (détails, edit, delete)
                        if (!document.body.classList.contains('modal-open')) {
                        window.calendar.refetchEvents();
                        }
                    }
                    } else {
                    if (!document.body.classList.contains('modal-open')) {
                        await refreshCardsIfNeeded();
                    }
                    }
                }
                } catch (e) {
                // silencieux : pas d'alerte pour le PC kiosque
                console.debug('Sync poll error:', e);
                } finally {
                syncing = false;
                }
            }

            // Lancer et re-poller quand l’onglet redevient visible
            document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
            setInterval(poll, SYNC_MS);
            // premier passage
            poll();
        })();
    </script>
</x-app-layout>
