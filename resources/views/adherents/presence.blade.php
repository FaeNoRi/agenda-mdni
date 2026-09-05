<!-- resources/views/presence.blade.php -->
@php

$colors = [
'MDNI' => 'info',
'Étudiant' => 'success',
'Bénévole' => 'secondary',
'Porteur de projet' => 'warning',
'Chef d\'entreprise' => 'danger',
'Salarié' => 'secondary',
'Particulier' => 'warning',
];
@endphp

<style>
    html,
    body {
        height: 100%;
        overflow: hidden; /* pas de scroll global */
    }

    img.avatar {
    object-fit: cover;
    object-position: center;
    aspect-ratio: 1 / 1; /* garantit un carré même si avatar-xl change */
    }

    .main-container {
        height: calc(100vh - 65px); /* hauteur de l’écran - la navbar */
    }

    .scrollable {
        height: 100%;
        overflow-y: auto; /* scroll vertical interne */
    }

    .text-start { padding-left: 6px; }

    .border-success-thick { border: 4px solid var(--tblr-success) !important; }
    .border-danger-thick  { border: 4px solid var(--tblr-danger)  !important; }
    .border-secondary-thick { border: 4px solid var(--tblr-secondary) !important; }

    /* Empêche toute barre horizontale parasite */
    html, body { overflow-x: hidden; }
    .offcanvas, .offcanvas-body { overflow-x: hidden; }

    /* Tronquer proprement le texte dans le tiroir */
    #offcanvasAbsents .text-truncate { max-width: 55vw; }
    @media (min-width: 992px) { #offcanvasAbsents .text-truncate { max-width: 40vw; } }

    /* Cache le FAB quand une modale est ouverte */
    body.modal-open #btnOpenAbsents { display: none; }

    #adherentModal .avatar-fit {
        width: 6rem;
        height: 6rem;
        border-radius: 50%;
        object-fit: cover;
        object-position: center;
        display: inline-block;
        flex-shrink: 0;
        aspect-ratio: 1 / 1;
    }
</style>

{{-- Navbar --}}
<div class="navbar" style="background-color: var(--tblr-primary); padding-top: .5rem; padding-bottom: .5rem; max-height: 65px; align-content: center;">
    <div class="container-fluid d-flex justify-content-between align-items-center">

        {{-- Logo à gauche --}}
        <a href="#" class="navbar-brand d-flex align-items-center text-white mb-0">
            <x-application-logo class="block w-auto h-8 fill-current text-white me-2" />
            <span class="fw-bold fs-15">Floppy Là</span>
        </a>

        {{-- Boutons à droite --}}
        <div class="d-flex">
            <button id="btnSignatureReglement" type="button" class="btn btn-outline-light mx-1 px-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-signature">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M3 17c3.333 -3.333 5 -6 5 -8c0 -3 -1 -3 -2 -3s-2.032 1.085 -2 3c.034 2.048 1.658 4.877 2.5 6c1.5 2 2.5 2.5 3.5 1l2 -3c.333 2.667 1.333 4 3 4c.53 0 2.639 -2 3 -2c.517 0 1.517 .667 3 2" />
                </svg>
                Signature
            </button>

            <button id="btnAddAdherent" type="button" class="btn btn-outline-light mx-1 px-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-user-plus">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                    <path d="M16 19h6" />
                    <path d="M19 16v6" />
                    <path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
                </svg>
                Ajouter
            </button>

            <button id="btnResetAll" class="btn btn-outline-light mx-1 px-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-arrow-back-up">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M9 14l-4 -4l4 -4" />
                    <path d="M5 10h11a4 4 0 1 1 0 8h-1" />
                </svg>
                Reset
            </button>

            <button id="btnStats" class="btn btn-outline-light mx-1 px-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-file-spreadsheet">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                    <path d="M8 11h8v7h-8z" />
                    <path d="M8 15h8" />
                    <path d="M11 11v7" />
                </svg>
                Stats
            </button>
        </div>
    </div>
</div>

