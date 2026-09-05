<!-- resources/views/modale.blade.php -->
<div class="modal fade" id="adherentModal" tabindex="-1" aria-labelledby="adherentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            {{-- Header --}}
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="adherentModalLabel">
                    Gestion de l’utilisateur
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            {{-- Onglets --}}
            <ul class="nav nav-tabs nav-tabs-bordered px-3 pt-2" id="adherentTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="presence-tab" data-bs-toggle="tab" data-bs-target="#tab-presence" type="button" role="tab" aria-controls="tab-presence" aria-selected="true">
                        Présence
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="gestion-tab" data-bs-toggle="tab" data-bs-target="#tab-gestion" type="button" role="tab" aria-controls="tab-gestion" aria-selected="false">
                        Gestion
                    </button>
                </li>
            </ul>

            {{-- Contenu de la modale --}}
            <div class="modal-body tab-content">

                {{-- Onglet Présence --}}
                <div class="tab-pane fade show active" id="tab-presence" role="tabpanel" aria-labelledby="presence-tab">
                    <div class="text-center mb-3">
                        <img id="adherentPhoto"
                            src="{{ asset('assets/img/default-avatar.png') }}"
                            class="avatar avatar-xl rounded-circle border border-info mb-2 avatar-fit"
                            alt="Photo adhérent">
                        <h3 class="fw-bold" id="adherentNomTab"></h3>
                        <div class="text-muted small">
                            <span id="adherentBadge" class="badge"></span>
                            <span id="adherentDom"></span>
                        </div>
                    </div>

                    <div class="alert alert-info text-center">
                        Merci d’indiquer votre arrivée ou votre départ de la MDNI en cliquant sur les boutons ci-dessous.
                    </div>

                    <div id="presenceCtas" class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-success btn-lg d-flex align-items-center px-4 py-3" onclick="changerPresence(currentAdherentId, 1)">
                            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-login me-2">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M15 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" />
                                <path d="M21 12h-13l3 -3" />
                                <path d="M11 15l-3 -3" />
                            </svg>
                            <span class="fw-bold">J’arrive à la MDNI</span>
                        </button>

                        <button type="button" class="btn btn-danger btn-lg d-flex align-items-center px-4 py-3" onclick="changerPresence(currentAdherentId, 0)">
                            <span class="fw-bold">Je pars de la MDNI</span>
                            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-login-2 ms-2 me-0">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M9 8v-2a2 2 0 0 1 2 -2h7a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-7a2 2 0 0 1 -2 -2v-2" />
                                <path d="M3 12h13l-3 -3" />
                                <path d="M13 15l3 -3" />
                            </svg>
                        </button>
                    </div>
                    <div class="d-flex flex-wrap gap-3 mt-4 justify-content-center">
                        <a href="{{ asset('assets/files/reglement_interieur.pdf') }}" target="_blank" class="btn btn-info">
                            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-book-2">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M19 4v16h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12z" />
                                <path d="M19 16h-12a2 2 0 0 0 -2 2" />
                                <path d="M9 8h6" />
                            </svg> Consulter le règlement intérieur
                        </a>
                    </div>
                </div>

                {{-- Onglet Gestion --}}
                <div class="tab-pane fade m-auto" id="tab-gestion" role="tabpanel" aria-labelledby="gestion-tab">
                
                        <div id="formEditWrapper" class="mt-3">
                            <h2 id="formModeTitle" class="badge bg-info-lt text-info mb-4" style="font-size: 24px;">
                            Modifier le profil
                            </h2>
                            @include('adherents._form_presence')
                        </div>
                                                
                        <hr class="my-4">

                        <div id="deleteSection">
                            <h2 class="badge bg-danger-lt text-danger mb-4" style="font-size: 24px;">Supprimer le profil</h2>
                            <form id="deleteAdherentForm" method="POST" onsubmit="return confirm('Confirmer la suppression de ce profil ?');">
                                @csrf
                                @method('DELETE')
                                <div class="col-md-6 mb-3">
                                    <label for="deletePassword" class="form-label text-danger">Mot de passe administrateur</label>
                                    <input type="password" name="admin_password" id="deletePassword" class="form-control" required>
                                    <small class="text-danger">Obligatoire pour valider la suppression.</small>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-danger">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                            class="icon icon-tabler icons-tabler-outline icon-tabler-trash-x me-2">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M4 7h16" />
                                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                            <path d="M10 12l4 4m0 -4l-4 4" />
                                        </svg>
                                        Supprimer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
