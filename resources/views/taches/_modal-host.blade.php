{{--
    Fenêtre modale « Détail tâche » + script d'ouverture (clic ou Entrée sur une mini-carte [data-tache],
    ou adresse ?tache=ID). Inclus par la page Tâches et par la fiche projet.
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

        function ouvrirTache(id) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            contenu.innerHTML = 'Chargement…';
            modal.show();
            fetch(`/taches/${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.ok ? r.text() : Promise.reject(r.status))
                .then(html => { contenu.innerHTML = html; })
                .catch(() => { contenu.innerHTML = '<div class="modal-body"><div class="alert alert-danger mb-0">Impossible de charger la tâche.</div></div>'; });
        }

        document.addEventListener('click', (e) => {
            const carte = e.target.closest('[data-tache]');
            if (carte) ouvrirTache(carte.dataset.tache);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            const carte = e.target.closest?.('[data-tache]');
            if (carte) { e.preventDefault(); ouvrirTache(carte.dataset.tache); }
        });

        const demande = new URLSearchParams(location.search).get('tache');
        if (demande) document.addEventListener('DOMContentLoaded', () => ouvrirTache(demande));
    })();
</script>
