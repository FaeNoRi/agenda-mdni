{{--
    Fenêtre modale des projets (création / modification) + script : ouverture ([data-projet-nouveau],
    [data-modifier-projet]), changement d'état, suppression. Les formulaires [data-ajax-form][data-hote="projet"]
    sont envoyés en Ajax ; les erreurs de validation (422) s'affichent dans leur bloc [data-erreurs].
--}}
<div id="projetModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" id="projetModalContenu">Chargement…</div>
    </div>
</div>

<script>
    (function () {
        const modalEl = document.getElementById('projetModal');
        const contenu = document.getElementById('projetModalContenu');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        function charger(url) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            contenu.innerHTML = 'Chargement…';
            modal.show();
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.ok ? r.text() : Promise.reject(r.status))
                .then(html => { contenu.innerHTML = html; })
                .catch(() => { contenu.innerHTML = '<div class="modal-body"><div class="alert alert-danger mb-0">Impossible de charger le formulaire.</div></div>'; });
        }

        document.addEventListener('click', (e) => {
            const t = e.target;

            if (t.closest('[data-projet-nouveau]')) {
                e.preventDefault();
                return void charger('/projets/create');
            }
            const modifier = t.closest('[data-modifier-projet]');
            if (modifier) return void charger(`/projets/${modifier.dataset.modifierProjet}/edit`);

            // Choix de l'état : la raison est exigée pour « En attente » et « Bloqué ».
            const choix = t.closest('[data-choix-etat]');
            if (choix) {
                const form = choix.closest('form');
                const valeur = form.querySelector('[data-etat-valeur]');
                valeur.value = choix.dataset.choixEtat;
                form.querySelectorAll('[data-choix-etat]').forEach(b => b.classList.toggle('on', b === choix));
                const change = valeur.value !== valeur.dataset.courant;
                form.querySelector('[data-enregistrer-etat]').classList.toggle('d-none', !change);
                form.querySelector('[data-raison-bloc]').classList.toggle('d-none', !(change && choix.dataset.exige === '1'));
                return;
            }

            if (t.closest('[data-supprimer-projet-demande]')) {
                document.querySelector('[data-suppr-projet-confirm]')?.classList.remove('d-none');
                return;
            }
            if (t.closest('[data-suppr-projet-annuler]')) {
                document.querySelector('[data-suppr-projet-confirm]')?.classList.add('d-none');
                return;
            }
            const suppr = t.closest('[data-supprimer-projet]');
            if (suppr) {
                suppr.disabled = true;
                fetch(`/projets/${suppr.dataset.supprimerProjet}`, {
                    method: 'DELETE',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                }).then(r => {
                    if (!r.ok) throw new Error(r.status);
                    location.href = '/projets';
                }).catch(() => { suppr.disabled = false; });
            }
        });

        document.addEventListener('submit', async (e) => {
            const form = e.target.closest('[data-ajax-form][data-hote="projet"]');
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
                if (form.dataset.apres === 'ouvrir-projet') {
                    location.href = '/projets/' + j.id;
                } else {
                    location.reload();
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
    })();
</script>
