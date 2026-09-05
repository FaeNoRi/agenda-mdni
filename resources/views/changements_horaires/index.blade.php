<x-app-layout>
    <x-slot name="header">
        <h2 class="h2 mb-0">Changements d'horaires</h2>
    </x-slot>

    <div class="container py-4">
        <a href="#" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#chgmtModal" onclick="openChgmtForm()">
            Ajouter un changement
        </a>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                <table id="changements-table" class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utilisateur</th>
                            <th>Type</th>
                            <th>Ancien</th>
                            <th>Nouveau</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($changements_horaires as $chgmt)
                        <tr>
                            <td>{{ $chgmt->id }}</td>
                            <td>{{ $chgmt->user->name ?? '—' }}</td>
                            <td>
                                @if($chgmt->type_chgmt === 'add')
                                <span class="badge bg-green text-white">Ajout</span>
                                @elseif($chgmt->type_chgmt === 'change')
                                <span class="badge bg-yellow text-white">Modification</span>
                                @else
                                <span class="badge bg-secondary text-white">Inconnu</span>
                                @endif
                            </td>
                            <td>
                                @if($chgmt->type_chgmt === 'change')
                                {{ \Carbon\Carbon::parse($chgmt->old_start)->format('Y-m-d H:i') }}
                                →
                                {{ \Carbon\Carbon::parse($chgmt->old_end)->format('Y-m-d H:i') }}
                                @else
                                <em class="text-muted">Non applicable</em>
                                @endif
                            </td>
                            <td>{{ $chgmt->new_start }} → {{ $chgmt->new_end }}</td>
                            <td>
                                <a href="#" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#chgmtModal" onclick="openChgmtForm({{ $chgmt->id }})">Modifier</a>
                                <button class="btn btn-sm btn-danger" onclick="confirmChgmtDelete({{ $chgmt->id }})">Supprimer</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modales --}}
    <div class="modal modal-blur fade" id="chgmtModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Changement d'horaire</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="chgmtFormContainer">Chargement...</div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="deleteChgmtModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="POST" id="deleteChgmtForm">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title">Supprimer ce changement ?</h5>
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
        function openChgmtForm(id = null) {
            const url = id ? `/changements_horaires/${id}/edit` : `/changements_horaires/create`;

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.text())
                .then(html => {
                    document.getElementById('chgmtFormContainer').innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('chgmtFormContainer').innerHTML = '<div class="alert alert-danger">Erreur de chargement du formulaire.</div>';
                });
        }

        function confirmChgmtDelete(id) {
            const form = document.getElementById('deleteChgmtForm');
            form.action = `/changements_horaires/${id}`;
            const modal = new bootstrap.Modal(document.getElementById('deleteChgmtModal'));
            modal.show();
        }

        document.addEventListener("DOMContentLoaded", function() {
            $('#changements-table').DataTable({
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
