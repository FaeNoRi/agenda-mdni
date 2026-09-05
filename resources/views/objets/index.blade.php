<x-app-layout>
    <x-slot name="header">
        <h2 class="h2 mb-0">Objets</h2>
    </x-slot>

    <div class="container py-4">
        <a href="#" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#objetModal" onclick="openObjetForm()">
            Ajouter un objet
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
                    <table id="objets-table" class="table card-table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($objets as $objet)
                            <tr>
                                <td>{{ $objet->nom_obj }}</td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#objetModal" onclick="openObjetForm({{ $objet->id }})">Modifier</a>
                                    <button class="btn btn-sm btn-danger" onclick="confirmObjetDelete(
                        {{ $objet->id }},
                        '{{ addslashes($objet->nom_obj) }}'
                      )">Supprimer</button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">
                                    Aucun objet enregistré.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <div class="modal modal-blur fade" id="objetModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Objet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="objetFormContainer">Chargement...</div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="deleteObjetModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="POST" id="deleteObjetForm">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title">Supprimer un objet</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p id="deleteObjetModalText">Confirmer la suppression ?</p>
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
        function openObjetForm(id = null) {
            const url = id ? `/objets/${id}/edit` : `/objets/create`;

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    document.getElementById('objetFormContainer').innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('objetFormContainer').innerHTML = '<div class="alert alert-danger">Erreur de chargement.</div>';
                });
        }

        function confirmObjetDelete(id, nom) {
            const form = document.getElementById('deleteObjetForm');
            form.action = `/objets/${id}`;
            document.getElementById('deleteObjetModalText').innerText =
                `Voulez-vous vraiment supprimer l’objet « ${nom} » ?`;

            const modal = new bootstrap.Modal(document.getElementById('deleteObjetModal'));
            modal.show();
        }

        document.addEventListener("DOMContentLoaded", function() {
            $('#objets-table').DataTable({
                language: {
                    url: "{{ asset('assets/datatables/fr.json') }}"
                }
                , pageLength: 10
                , order: []
                , columnDefs: [{
                    orderable: false
                    , targets: 1
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
