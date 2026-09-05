<x-app-layout>
    <x-slot name="header">
        <h2 class="h2 mb-0">Salles</h2>
    </x-slot>

    <div class="container py-4">
        <a href="#" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#salleModal" onclick="openSalleForm()">
            Ajouter une salle
        </a>

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
                <div class="table-responsive">
                    <table id="salles-table" class="table card-table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($salles as $salle)
                            <tr>
                                <td>{{ $salle->nom_salle }}</td>
                                <td>{{ $salle->type_salle }}</td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#salleModal" onclick="openSalleForm({{ $salle->id }})">Modifier</a>
                                    <button class="btn btn-sm btn-danger" onclick="confirmSalleDelete( {{ $salle->id }},'{{ addslashes($salle->nom_salle) }}')">Supprimer</button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    Aucune salle enregistrée.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modale de formulaire --}}
    <div class="modal modal-blur fade" id="salleModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Salle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="salleFormContainer">Chargement...</div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="deleteSalleModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="POST" id="deleteSalleForm">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title">Supprimer une salle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p id="deleteSalleModalText">Êtes-vous sûr de vouloir supprimer cette salle ?</p>
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
        function openSalleForm(id = null) {
            const url = id ? `/salles/${id}/edit` : `/salles/create`;

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    document.getElementById('salleFormContainer').innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('salleFormContainer').innerHTML = '<div class="alert alert-danger">Erreur de chargement du formulaire.</div>';
                });
        }

        function confirmSalleDelete(id, nom) {
            const form = document.getElementById('deleteSalleForm');
            form.action = `/salles/${id}`;
            document.getElementById('deleteSalleModalText').innerText = `Supprimer la salle « ${nom} » ?`;

            const modal = new bootstrap.Modal(document.getElementById('deleteSalleModal'));
            modal.show();
        }

        document.addEventListener("DOMContentLoaded", function() {
            $('#salles-table').DataTable({
                language: {
                    url: "{{ asset('assets/datatables/fr.json') }}"
                }
                , pageLength: 10
                , order: []
                , columnDefs: [{
                    orderable: false
                    , targets: 2
                }]
            });

            $('.dataTables_filter input')
                .addClass('form-control ms-2')
                .attr('placeholder', 'Rechercher…');
            $('.dataTables_length select').addClass('form-select');
            $('.dataTables_paginate .paginate_button')
                .addClass('btn btn-sm btn-outline-primary mx-1');
            $('.dataTables_info').addClass('text-muted mt-2');
        });

    </script>

</x-app-layout>
