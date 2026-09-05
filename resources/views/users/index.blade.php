<x-app-layout>
    <x-slot name="header">
        <h2 class="h2 mb-0 font-weight-bold">Utilisateurs</h2>
    </x-slot>

    <div class="container py-4">
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openUserForm()">
            Ajouter un utilisateur
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
                <div class="table-responsive">
                    <table id="users-table" class="table card-table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Admin</th>
                                <th>Équipe</th>
                                <th>Envoi Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{!! $user->is_admin
                                    ? '<span class="badge bg-success text-white">Oui</span>'
                                    : '<span class="badge bg-secondary text-white">Non</span>' !!}
                                </td>
                                <td>{!! $user->is_equipe
                                    ? '<span class="badge bg-info text-white">Oui</span>'
                                    : '<span class="badge bg-secondary text-white">Non</span>' !!}
                                </td>
                                <td>{!! $user->is_email
                                    ? '<span class="badge bg-primary text-white">Oui</span>'
                                    : '<span class="badge bg-secondary text-white">Non</span>' !!}
                                </td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openUserForm({{ $user->id }})">Modifier</a>
                                    <button class="btn btn-sm btn-danger" onclick="confirmUserDelete(
                        {{ $user->id }},
                        '{{ addslashes($user->name) }}'
                      )">Supprimer</button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    Aucun utilisateur trouvé.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="userModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="userFormContainer">
                        Chargement...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="POST" id="deleteUserForm">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title">Confirmation de suppression</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p id="deleteModalText">Voulez-vous vraiment supprimer cet utilisateur ?</p>
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
        function confirmUserDelete(userId, userName) {
            const form = document.getElementById('deleteUserForm');
            form.action = `/users/${userId}`;

            document.getElementById('deleteModalText').innerText = `Confirmer la suppression de "${userName}" ?`;

            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
        }

        document.addEventListener("DOMContentLoaded", function() {
            $('#users-table').DataTable({
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