{{-- Contenu principal --}}
<x-guest-layout>
    <div class="container-fluid pb-4 main-container">
        <div class="row h-100">

            {{-- Colonne gauche : MDNI --}}
            <div class="col-md-4 h-100">
                <div class="card h-100">
                    <div class="card-header bg-warning text-white py-2">Équipe MDNI</div>
                    <div class="card-body scrollable">
                        <div id="teamContainer" class="row row-cols-1 row-cols-sm-2 g-3">
                            @foreach($equipe as $adh)
                                @php $clr = $colors[$adh->situation_adh] ?? 'gray'; @endphp
                                <div class="col adh-card"
                                    data-id="{{ $adh->id }}"
                                    data-nom="{{ $adh->nom_adh }}"
                                    data-situation="{{ $adh->situation_adh }}"
                                    data-dom="{{ $adh->dom_adh }}"
                                    data-date="{{ $adh->date_adh ? $adh->date_adh->format('Y-m-d') : '' }}"
                                    data-photo="{{ $adh->photo_adh ? asset($adh->photo_adh) : asset('assets/img/default-avatar.png') }}"
                                    data-present="{{ $adh->isPresent ? 1 : 0 }}"
                                    data-cgu="{{ $adh->isCGU ? 1 : 0 }}"
                                    data-color="{{ $colors[$adh->situation_adh] ?? 'gray' }}">
                                    <div class="card shadow-sm h-100">
                                        <div class="card-body d-flex align-items-center">
                                            @php
                                                $isExpired = $adh->date_adh && \Carbon\Carbon::today()->gt($adh->date_adh);
                                                // Priorité rouge > vert > gris
                                                if ($isExpired)      $borderClass = 'border-danger-thick';
                                                elseif ($adh->isPresent) $borderClass = 'border-success-thick';
                                                else                 $borderClass = 'border-secondary-thick';
                                            @endphp

                                            <img src="{{ asset($adh->photo_adh ?? 'assets/img/default-avatar.png') }}"
                                                class="rounded-circle {{ $borderClass }}"
                                                style="width: 5rem; height: 5rem; flex-shrink: 0; object-fit: cover;"
                                                alt="photo">
                                            <div class="text-start ms-3 flex-grow-1">
                                                <div class="fw-bold">{{ $adh->nom_adh }}</div>
                                                <div class="text-muted small">
                                                    <span class="badge bg-{{ $clr }}-lt text-{{ $clr }}">{{ $adh->situation_adh }}</span>
                                                    – {{ $adh->dom_adh }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Colonne droite : Coworkers présents --}}
            <div class="col-md-8 h-100">
                <div class="card h-100">
                    <div class="card-header bg-success text-white py-2">Coworkers Présents</div>
                    <div class="card-body scrollable">
                        <div id="presentContainer" class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-2">
                            @foreach($presents as $adh)
                                @php $clr = $colors[$adh->situation_adh] ?? 'gray'; @endphp
                                <div class="col adh-card"
                                    data-id="{{ $adh->id }}"
                                    data-nom="{{ $adh->nom_adh }}"
                                    data-situation="{{ $adh->situation_adh }}"
                                    data-dom="{{ $adh->dom_adh }}"
                                    data-date="{{ $adh->date_adh ? $adh->date_adh->format('Y-m-d') : '' }}"
                                    data-photo="{{ $adh->photo_adh ? asset($adh->photo_adh) : asset('assets/img/default-avatar.png') }}"
                                    data-present="{{ $adh->isPresent ? 1 : 0 }}"
                                    data-cgu="{{ $adh->isCGU ? 1 : 0 }}"
                                    data-color="{{ $colors[$adh->situation_adh] ?? 'gray' }}">
                                    <div class="card text-center shadow-sm">
                                        <div class="card-body p-3">
                                            @php
                                                $isExpired = $adh->date_adh && \Carbon\Carbon::today()->gt($adh->date_adh);
                                                // Priorité rouge > vert (les présents) — gris ne s'applique pas ici car ce conteneur = présents
                                                $borderClass = $isExpired ? 'border-danger-thick' : 'border-success-thick';
                                            @endphp

                                            <img src="{{ asset($adh->photo_adh ?? 'assets/img/default-avatar.png') }}"
                                                class="avatar avatar-xl mb-2 rounded-circle {{ $borderClass }}"
                                                alt="photo">
                                            <h3 class="fw-bold mb-0">{{ $adh->nom_adh }}</h3>
                                            <small class="text-muted">
                                                <span class="badge bg-{{ $clr }}-lt text-{{ $clr }}">
                                                    {{ $adh->situation_adh }}
                                                </span> - {{ $adh->dom_adh }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FAB : Ouvrir le tiroir "Absents" --}}
    <button id="btnOpenAbsents" type="button"
            class="btn btn-primary position-fixed h2 btn-open-absents"
            style="bottom:1rem; right:1rem; z-index:1040;"
            aria-label="Ouvrir la liste des adhérents absents">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
        </svg>
        Se connecter
    </button>

    <div class="modal fade" id="signatureReglementModal" tabindex="-1" aria-labelledby="signatureReglementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form class="modal-content" id="signatureReglementForm" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="signatureReglementModalLabel">Signature du règlement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <div class="modal-body">
                    <a href="{{ route('reglement.lecture') }}" target="_blank" class="btn btn-outline-primary mb-3">
                        Lire le règlement avant signature
                    </a>

                    <div class="row">
                        <label class="form-label">Adhérent 2026</label>
                        <div class="col-md-3 mb-3">
                            <input type="text" class="form-control" id="signatureAdherentSearch" placeholder="Recherche">
                        </div>

                        <div class="col-md-6 mb-3">
                            <select class="form-select" id="signatureAdherentId" name="adherent_id" required>
                                <option value="">Chargement...</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mot de passe Admin</label>
                        <input type="password" class="form-control" name="admin_password" required>
                    </div>

                    <div class="mb-3">

                        <label class="form-label">Signature manuscrite</label>
                        <div class="mb-2 fw-bold">
                            Par la présente signature, je certifie avoir pris connaissance du règlement et m'engage à le respecter :
                        </div>

                        <div class="border rounded bg-white">
                            <canvas id="signatureCanvas" style="width:100%; height:220px;"></canvas>
                        </div>

                        <input type="hidden" name="signature" id="signatureInput">

                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="clearSignature">
                            Effacer la signature
                        </button>
                    </div>

                    <div class="mb-3">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="refus_photo" id="refusPhoto" value="1">
                            <span class="form-check-label">
                                Merci de cocher la case ci-contre si vous vous opposez à toute prise de photos/vidéos
                                (en dehors de la photo obligatoire pour la plateforme de suivi des présences - photo non diffusée)
                            </span>
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Annuler</button>
                    <button class="btn btn-primary" type="submit">
                        Signer et envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale Reset -->
    <div class="modal fade" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" id="resetForm" autocomplete="off">
            <div class="modal-header">
                <h5 class="modal-title" id="resetModalLabel">Réinitialiser les présences</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">
                Cette action va passer <strong>tous les adhérents</strong> au statut <em>absent</em><br>
                Confirmer avec le mot de passe administrateur.
                </p>

                <div class="mb-2">
                    <label class="form-label">Mot de passe administrateur</label>
                    <input type="password" name="admin_password" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-danger" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 14l-4 -4l4 -4" />
                    <path d="M5 10h11a4 4 0 1 1 0 8h-1" />
                </svg>
                Déconnecter tout le monde
                </button>
            </div>
            </form>
        </div>
    </div>

    <!-- Modale Export Stats -->
    <div class="modal fade" id="statsModal" tabindex="-1" aria-labelledby="statsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" id="statsForm" autocomplete="off">
            <div class="modal-header">
                <h5 class="modal-title" id="statsModalLabel">Exporter les statistiques de fréquentation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body">
                <div class="mb-2">
                <label class="form-label">Période</label>
                <select id="statsPeriod" name="period" class="form-select" required>
                    <option value="all" selected>Toutes les stats</option>
                    <option value="last_30d">30 derniers jours</option>
                </select>
                <div class="form-text">Seuls les mois où au moins une présence existe apparaissent.</div>
                </div>

                <div class="mb-2">
                <label class="form-label">E-mail destinataire</label>
                <input type="email" class="form-control" id="statsEmail" name="recipient" placeholder="contact@mdnicalaisis.com">
                <div class="form-text">Laisser vide pour utiliser l’adresse par défaut : <b>contact@mdnicalaisis.com</b> .</div>
                </div>

                <div class="mb-2">
                <label class="form-label">Mot de passe administrateur</label>
                <input type="password" class="form-control" id="statsAdminPassword" name="admin_password" required>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-primary" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                </svg>
                Exporter
                </button>
            </div>
            </form>
        </div>
    </div>
