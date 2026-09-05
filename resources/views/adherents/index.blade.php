<x-app-layout>
    <x-slot name="header">
        <h2 class="h2 mb-0">Adhérents</h2>
    </x-slot>

    <div class="container py-4">
        <a href="#" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#adherentModal" onclick="openAdherentForm()">
            Ajouter un adhérent
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
                    <table id="adherents-table" class="table card-table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom Prénom</th>
                                <th>Situation</th>
                                <th>Domaine</th>
                                <th>Fin adhésion</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($adherents as $adherent)
                            <tr>
                                <td>{{ $adherent->id }}</td>
                                <td>{{ $adherent->nom_adh }}</td>
                                <td>{{ $adherent->situation_adh }}</td>
                                <td>{{ $adherent->dom_adh }}</td>
                                <td>{{ \Carbon\Carbon::parse($adherent->date_adh)->format('d/m/Y') }}</td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#adherentModal" onclick="openAdherentForm({{ $adherent->id }})">Modifier</a>
                                    <button class="btn btn-sm btn-danger" onclick="confirmAdherentDelete({{ $adherent->id }}, '{{ addslashes($adherent->nom_adh) }}')">Supprimer</button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Aucun adhérent enregistré.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modale formulaire --}}
    <div class="modal modal-blur fade" id="adherentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adhérent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="adherentFormContainer">Chargement...</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modale de suppression --}}
    <div class="modal modal-blur fade" id="deleteAdherentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="POST" id="deleteAdherentForm">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title">Supprimer un adhérent</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p id="deleteAdherentModalText">Êtes-vous sûr de vouloir supprimer cet adhérent ?</p>
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
        function openAdherentForm(id = null) {
            const url = id ? `/adherents/${id}/edit` : `/adherents/create`;

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.text())
            .then(html => {
                document.getElementById('adherentFormContainer').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('adherentFormContainer').innerHTML = '<div class="alert alert-danger">Erreur de chargement du formulaire.</div>';
            });
        }

        function confirmAdherentDelete(id, nom) {
            const form = document.getElementById('deleteAdherentForm');
            form.action = `/adherents/${id}`;
            document.getElementById('deleteAdherentModalText').innerText = `Supprimer l'adhérent « ${nom} » ?`;

            const modal = new bootstrap.Modal(document.getElementById('deleteAdherentModal'));
            modal.show();
        }

        document.addEventListener("DOMContentLoaded", function () {
            $('#adherents-table').DataTable({
                language: {
                    url: "{{ asset('assets/datatables/fr.json') }}"
                },
                pageLength: 10,
                order: [],
                columnDefs: [{
                    orderable: false,
                    targets: 5
                }]
            });
        });
    </script>
</x-app-layout>
