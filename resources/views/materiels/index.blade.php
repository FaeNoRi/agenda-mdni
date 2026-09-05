<x-app-layout>
    <x-slot name="header">
        <h2 class="h2 mb-0">Matériels</h2>
    </x-slot>

    <div class="container py-4">
        <a href="#" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#materielModal" onclick="openMaterielForm()">
            Ajouter un matériel
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
                    <table id="materiels-table" class="table card-table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($materiels as $materiel)
                            <tr>
                                <td>{{ $materiel->nom_mat }}</td>
                                <td>{{ $materiel->nb_stock }}</td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#materielModal" onclick="openMaterielForm({{ $materiel->id }})">Modifier</a>
                                    <button class="btn btn-sm btn-danger" onclick="confirmMaterielDelete(
                        {{ $materiel->id }},
                        '{{ addslashes($materiel->nom_mat) }}'
                      )">Supprimer</button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    Aucun matériel enregistré.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="materielModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Matériel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="materielFormContainer">Chargement...</div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="deleteMaterielModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="POST" id="deleteMaterielForm">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title">Supprimer un matériel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p id="deleteMaterielModalText">Confirmer la suppression du matériel ?</p>
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
        function openMaterielForm(id = null) {
            const url = id ? `/materiels/${id}/edit` : `/materiels/create`;

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    document.getElementById('materielFormContainer').innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('materielFormContainer').innerHTML = '<div class="alert alert-danger">Erreur de chargement.</div>';
                });
        }

        function confirmMaterielDelete(id, nom) {
            const form = document.getElementById('deleteMaterielForm');
            form.action = `/materiels/${id}`;

            document.getElementById('deleteMaterielModalText').innerText =
                `Voulez-vous vraiment supprimer le matériel « ${nom} » ?`;

            const modal = new bootstrap.Modal(document.getElementById('deleteMaterielModal'));
            modal.show();
        }

        document.addEventListener("DOMContentLoaded", function() {
            $('#materiels-table').DataTable({
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