</x-guest-layout>

{{-- Offcanvas : Adhérents absents --}}
<div class="offcanvas offcanvas-end" id="offcanvasAbsents" tabindex="-1" aria-labelledby="offcanvasAbsentsLabel"
        data-bs-backdrop="false"  data-bs-keyboard="false"  data-bs-scroll="true" style="box-shadow: -10px 0px 10px -3px rgb(0 0 0 / .1);">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title h4" id="offcanvasAbsentsLabel">Liste des Adhérents</h2>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
    </div>

    <div class="offcanvas-body">
        {{-- Barre de recherche --}}
        <div class="mb-3">
        <div class="input-icon">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" class="form-control" id="absentsSearch" placeholder="Rechercher (nom, situation, domaine)…">
        </div>
        <div class="small text-muted mt-1" id="absentsMeta">
            {{ $absents->count() }} adhérent(s)
        </div>
        </div>

        {{-- Liste des absents (chaque item est cliquable comme une carte) --}}
        <ul class="list-group list-group-flush" id="absentsList" style="max-height: 71vh; overflow:auto;">
        @forelse($absents as $adh)
            @php
            $clr = $colors[$adh->situation_adh] ?? 'gray';
            $photo = $adh->photo_adh ? asset($adh->photo_adh) : asset('assets/img/default-avatar.png');
            @endphp
            <li class="list-group-item adh-card" role="button"
                data-id="{{ $adh->id }}"
                data-nom="{{ $adh->nom_adh }}"
                data-situation="{{ $adh->situation_adh }}"
                data-dom="{{ $adh->dom_adh }}"
                data-date="{{ $adh->date_adh ? $adh->date_adh->format('Y-m-d') : '' }}"
                data-photo="{{ $photo }}"
                data-present="{{ $adh->isPresent ? 1 : 0 }}"
                data-cgu="{{ $adh->isCGU ? 1 : 0 }}"
                data-color="{{ $colors[$adh->situation_adh] ?? 'gray' }}"
                data-searchblob="{{ Str::lower($adh->nom_adh.' '.$adh->situation_adh.' '.$adh->dom_adh) }}"
            >
                <div class="d-flex align-items-center py-2 gap-2">
                    <span class="avatar flex-shrink-0" style="background-image:url('{{ $photo }}'); width:3rem; height:3rem;"></span>
                    <div class="min-w-0">
                    <div class="fw-semibold text-truncate">{{ $adh->nom_adh }}</div>
                    <div class="text-muted small text-truncate">
                        <span class="badge bg-{{ $clr }}-lt text-{{ $clr }}">{{ $adh->situation_adh }}</span>
                        — {{ $adh->dom_adh }}
                    </div>
                    </div>
                </div>
            </li>
        @empty
            <li class="list-group-item text-muted">Aucun absent 🎉</li>
        @endforelse
        </ul>
    </div>

    <div class="border-top p-3 d-flex justify-content-end">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">
            Fermer
        </button>
    </div>
</div>

@include('adherents.modale')
<audio id="snd-login"  src="{{ asset('assets/audio/login.wav') }}"  preload="auto"></audio>
<audio id="snd-logout" src="{{ asset('assets/audio/logout.wav') }}" preload="auto"></audio>
<audio id="snd-invalid" src="{{ asset('assets/audio/invalid.wav') }}" preload="auto"></audio>

