{{--
    Fenêtre modale des tâches + script : fiche (clic ou Entrée sur [data-tache], ou adresse ?tache=ID),
    création ([data-tache-nouvelle], avec data-projet facultatif), modification, changement de statut
    et suppression. Les formulaires [data-ajax-form] sont envoyés en Ajax ; les erreurs de validation (422)
    s'affichent dans le bloc [data-erreurs] du formulaire.
    Inclus par la page Tâches et par la fiche projet.
--}}
<div id="tacheModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" id="tacheModalContenu">Chargement…</div>
    </div>
</div>

<script>
    (function () {
        const modalEl = document.getElementById('tacheModal');
        const contenu = document.getElementById('tacheModalContenu');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        let modifie = false; // la page derrière est périmée : on la recharge à la fermeture

        const erreurHtml = '<div class="modal-body"><div class="alert alert-danger mb-0">Impossible de charger le contenu.</div></div>';

        function charger(url) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            contenu.innerHTML = 'Chargement…';
            modal.show();
            return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.ok ? r.text() : Promise.reject(r.status))
                .then(html => { contenu.innerHTML = html; })
                .catch(() => { contenu.innerHTML = erreurHtml; });
        }

        const ouvrirTache = (id) => charger(`/taches/${id}`);

        modalEl.addEventListener('hidden.bs.modal', () => {
            if (modifie) location.href = location.pathname;
        });

        document.addEventListener('click', (e) => {
            const t = e.target;

            const nouvelle = t.closest('[data-tache-nouvelle]');
            if (nouvelle) {
                e.preventDefault();
                const projet = nouvelle.dataset.projet;
                return void charger('/taches/create' + (projet ? `?projet=${projet}` : ''));
            }

            const modifier = t.closest('[data-modifier-tache]');
            if (modifier) return void charger(`/taches/${modifier.dataset.modifierTache}/edit`);

            const retour = t.closest('[data-ouvrir-tache]');
            if (retour) return void ouvrirTache(retour.dataset.ouvrirTache);

            // Choix du statut : la raison est exigée pour « En attente » et « Bloqué ».
            const choix = t.closest('[data-choix-statut]');
            if (choix) {
                const form = choix.closest('form');
                const valeur = form.querySelector('[data-statut-valeur]');
                valeur.value = choix.dataset.choixStatut;
                form.querySelectorAll('[data-choix-statut]').forEach(b => b.classList.toggle('on', b === choix));
                const change = valeur.value !== valeur.dataset.courant;
                form.querySelector('[data-enregistrer-statut]').classList.toggle('d-none', !change);
                form.querySelector('[data-raison-bloc]').classList.toggle('d-none', !(change && choix.dataset.exige === '1'));
                return;
            }

            if (t.closest('[data-supprimer-demande]')) {
                contenu.querySelector('[data-suppr-confirm]')?.classList.remove('d-none');
                return;
            }
            if (t.closest('[data-suppr-annuler]')) {
                contenu.querySelector('[data-suppr-confirm]')?.classList.add('d-none');
                return;
            }
            const suppr = t.closest('[data-supprimer]');
            if (suppr) {
                suppr.disabled = true;
                fetch(`/taches/${suppr.dataset.supprimer}`, {
                    method: 'DELETE',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                }).then(r => {
                    if (!r.ok) throw new Error(r.status);
                    modifie = true;
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                }).catch(() => { suppr.disabled = false; });
                return;
            }

            const carte = t.closest('[data-tache]');
            if (carte) ouvrirTache(carte.dataset.tache);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            const carte = e.target.closest?.('[data-tache]');
            if (carte) { e.preventDefault(); ouvrirTache(carte.dataset.tache); }
        });

        document.addEventListener('submit', async (e) => {
            const form = e.target.closest('[data-ajax-form]');
            if (!form) return;
            e.preventDefault();

            const bloc = form.querySelector('[data-erreurs]');
            const bouton = form.querySelector('[type="submit"]');
            bloc?.classList.add('d-none');
            if (bouton) bouton.disabled = true;

            try {
                const r = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                });

                if (r.status === 422) {
                    const j = await r.json();
                    const messages = Object.values(j.errors || {}).flat();
                    bloc.innerHTML = messages.map(m => `<div>${m.replace(/</g, '&lt;')}</div>`).join('');
                    bloc.classList.remove('d-none');
                    bloc.scrollIntoView({ block: 'nearest' });
                    return;
                }
                if (!r.ok) throw new Error(r.status);

                const j = await r.json();
                modifie = true;

                if (form.dataset.apres === 'ouvrir-nouvelle') {
                    // on recharge la page en ouvrant directement la tâche créée
                    modifie = false;
                    location.href = location.pathname + '?tache=' + j.id;
                } else {
                    ouvrirTache(j.id ?? form.dataset.id);
                }
            } catch (err) {
                if (bloc) {
                    bloc.textContent = 'Une erreur est survenue, réessayez.';
                    bloc.classList.remove('d-none');
                }
            } finally {
                if (bouton) bouton.disabled = false;
            }
        });

        const demande = new URLSearchParams(location.search).get('tache');
        if (demande) document.addEventListener('DOMContentLoaded', () => ouvrirTache(demande));
    })();
</script>
