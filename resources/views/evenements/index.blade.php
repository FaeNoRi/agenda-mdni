<x-app-layout>
    <x-slot name="header">
        <h2 class="h2 mb-0 font-weight-bold">
            Événements
        </h2>
    </x-slot>

    <div class="container py-4">
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#evenementModal" onclick="openEvenementCreateForm(event)">
            Ajouter un événement
        </button>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            <div class="d-flex">
                <div>{{ session('success') }}</div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></a>
            </div>
        </div>
        @endif

        <div class="card shadow-sm border rounded">
            <div class="card-body">
                <div class="table-responsive" style="overflow-x: hidden;">
                    <table class="table card-table table-striped table-hover" id="evenements-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Client</th>
                                <th>Type</th>
                                <th>Début</th>
                                <th>Fin</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
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
                <div class="modal-body">
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

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            $('#evenements-table').DataTable({
                serverSide: true,
                processing: true,
                scrollX: false,
                ajax: "{{ route('evenements.data') }}",
                columns: [
                    { data: 'id',                  name: 'id',                  searchable: true },
                    { data: 'nom_event',           name: 'nom_event',           searchable: true },
                    { data: 'commanditaire_event', name: 'commanditaire_event', searchable: true },
                    { data: 'type_event',          name: 'type_event',          searchable: true },
                    { data: 'date_heure_debut',    name: 'date_heure_debut',    searchable: true },
                    { data: 'date_heure_fin',      name: 'date_heure_fin',      searchable: true },
                    { data: 'actions',             name: 'actions',             orderable: false, searchable: false }
                ],
                order: [[0, 'desc']],
                columnDefs: [{ orderable: false, targets: -1 }],
                language: {
                    url: "{{ asset('assets/datatables/fr.json') }}"
                }
            });

            // styling Tabler
            $('.dataTables_filter input')
                .addClass('form-control ms-2')
                .attr('placeholder', 'Rechercher…');
            $('.dataTables_length select').addClass('form-select');
            $('.dataTables_paginate .paginate_button')
                .addClass('btn btn-sm btn-outline-primary mx-1');
            $('.dataTables_info').addClass('text-muted mt-2');
        });

        function confirmEvenementDelete(id, nom) {
            const form = document.getElementById('deleteEvenementForm');
            form.action = `/evenements/${id}`;
            // backticks OK ici, pas de retour à la ligne
            document.getElementById('deleteModalText').innerText =
                `Confirmer la suppression de "${nom}" ?`;
            new bootstrap.Modal(
                document.getElementById('deleteConfirmModal')
            ).show();
        }

        function loadEvenementFormInModal(url, title = 'Événement', event = null) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            const container = document.getElementById('evenementFormContainer');
            const modalTitle = document.getElementById('evenementModalTitle');

            if (!container) {
                console.error('Conteneur #evenementFormContainer introuvable.');
                return;
            }

            if (modalTitle) {
                modalTitle.textContent = title;
            }

            container.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">Chargement…</div>
                </div>
            `;

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
            })
                .then(res => {
                    if (!res.ok) {
                        throw new Error(`Erreur HTTP ${res.status}`);
                    }

                    return res.text();
                })
                .then(html => {
                    container.innerHTML = html;

                    if (typeof initEvenementForm === 'function') {
                        initEvenementForm();
                    }

                    if (typeof bindDisponibiliteEvents === 'function') {
                        bindDisponibiliteEvents();
                    }
                })
                .catch(error => {
                    console.error(error);

                    container.innerHTML = `
                        <div class="alert alert-danger mb-0">
                            Impossible de charger le formulaire.
                        </div>
                    `;
                });
        }

        function openEvenementCreateForm(event = null) {
            loadEvenementFormInModal('/evenements/create', 'Créer un événement', event);
        }

        function openEvenementForm(id, event = null) {
            loadEvenementFormInModal(`/evenements/${id}/edit`, 'Modifier un événement', event);
        }

        function openEvenementDuplicateForm(id, event = null) {
            loadEvenementFormInModal(`/evenements/${id}/duplicate`, 'Copier un événement', event);
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
    </script>

</x-app-layout>