<script>
    let currentAdherentId = null;
    let _ocBusy = false;
    let _ocPendingOnce = null;
    const DEFAULT_PHOTO = "{{ asset('assets/img/default-avatar.png') }}";

    const sndLogin  = document.getElementById('snd-login');
    const sndLogout = document.getElementById('snd-logout');
    const sndInvalid = document.getElementById('snd-invalid');

    function playSound(el) {
        if (!el) return;
        try {
            el.muted = false;
            el.volume = 1;
            el.pause();
            el.currentTime = 0;
            el.play().catch(() => { });
        } catch (_) {}
    }

    function playPresenceSound(isPresent, forceInvalid = false) {
        if (forceInvalid) return playSound(sndInvalid);
        playSound(isPresent ? sndLogin : sndLogout);
    }

    function isInvalidMembershipDateStr(ds) {
        // ds = "YYYY-MM-DD" (date de FIN d'adhésion)
        if (!ds || typeof ds !== 'string') return true;

        const end = new Date(ds + 'T00:00:00');
        if (isNaN(end.getTime())) return true;

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        // invalide si la fin d'adhésion est avant aujourd'hui
        return end < today;
    }

    function getEditForm() {
        return document.getElementById('adherentEditForm') || document.querySelector('#formEditWrapper form');
    }

    function fillPresenceHeaderFromCard(card) {
        document.getElementById('adherentNomTab').textContent = card.dataset.nom || '';
        document.getElementById('adherentPhoto').src = card.dataset.photo || DEFAULT_PHOTO;
        document.getElementById('adherentDom').textContent = card.dataset.dom ? ` - ${card.dataset.dom}` : '';

        const badge = document.getElementById('adherentBadge');
        if (badge) {
            badge.className = `badge bg-${card.dataset.color}-lt text-${card.dataset.color}`;
            badge.textContent = card.dataset.situation || '';
        }
    }

    function fillFormFromCard(card) {
        const form = getEditForm();
        if (!form) return;

        form.querySelector('input[name="nom_adh"]').value = card.dataset.nom || '';
        form.querySelector('select[name="situation_adh"]').value = card.dataset.situation || '';
        form.querySelector('input[name="dom_adh"]').value = card.dataset.dom || '';
        if (card.dataset.date) form.querySelector('input[name="date_adh"]').value = card.dataset.date;

        const isCGUCheckbox     = form.querySelector('input[type="checkbox"][name="isCGU"]');
        const isPresentCheckbox = form.querySelector('input[type="checkbox"][name="isPresent"]');
        if (isCGUCheckbox)     isCGUCheckbox.checked     = (card.dataset.cgu === "1");
        if (isPresentCheckbox) isPresentCheckbox.checked = (card.dataset.present === "1");
    }

    function resetFormFieldsToCreateDefaults() {
        const form = getEditForm();
        if (!form) return;

        form.reset();

        const spoof = form.querySelector('input[name="_method"]');
        if (spoof) spoof.remove();

        const sel = form.querySelector('select[name="situation_adh"]'); if (sel) sel.value = 'MDNI';
        const cgu = form.querySelector('input[type="checkbox"][name="isCGU"]'); if (cgu) cgu.checked = false;
        const present = form.querySelector('input[type="checkbox"][name="isPresent"]'); if (present) present.checked = false;
        const pwd = form.querySelector('input[name="admin_password"]'); if (pwd) pwd.value = '';
        const file = form.querySelector('input[type="file"][name="photo_adh"]'); if (file) file.value = '';

        document.getElementById('adherentPhoto').src = DEFAULT_PHOTO;
        const nomTab = document.getElementById('adherentNomTab'); if (nomTab) nomTab.textContent = 'Nouvel adhérent';
        const domTab = document.getElementById('adherentDom'); if (domTab) domTab.textContent = '';
        const badge = document.getElementById('adherentBadge'); if (badge) { badge.className = 'badge'; badge.textContent = ''; }

        const title = document.getElementById('formModeTitle'); if (title) title.textContent = 'Ajouter un adhérent';
        const submitBtn  = document.getElementById('formSubmitBtn');
        const label      = document.getElementById('formSubmitLabel');
        const iconEdit   = document.getElementById('iconEdit');
        const iconAdd    = document.getElementById('iconAdd');
        const pwdHelp    = document.getElementById('adminPasswordHelp');

        if (submitBtn) { submitBtn.classList.remove('btn-info'); submitBtn.classList.add('btn-success'); }
        if (label)     label.textContent = 'Créer';
        if (iconEdit)  iconEdit.classList.add('d-none');
        if (iconAdd)   iconAdd.classList.remove('d-none');
        if (pwdHelp)   pwdHelp.textContent = 'Obligatoire pour valider la création.';
    }

    function setFormToCreateMode() {
        const form = getEditForm();
        if (!form) return;

        form.action = "{{ route('presence.adherents.store') }}";
        document.getElementById('presenceCtas')?.classList.add('d-none');
        document.getElementById('deleteSection')?.classList.add('d-none');

        resetFormFieldsToCreateDefaults();

        const gestionTab = document.getElementById('gestion-tab');
        if (gestionTab) new bootstrap.Tab(gestionTab).show();
    }

    function setFormToEditMode(adherentId) {
        const form = getEditForm();
        if (!form) return;

        if (!form.querySelector('input[name="_method"]')) {
            form.insertAdjacentHTML('afterbegin','<input type="hidden" name="_method" value="PUT">');
        }

        form.action = "{{ route('presence.adherents.update', ['adherent' => '__ID__']) }}".replace('__ID__', adherentId);

        document.getElementById('presenceCtas')?.classList.remove('d-none');
        document.getElementById('deleteSection')?.classList.remove('d-none');

        const title = document.getElementById('formModeTitle'); if (title) title.textContent = 'Modifier le profil';
        const submitBtn  = document.getElementById('formSubmitBtn');
        const label      = document.getElementById('formSubmitLabel');
        const iconEdit   = document.getElementById('iconEdit');
        const iconAdd    = document.getElementById('iconAdd');
        const pwdHelp    = document.getElementById('adminPasswordHelp');

        if (submitBtn) { submitBtn.classList.remove('btn-success'); submitBtn.classList.add('btn-info'); }
        if (label)     label.textContent = 'Modifier';
        if (iconEdit)  iconEdit.classList.remove('d-none');
        if (iconAdd)   iconAdd.classList.add('d-none');
        if (pwdHelp)   pwdHelp.textContent = 'Obligatoire pour valider la modification.';
    }

    function setDeleteAction(adherentId) {
        const deleteForm = document.getElementById('deleteAdherentForm');
        if (deleteForm) {
            deleteForm.action = "{{ route('presence.adherents.destroy', ['adherent' => '__ID__']) }}".replace('__ID__', adherentId);
        }
    }

    function changerPresence(adherentId, isPresent) {
        fetch(`/adherents/${adherentId}/presence`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ isPresent, source: 'presence_ui' })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                showToast("Erreur : " + (data.message || "changement de présence impossible"), 'danger');
                return;
            }

            const srcEl = document.querySelector(`.adh-card[data-id='${adherentId}']`);
            const wasPresent = srcEl?.dataset?.present === "1";
            const nowPresent = !!isPresent;
            const invalidMembership = isInvalidMembershipDateStr(srcEl?.dataset?.date);

            if (wasPresent !== nowPresent) {
                playPresenceSound(nowPresent, nowPresent && invalidMembership);
            }

            showToast(data.message, 'success');

            let payload = srcEl ? cardDataFromEl(srcEl) : null;
            if (!payload) payload = { id: adherentId, nom:'', situation:'', dom:'', date:'', photo:DEFAULT_PHOTO, color:'gray' };

            const isMDNI = (payload.situation || '').toUpperCase() === 'MDNI';

            if (isPresent) {
                if (!isMDNI) {
                    removeFromAbsents(adherentId);
                    addToPresents({ ...payload, present:true });
                }
            } else {
                if (!isMDNI) {
                    removeFromPresents(adherentId);
                    addToAbsents({ ...payload, present:false });
                }
            }

            // Met à jour l'état et la bordure sur toutes les occurrences visibles (MDNI + Présents)
            document.querySelectorAll(`.adh-card[data-id='${adherentId}']`).forEach(card => {
                setCardPresentState(card, isPresent); // applique rouge (prioritaire), vert, ou gris
            });

            // Retrie la colonne MDNI (présents en haut)
            sortTeamContainerByPresenceThenName();

            document.querySelectorAll(`.adh-card[data-id='${adherentId}']`).forEach(el => {
                el.dataset.present = isPresent ? "1" : "0";
            });

            const modalEl = document.getElementById('adherentModal');
            bootstrap.Modal.getInstance(modalEl)?.hide();
        })
        .catch(() => showToast("Une erreur est survenue.", 'danger'));
    }

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0`;
        toast.role = 'alert';
        toast.ariaLive = 'assertive';
        toast.ariaAtomic = 'true';
        toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto"
            data-bs-dismiss="toast" aria-label="Fermer"></button>
        </div>
        `;
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(container);
        }
        container.appendChild(toast);
        new bootstrap.Toast(toast, { delay: 3000 }).show();
    }

    function cardDataFromEl(el) {
        return {
            id: el.dataset.id,
            nom: el.dataset.nom || '',
            situation: el.dataset.situation || '',
            dom: el.dataset.dom || '',
            date: el.dataset.date || '',
            photo: el.dataset.photo || DEFAULT_PHOTO,
            color: el.dataset.color || 'gray',
            present: el.dataset.present === '1'
        };
    }

    function isExpiredDateStr(ds) {
        if (!ds) return false;
        const today = new Date(); today.setHours(0,0,0,0);
        const end   = new Date(ds + 'T00:00:00');
        return end.getTime() < today.getTime();
    }

    function makePresentCardHTML(d) {
        // priorité rouge si expiré, sinon vert (car c'est le conteneur "Présents")
        const borderClass = isExpiredDateStr(d.date) ? 'border-danger-thick' : 'border-success-thick';

        return `
        <div class="col adh-card"
            data-id="${d.id}"
            data-nom="${d.nom}"
            data-situation="${d.situation}"
            data-dom="${d.dom}"
            data-date="${d.date}"
            data-photo="${d.photo}"
            data-present="1"
            data-cgu="1"
            data-color="${d.color}">
            <div class="card text-center shadow-sm">
                <div class="card-body p-3">
                    <img src="${d.photo}" class="avatar avatar-xl mb-2 rounded-circle ${borderClass}" alt="photo">
                    <h3 class="fw-bold mb-0">${d.nom}</h3>
                    <small class="text-muted">
                        <span class="badge bg-${d.color}-lt text-${d.color}">${d.situation}</span> - ${d.dom}
                    </small>
                </div>
            </div>
        </div>`;
    }

    function makeAbsentLiHTML(d) {
        return `
        <li class="list-group-item adh-card" role="button"
            data-id="${d.id}"
            data-nom="${d.nom}"
            data-situation="${d.situation}"
            data-dom="${d.dom}"
            data-date="${d.date}"
            data-photo="${d.photo}"
            data-present="0"
            data-cgu="1"
            data-color="${d.color}"
            data-searchblob="${(d.nom + ' ' + d.situation + ' ' + d.dom).toLowerCase()}">
            <div class="d-flex align-items-center py-2 gap-2">
                <span class="avatar flex-shrink-0" style="background-image:url('${d.photo}'); width:3rem; height:3rem;"></span>
                <div class="min-w-0">
                    <div class="fw-semibold text-truncate">${d.nom}</div>
                    <div class="text-muted small text-truncate">
                        <span class="badge bg-${d.color}-lt text-${d.color}">${d.situation}</span>
                        — ${d.dom}
                    </div>
                </div>
            </div>
        </li>`;
    }

    function nameSortKey(fullName = '') {
        const s = String(fullName || '');
        const m = s.match(/[A-ZÀÂÄÇÉÈÊËÎÏÔÖÙÛÜŸÆŒ' -]{2,}$/u);
        const base = (m ? m[0] : s).trim();
        try {
            return base.normalize('NFD').replace(/\p{Diacritic}/gu,'').toLowerCase();
        } catch {
            return base.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
        }
    }

    function sortTeamContainerByPresenceThenName() {
        const container = document.getElementById('teamContainer');
        if (!container) return;

        const items = Array.from(container.querySelectorAll('.adh-card'));
        items.sort((a, b) => {
            const ap = a.dataset.present === '1' ? 0 : 1; // présents d'abord
            const bp = b.dataset.present === '1' ? 0 : 1;
            if (ap !== bp) return ap - bp;

            return nameSortKey(a.dataset.nom || '')
                .localeCompare(nameSortKey(b.dataset.nom || ''), 'fr', { sensitivity: 'base' });
        });
        items.forEach(it => container.appendChild(it));
    }

    function isExpiredFromDatasetDate(el) {
        const ds = el?.dataset?.date;
        if (!ds) return false; // pas de date => non expiré
        const today = new Date(); today.setHours(0,0,0,0);
        const end   = new Date(ds + 'T00:00:00');
        return end.getTime() < today.getTime();
    }

    function setCardPresentState(cardEl, isPresent) {
        cardEl.dataset.present = isPresent ? "1" : "0";

        const expired = isExpiredFromDatasetDate(cardEl);
        // priorité : rouge > vert > gris
        const toAdd = expired
            ? 'border-danger-thick'
            : (isPresent ? 'border-success-thick' : 'border-secondary-thick');

        // Applique sur les <img> (colonnes gauche & droite)
        cardEl.querySelectorAll('img').forEach(img => {
            img.classList.remove('border-success-thick','border-danger-thick','border-secondary-thick');
            img.classList.add(toAdd);
        });
    }

    function insertSortedChild(container, newEl, selectorForSiblings, getKey) {
        const newKey = getKey(newEl);
        const siblings = Array.from(container.querySelectorAll(selectorForSiblings));
        const target = siblings.find(el => getKey(el).localeCompare(newKey, 'fr', { sensitivity: 'base' }) > 0);
        if (target) container.insertBefore(newEl, target);
        else container.appendChild(newEl);
    }

    function htmlToElement(html) {
        const tpl = document.createElement('template');
        tpl.innerHTML = html.trim();
        return tpl.content.firstElementChild;
    }

    function removeFromPresents(id) {
        document.querySelectorAll(`#presentContainer .adh-card[data-id="${id}"]`).forEach(n => n.closest('.col')?.remove());
    }

    function removeFromAbsents(id) {
        document.querySelectorAll(`#absentsList .adh-card[data-id="${id}"]`).forEach(n => n.remove());
    }

    function addToPresents(data) {
        const wrap = document.getElementById('presentContainer');
        const el = htmlToElement(makePresentCardHTML(data));
        insertSortedChild(wrap, el, '.adh-card', (node) => nameSortKey(node.dataset.nom || ''));
    }

    function addToAbsents(data) {
        const list = document.getElementById('absentsList');
        const el = htmlToElement(makeAbsentLiHTML(data));
        insertSortedChild(list, el, '.adh-card', (node) => nameSortKey(node.dataset.nom || ''));

        const meta = document.getElementById('absentsMeta');
        if (meta) {
            const count = document.querySelectorAll('#absentsList .adh-card').length;
            meta.textContent = `${count} adhérent(s)`;
        }

        const searchEl = document.getElementById('absentsSearch');
        if (searchEl && searchEl.value.trim() !== '') {
            const q = searchEl.value.trim().toLowerCase();
            const blob = (el.getAttribute('data-searchblob') || '').toLowerCase();
            el.classList.toggle('d-none', !blob.includes(q));
        }
    }

    function closeOffcanvasThen(cb) {
        const ocEl = document.getElementById('offcanvasAbsents');
        if (!ocEl) { cb && cb(); return; }

        if (_ocBusy) return;            // évite les appels concurrents
        _ocBusy = true;

        const ocIns = bootstrap.Offcanvas.getOrCreateInstance(ocEl);

        const runCb = () => {
            setTimeout(() => {            // laisse le temps au focus-trap de se libérer
            try { cb && cb(); } finally { _ocBusy = false; }
            }, 20);
        };

        if (_ocPendingOnce) {
            ocEl.removeEventListener('hidden.bs.offcanvas', _ocPendingOnce);
            _ocPendingOnce = null;
        }

        if (ocEl.classList.contains('show')) {
            _ocPendingOnce = function onHiddenOnce() {
            ocEl.removeEventListener('hidden.bs.offcanvas', onHiddenOnce);
            _ocPendingOnce = null;
            runCb();
            };
            ocEl.addEventListener('hidden.bs.offcanvas', _ocPendingOnce, { once: true });
            ocIns.hide();
        } else {
            runCb();
        }
    }

    function openAdherentModalFromCard(card){
        currentAdherentId = card.dataset.id;
        fillPresenceHeaderFromCard(card);
        fillFormFromCard(card);
        setFormToEditMode(currentAdherentId);
        setDeleteAction(currentAdherentId);

        const modalEl = document.getElementById('adherentModal');
        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        const presenceTabTrigger = modalEl.querySelector('[data-bs-toggle="tab"][data-bs-target="#tab-presence"]')
                                || document.getElementById('presence-tab');
        if (presenceTabTrigger) new bootstrap.Tab(presenceTabTrigger).show();
    }

    function resetAllLocally() {
    // snapshot des cartes présentes AVANT modif (on déplacera ces éléments non-MDNI)
    const presentCards = Array.from(document.querySelectorAll('#presentContainer .adh-card'));

    // 1) tout le monde à absent côté UI (y compris MDNI : on met la bordure rouge)
    document.querySelectorAll('.adh-card').forEach(card => setCardPresentState(card, false));

    // 2) déplacer les non-MDNI hors de la grille Présents vers la liste Absents
    presentCards.forEach(card => {
        const d = cardDataFromEl(card);
        const isMDNI = (d.situation || '').toUpperCase() === 'MDNI';
        if (isMDNI) return; // MDNI reste dans sa colonne, bordure déjà mise à jour

        // retire de la grille
        removeFromPresents(d.id);
        // insert trié chez les absents
        addToAbsents({ ...d, present: false });
    });

        sortTeamContainerByPresenceThenName();
    }

    async function loadStatsPeriods() {
        const sel = document.getElementById('statsPeriod');
        if (!sel) return;

        // Nettoyage des anciennes options dynamiques (mois & années)
        Array.from(sel.querySelectorAll('option[data-dynamic="1"], optgroup[data-dynamic="1"]')).forEach(n => n.remove());

        try {
            const r = await fetch("{{ route('presence.stats.periods') }}", { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
            const data = await r.json();

            if (!data.success) throw new Error('Chargement des périodes impossible.');

            const months = data.months || [];
            const years  = data.years  || [];

            if (months.length) {
            const og = document.createElement('optgroup');
            og.label = 'Mois disponibles';
            og.setAttribute('data-dynamic','1');

            months.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.key;        // "YYYY-MM"
                opt.textContent = p.label; // "Octobre 2025"
                opt.setAttribute('data-dynamic','1');
                og.appendChild(opt);
            });
            sel.appendChild(og);
            }

            if (years.length) {
                const og = document.createElement('optgroup');
                og.label = 'Années disponibles';
                og.setAttribute('data-dynamic','1');

                years.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.key;         // "YYYY"
                    opt.textContent = p.label; // "Année 2025"
                    opt.setAttribute('data-dynamic','1');
                    og.appendChild(opt);
                });
                sel.appendChild(og);
            }
        } catch (e) {
            showToast(e.message || 'Erreur lors du chargement des périodes.', 'danger');
        }
    }

    let signaturePad = null;
    let adherentsSignature = [];

    function resizeSignatureCanvas() {
        const canvas = document.getElementById('signatureCanvas');
        if (!canvas || !signaturePad) return;

        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);

        signaturePad.clear();
    }

    async function loadSignatureAdherents() {
        const select = document.getElementById('signatureAdherentId');
        if (!select) return;

        const response = await fetch("{{ route('reglement.adherentsCurrentYear') }}");
        adherentsSignature = await response.json();

        renderSignatureAdherents('');
    }

    function renderSignatureAdherents(search) {
        const select = document.getElementById('signatureAdherentId');
        const q = search.toLowerCase();

        const filtered = adherentsSignature.filter(a =>
            a.nom_adh.toLowerCase().includes(q)
        );

        select.innerHTML = '';

        filtered.forEach(a => {
            const option = document.createElement('option');
            option.value = a.id;
            option.textContent = a.nom_adh;
            select.appendChild(option);
        });
    }

    /* ------------------ init (un seul DOMContentLoaded) ------------------ */
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('btnSignatureReglement')?.addEventListener('click', async () => {
            const modalEl = document.getElementById('signatureReglementModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

            modal.show();
            await loadSignatureAdherents();
        });

        document.getElementById('signatureReglementModal')?.addEventListener('shown.bs.modal', async () => {
            if (!window.SignaturePad) {
                await new Promise((resolve, reject) => {
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/signature_pad@4/dist/signature_pad.umd.min.js';
                    s.onload = resolve;
                    s.onerror = reject;
                    document.head.appendChild(s);
                });
            }

            const canvas = document.getElementById('signatureCanvas');
            signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255,255,255)',
            });

            resizeSignatureCanvas();
        });

        document.getElementById('signatureAdherentSearch')?.addEventListener('input', e => {
            renderSignatureAdherents(e.target.value);
        });

        document.getElementById('clearSignature')?.addEventListener('click', () => {
            signaturePad?.clear();
        });

        document.getElementById('signatureReglementForm')?.addEventListener('submit', async e => {
            e.preventDefault();

            const form = e.currentTarget;
            const btn = form.querySelector('button[type="submit"]');

            if (!signaturePad || signaturePad.isEmpty()) {
                showToast('La signature est obligatoire.', 'danger');
                return;
            }

            document.getElementById('signatureInput').value = signaturePad.toDataURL('image/png');

            btn.disabled = true;

            try {
                const response = await fetch("{{ route('reglement.signature') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: new FormData(form),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Erreur lors de la signature.');
                }

                showToast(data.message, 'success');

                form.reset();
                signaturePad.clear();

                bootstrap.Modal.getInstance(
                    document.getElementById('signatureReglementModal')
                )?.hide();

            } catch (error) {
                showToast(error.message, 'danger');
            } finally {
                btn.disabled = false;
            }
        });

        const searchEl = document.getElementById('absentsSearch');
        const listEl   = document.getElementById('absentsList');
        const metaEl   = document.getElementById('absentsMeta');
        const modalEl  = document.getElementById('adherentModal');

        if (searchEl && listEl) {

            const applyFilter = () => {
                const q = (searchEl.value || '').trim().toLowerCase();
                let shown = 0;
                listEl.querySelectorAll('.list-group-item.adh-card').forEach(li => {
                    const hay = li.getAttribute('data-searchblob') || '';
                    const ok = !q || hay.includes(q);
                    li.classList.toggle('d-none', !ok);
                    if (ok) shown++;
                });
                if (metaEl) metaEl.textContent = `${shown} adhérent(s)`;
            };

            searchEl.addEventListener('input', applyFilter);

            document.getElementById('offcanvasAbsents')
                ?.addEventListener('shown.bs.offcanvas', () => {
                    searchEl.value = '';
                    applyFilter();
                    setTimeout(() => searchEl.focus(), 100);
            });

            document.getElementById('teamContainer')?.addEventListener('click', (e) => {
                const card = e.target.closest('.adh-card');
                if (!card) return;
                closeOffcanvasThen(() => openAdherentModalFromCard(card));
            });

            document.getElementById('presentContainer')?.addEventListener('click', (e) => {
                const card = e.target.closest('.adh-card');
                if (!card) return;
                closeOffcanvasThen(() => openAdherentModalFromCard(card));
            });

            document.getElementById('absentsList')?.addEventListener('click', (e) => {
                const card = e.target.closest('.adh-card');
                if (!card) return;
                closeOffcanvasThen(() => openAdherentModalFromCard(card));
            });

            document.getElementById('btnAddAdherent')?.addEventListener('click', () => {
                closeOffcanvasThen(() => {
                    currentAdherentId = null;
                    setFormToCreateMode();
                    const modalEl = document.getElementById('adherentModal');
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                });
            });

            document.getElementById('offcanvasAbsents')?.addEventListener('hidden.bs.offcanvas', () => {
                const searchEl = document.getElementById('absentsSearch');
                if (searchEl) {
                    searchEl.value = '';
                    const evt = new Event('input');
                    searchEl.dispatchEvent(evt);
                }
            });

            document.getElementById('btnResetAll')?.addEventListener('click', () => {
                const m = new bootstrap.Modal(document.getElementById('resetModal'));
                m.show();
            });

            document.getElementById('resetForm')?.addEventListener('submit', (e) => {
                e.preventDefault();
                const form = e.currentTarget;
                const pwd  = form.querySelector('input[name="admin_password"]').value.trim();
                if (!pwd) return;

                fetch("{{ route('presence.reset') }}", {
                    method: 'POST',
                    headers: {
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ admin_password: pwd })
                })
                .then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok || !data.success) {
                    throw new Error(data.message || 'Échec du reset.');
                    }
                    return data;
                })
                .then((data) => {
                    // maj immédiate du front
                    resetAllLocally();
                    showToast(data.message || 'Reset effectué.', 'success');

                    // ferme et nettoie la modale
                    const modalEl = document.getElementById('resetModal');
                    form.reset();
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                })
                .catch((err) => {
                    showToast(err.message || 'Une erreur est survenue.', 'danger');
                });
            });

            document.getElementById('btnOpenAbsents')?.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const ocEl = document.getElementById('offcanvasAbsents');
                if (!ocEl) return;
                const ocIns = bootstrap.Offcanvas.getOrCreateInstance(ocEl);
                if (!ocEl.classList.contains('show')) {
                    ocIns.show();
                }
            });

            document.getElementById('btnStats')?.addEventListener('click', () => {
                const modalEl = document.getElementById('statsModal');
                const m = bootstrap.Modal.getOrCreateInstance(modalEl);

                // valeurs par défaut à l’ouverture
                document.getElementById('statsPeriod').value = 'all';
                document.getElementById('statsEmail').value = '';
                document.getElementById('statsAdminPassword').value = '';

                m.show();
                loadStatsPeriods();
            });

            document.getElementById('statsForm')?.addEventListener('submit', async (e) => {
                e.preventDefault();

                const form = e.currentTarget;               // capture la ref du <form> tout de suite
                if (!form) return;

                const period = document.getElementById('statsPeriod').value;
                const recipient = document.getElementById('statsEmail').value.trim();
                const admin_password = document.getElementById('statsAdminPassword').value;

                const btn = form.querySelector('button[type="submit"]');
                btn?.setAttribute('disabled', 'disabled');

                try {
                    const r = await fetch("{{ route('presence.stats.export') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type':'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ period, recipient, admin_password })
                    });

                    const data = await r.json().catch(() => ({}));
                    if (!r.ok || !data.success) {
                    throw new Error(data.message || 'Échec de l’export.');
                    }

                    showToast(data.message || 'Export lancé avec succès.', 'success');

                    // Réinitialiser AVANT de cacher la modale (évite les refs nulles)
                    form.reset();

                    const modalEl = document.getElementById('statsModal');
                    bootstrap.Modal.getInstance(modalEl)?.hide();

                } catch (err) {
                    showToast(err.message || 'Une erreur est survenue.', 'danger');
                } finally {
                    btn?.removeAttribute('disabled');
                }
            });

            const grid = document.getElementById('presentContainer');
            if (grid) {
                const items = Array.from(grid.querySelectorAll('.adh-card'));
                items.sort((a,b) => nameSortKey(a.dataset.nom).localeCompare(nameSortKey(b.dataset.nom), 'fr', { sensitivity:'base' }));
                items.forEach(it => grid.appendChild(it));
            }

            const list = document.getElementById('absentsList');
            if (list) {
                const items = Array.from(list.querySelectorAll('.adh-card'));
                items.sort((a,b) => nameSortKey(a.dataset.nom).localeCompare(nameSortKey(b.dataset.nom), 'fr', { sensitivity:'base' }));
                items.forEach(it => list.appendChild(it));
            }

            sortTeamContainerByPresenceThenName();
        }
    });

    (function () {
        const SYNC_MS = 60000; // 12s (ajuste si besoin)
        let lastSig = null;
        let syncing = false;

        async function fetchJSON(url) {
            const r = await fetch(url, { cache: 'no-store' });
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }

        function sortContainerByName(container, itemSelector) {
            if (!container) return;
            const items = Array.from(container.querySelectorAll(itemSelector));
            items.sort((a,b) => nameSortKey(a.dataset.nom || '')
            .localeCompare(nameSortKey(b.dataset.nom || ''), 'fr', { sensitivity:'base' }));
            items.forEach(it => container.appendChild(it));
        }

        function applyCurrentSearchFilter() {
            const searchEl = document.getElementById('absentsSearch');
            const listEl   = document.getElementById('absentsList');
            const metaEl   = document.getElementById('absentsMeta');
            if (!searchEl || !listEl) return;

            const q = (searchEl.value || '').trim().toLowerCase();
            let shown = 0;
            listEl.querySelectorAll('.list-group-item.adh-card').forEach(li => {
            const hay = (li.getAttribute('data-searchblob') || '').toLowerCase();
            const ok = !q || hay.includes(q);
            li.classList.toggle('d-none', !ok);
            if (ok) shown++;
            });
            if (metaEl) metaEl.textContent = `${shown} adhérent(s)`;
        }

        async function replacePartials() {
            const data = await fetchJSON('{{ route('presence.partials') }}');

            const teamCt    = document.getElementById('teamContainer');
            const presentCt = document.getElementById('presentContainer');
            const absList   = document.getElementById('absentsList');

            // préserver scroll du tiroir
            const prevScroll = absList ? absList.scrollTop : 0;

            if (teamCt && typeof data.team === 'string')      teamCt.innerHTML = data.team;
            if (presentCt && typeof data.presents === 'string') presentCt.innerHTML = data.presents;
            if (absList && typeof data.absents === 'string')  absList.innerHTML = data.absents;

            // tri alpha (réutilise ta logique)
            sortTeamContainerByPresenceThenName();
            sortContainerByName(presentCt, '.adh-card');
            sortContainerByName(absList, '.adh-card');

            // maj compteur + filtre saisi
            applyCurrentSearchFilter();

            if (absList) absList.scrollTop = prevScroll;
        }

        function offcanvasOpen() {
            const oc = document.getElementById('offcanvasAbsents');
            return !!oc && oc.classList.contains('show');
        }

        async function poll() {
            if (syncing || document.hidden) return;
            syncing = true;
            try {
            const v = await fetchJSON('{{ route('sync.version', ['scope' => 'presence']) }}');
            const sig = v?.presence?.sig || null;

            if (sig && sig !== lastSig) {
                // ne maj que si aucune modale/tiroir ouverts (évite les conflits focus)
                if (!document.body.classList.contains('modal-open') && !offcanvasOpen()) {
                await replacePartials();
                lastSig = sig;
                }
                // sinon: on attend le prochain tour (pour ne pas perdre la mise à jour)
            }
            } catch (e) {
            console.debug('presence sync error:', e);
            } finally {
            syncing = false;
            }
        }

        // relancer juste après fermeture modale/tiroir pour rattraper une MAJ
        document.getElementById('adherentModal')
            ?.addEventListener('hidden.bs.modal', () => setTimeout(poll, 150));
        document.getElementById('offcanvasAbsents')
            ?.addEventListener('hidden.bs.offcanvas', () => setTimeout(poll, 150));

        document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

        setInterval(poll, SYNC_MS);
        poll(); // premier tir
    })();

</script>
