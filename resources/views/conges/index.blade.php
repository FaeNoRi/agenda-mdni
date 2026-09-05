<x-app-layout>
    <x-slot name="header">
        <h2 class="h2 mb-0">Congés</h2>
    </x-slot>

    <div class="container py-4">
        <a href="#" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#congeModal" onclick="openCongeForm()">
            Ajouter un congé
        </a>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                <table id="conges-table" class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utilisateur</th>
                            <th>Début</th>
                            <th>Fin</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($conges as $conge)
                        <tr>
                            <td>{{ $conge->id }}</td>
                            <td>{{ $conge->user->name ?? '—' }}</td>
                            <td>{{ \Carbon\Carbon::parse($conge->start)->format('Y-m-d H:i') }}</td>
                            <td>{{ \Carbon\Carbon::parse($conge->end)->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="#" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#congeModal" onclick="openCongeForm({{ $conge->id }})">Modifier</a>
                                <button class="btn btn-sm btn-danger" onclick="confirmCongeDelete({{ $conge->id }})">Supprimer</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modale de création/édition --}}
    <div class="modal modal-blur fade" id="congeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Congé</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="congeFormContainer">Chargement...</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modale de suppression --}}
    <div class="modal modal-blur fade" id="deleteCongeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="POST" id="deleteCongeForm">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title">Supprimer ce congé ?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger ms-auto">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCongeForm(id = null) {
            const url = id ? `/conges/${id}/edit` : `/conges/create`;

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.text())
                .then(html => {
                    document.getElementById('congeFormContainer').innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('congeFormContainer').innerHTML = '<div class="alert alert-danger">Erreur de chargement du formulaire.</div>';
                });
        }

        function confirmCongeDelete(id) {
            const form = document.getElementById('deleteCongeForm');
            form.action = `/conges/${id}`;
            const modal = new bootstrap.Modal(document.getElementById('deleteCongeModal'));
            modal.show();
        }

        document.addEventListener("DOMContentLoaded", function() {
            $('#conges-table').DataTable({
                language: {
                    url: "{{ asset('assets/datatables/fr.json') }}"
                }
                , pageLength: 10
                , order: []
                , columnDefs: [{
                    orderable: false
                    , targets: 4
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
