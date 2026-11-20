{{--
    Disponibilités du formulaire d'événement (tableau de bord et liste des événements).
    Indicatif, non bloquant : dans les listes, une personne / salle occupée est grisée et suffixée
    « — affecté(e) à n événements » (une liste déroulante native n'accepte que du texte) ;
    sous chaque ligne, un petit message donne la raison pour l'élément sélectionné.
--}}
<script>
    window.dispoState = window.dispoState || { data: null, seq: 0 };

    function bindDisponibiliteEvents() {
        const debut = document.querySelector('[name="date_heure_debut"]');
        const fin = document.querySelector('[name="date_heure_fin"]');

        if (!debut || !fin) return;

        window.dispoState.data = null;

        debut.addEventListener('change', checkDisponibilites);
        fin.addEventListener('change', checkDisponibilites);

        const form = debut.closest('form');
        if (form) {
            // Changement d'animateur / de salle : la raison affichée suit la sélection.
            form.addEventListener('change', (e) => {
                if (e.target.matches('select[data-type="user"], select[data-type="salle"]')) {
                    reappliquerDisponibilites();
                }
            });

            // Alpine (re)construit les lignes (ajout, suppression) : on réapplique le marquage.
            const estUneLigne = (n) => n.nodeType === 1
                && (n.matches('.dispo-row, select[data-type]') || n.querySelector('select[data-type]'));
            new MutationObserver((mutations) => {
                if (mutations.some(m => [...m.addedNodes, ...m.removedNodes].some(estUneLigne))) {
                    reappliquerDisponibilites();
                }
            }).observe(form, { childList: true, subtree: true });
        }

        // Vérification initiale, une fois qu'Alpine a rempli les dates (x-model) et les listes :
        // appelée trop tôt, les champs sont encore vides et rien n'est marqué.
        setTimeout(checkDisponibilites, 0);
    }

    function checkDisponibilites() {
        const debutInput = document.querySelector('[name="date_heure_debut"]');
        const finInput = document.querySelector('[name="date_heure_fin"]');

        if (!debutInput || !finInput) return;

        const debut = debutInput.value;
        const fin = finInput.value;

        if (!debut || !fin) return;

        const eventId = debutInput.closest('form')?.dataset.eventId;
        const exclude = eventId ? `&exclude=${encodeURIComponent(eventId)}` : '';
        const seq = ++window.dispoState.seq;   // seule la dernière réponse compte

        fetch(`/evenements/disponibilites?debut=${encodeURIComponent(debut)}&fin=${encodeURIComponent(fin)}${exclude}`)
            .then(res => res.json())
            .then(data => {
                if (seq !== window.dispoState.seq) return;
                window.dispoState.data = data;
                appliquerDisponibilites(data);
            })
            .catch(err => console.error('checkDisponibilites', err));
    }

    function reappliquerDisponibilites() {
        if (window.dispoState.data) {
            appliquerDisponibilites(window.dispoState.data);
        }
    }

    function appliquerDisponibilites(data) {
        const IGNORE_IDS = ['0', '16'];
        const config = {
            user: { reasons: data.users || {}, affecte: 'affecté(e)' },
            salle: { reasons: data.salles || {}, affecte: 'affectée' },
        };

        // « — affecté(e) à 2 événements » (chevauchant le créneau) ; à défaut, le motif
        // propre à la personne : « — en congé », « — horaire modifié ».
        const suffixe = (raisons, affecte) => {
            const n = raisons.filter(r => r.startsWith('Occupé')).length;
            if (n > 0) return ` — ${affecte} à ${n} événement${n > 1 ? 's' : ''}`;
            if (raisons.some(r => r.startsWith('En congé'))) return ' — en congé';
            return ' — horaire modifié';
        };

        Object.entries(config).forEach(([type, { reasons, affecte }]) => {
            document.querySelectorAll(`select[data-type="${type}"]`).forEach(select => {
                select.querySelectorAll('option').forEach(opt => {
                    if (opt.dataset.label === undefined) {
                        opt.dataset.label = opt.textContent.trim();
                    }

                    const busy = !IGNORE_IDS.includes(opt.value)
                        && Object.prototype.hasOwnProperty.call(reasons, opt.value);

                    opt.textContent = opt.dataset.label + (busy ? suffixe(reasons[opt.value], affecte) : '');
                    opt.classList.toggle('dispo-occupe', busy);
                    opt.style.color = busy ? '#9aa0ac' : '';

                    if (busy) {
                        opt.title = reasons[opt.value].join(' • ');
                    } else {
                        opt.removeAttribute('title');
                    }
                });

                const small = select.closest('.dispo-row')?.querySelector('.dispo-reason');
                if (!small) return;

                const selectedReasons = !IGNORE_IDS.includes(select.value) ? reasons[select.value] : null;

                if (selectedReasons && selectedReasons.length) {
                    small.textContent = '⚠ ' + selectedReasons.join(' • ');
                    small.classList.remove('d-none');
                } else {
                    small.textContent = '';
                    small.classList.add('d-none');
                }
            });
        });
    }
</script>
