{{-- Styles propres à Projets & tâches (pas de build front en production : CSS dans la vue). --}}
<style>
    .pt-carre { position: relative; display: inline-flex; align-items: center; justify-content: center; flex: none; border-radius: 6px; color: #fff; cursor: default; }
    .pt-carre:hover::after { content: attr(data-tip); position: absolute; bottom: calc(100% + 6px); left: 50%; transform: translateX(-50%); z-index: 20; width: max-content; max-width: 200px; padding: 4px 8px; border-radius: 6px; background: #182433; color: #fff; font-size: 12px; font-weight: 500; text-align: center; pointer-events: none; }

    .pt-echeance { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 600; white-space: nowrap; }
    .pt-echeance--retard { background: #d639391f; color: #d63939; }
    .pt-echeance--bientot { background: #f59f001f; color: #b07100; }
    .pt-echeance--normal { background: #4299e11f; color: #1f6fb5; }
    .pt-echeance--calme, .pt-echeance--neutre { background: #f1f3f5; color: #667382; }

    .pt-avatars { display: inline-flex; padding-left: 7px; vertical-align: middle; }
    .pt-avatar { display: inline-flex; align-items: center; justify-content: center; flex: none; margin-left: -7px; border: 2px solid #fff; border-radius: 50%; background: #e9ecef; color: #667382; font-weight: 700; }
    .pt-avatar--moi { background: color-mix(in srgb, var(--tblr-primary) 18%, white); color: var(--tblr-primary); }

    .pt-anneau { position: relative; flex: none; }
    .pt-anneau__texte { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; line-height: 1.05; }
    .pt-anneau__nb { font-weight: 800; color: #182433; }
    .pt-anneau__sur { color: #9aa0ac; font-weight: 600; }
    .pt-anneau__lib { font-size: 10px; color: #667382; }

    /* Bouton d'action léger (fond teinté, comme les badges d'événements) */
    .pt-btn-doux { border: 0; background: color-mix(in srgb, var(--tblr-primary) 14%, white); color: var(--tblr-primary); font-weight: 700; }
    .pt-btn-doux:hover, .pt-btn-doux:focus-visible { background: color-mix(in srgb, var(--tblr-primary) 24%, white); color: var(--tblr-primary); }
    .pt-btn-doux:disabled { opacity: .6; }

    .pt-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 6px; font-size: 12px; font-weight: 700; white-space: nowrap; }

    .pt-badge--retard { background: #d639391f; color: #d63939; }

    .pt-kpis { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; }
    .pt-kpi { display: flex; align-items: center; gap: 10px; width: 178px; padding: 10px 12px; border: 1px solid #e6e7e9; border-radius: 12px; background: #fff; }
    .pt-kpi__rond { display: inline-flex; align-items: center; justify-content: center; flex: none; width: 36px; height: 36px; border-radius: 50%; }
    .pt-kpi__nb { font-size: 21px; font-weight: 800; line-height: 1; }
    .pt-kpi__lib { font-size: 11.5px; color: #667382; }

    .pt-grille { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 16px; }
    .pt-carte { display: flex; flex-direction: column; gap: 16px; padding: 18px 18px 14px; border: 1px solid #e6e7e9; border-top: 4px solid var(--pc); border-radius: 14px; background: #fff; color: inherit; text-decoration: none; transition: box-shadow .15s, transform .15s; }
    .pt-carte:hover { box-shadow: 0 6px 18px rgba(24, 36, 51, .1); transform: translateY(-1px); color: inherit; }
    .pt-carte--clos { opacity: .8; }
    .pt-carte__pied { display: flex; flex-direction: column; gap: 10px; padding-top: 12px; border-top: 1px solid #f1f3f5; margin-top: auto; }
    .pt-carte__ligne { display: flex; align-items: center; min-height: 28px; }
    .pt-carte__ligne--ecarte { justify-content: space-between; gap: 8px; }
    .pt-picto { display: inline-flex; align-items: center; justify-content: center; flex: none; border-radius: 10px; }
    .pt-carte__titre { font-size: 15px; font-weight: 700; line-height: 1.25; }
    .pt-carte__desc { margin-top: 3px; font-size: 12px; line-height: 1.4; color: #667382; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .pt-carte__corps { display: flex; align-items: center; gap: 18px; flex: 1; }
    .pt-compteurs { display: flex; flex-wrap: wrap; gap: 8px 12px; }
    .pt-compteur { display: inline-flex; align-items: center; gap: 5px; font-size: 13px; font-weight: 700; color: #182433; }

    .pt-filtres { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 14px; padding: 10px 12px; border: 1px solid #e6e7e9; border-radius: 12px; background: #fff; }
    .pt-filtres__sep { width: 1px; height: 22px; background: #dee2e6; }
    .pt-filtres__t { font-size: 11.5px; color: #9aa0ac; }
    .pt-pastille { --pc: var(--tblr-primary); display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px 4px 4px; border: 1px solid var(--tblr-border-color, #dee2e6); border-radius: 8px; background: #fff; color: #182433; font-size: 12px; font-weight: 600; cursor: pointer; }
    .pt-pastille--texte { padding: 5px 12px; border-radius: 999px; }
    .pt-pastille:hover { border-color: var(--pc); }
    .pt-pastille.on { border-color: var(--pc); background: color-mix(in srgb, var(--pc) 13%, white); box-shadow: inset 0 0 0 1px var(--pc); }
    .pt-pastille--texte.on { color: var(--pc); }
    .pt-vide { padding: 40px; border-radius: 12px; background: #fff; color: #9aa0ac; text-align: center; }

    .pt-fiche { margin-bottom: 16px; padding: 16px; border: 1px solid #e6e7e9; border-radius: 14px; background: #fff; }
    .pt-fiche__entete { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; padding: 14px 16px; border-radius: 10px; }
    .pt-meta { display: grid; grid-template-columns: repeat(3, auto); gap: 4px 26px; justify-content: start; margin-top: 14px; font-size: 12.5px; }
    .pt-meta__k { color: #667382; }
    .pt-panneau { padding: 14px; border: 1px solid #e6e7e9; border-radius: 12px; background: #fff; }
    .pt-panneau h3 { margin: 0 0 10px; font-size: 14px; font-weight: 700; }

    .pt-mini { display: flex; flex-direction: column; gap: 8px; padding: 10px 12px; border: 1px solid #e6e7e9; border-left: 4px solid var(--sc); border-radius: 10px; background: #fff; }
    .pt-mini--clos { opacity: .5; }
    .pt-mini__titre { flex: 1; min-width: 0; font-size: 13px; font-weight: 700; line-height: 1.3; }
    .pt-mini__raison { padding: 4px 8px; border-radius: 6px; font-size: 11px; }
    .pt-grille-mini { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 10px; }

    /*
     * Formulaires en modale : le <form> s'intercale entre .modal-content et .modal-body, ce qui casse le
     * défilement de Bootstrap (le bas de la fenêtre, avec le bouton d'enregistrement, restait inatteignable
     * sur un écran peu haut). Le formulaire devient donc lui-même la colonne flex bornée à la hauteur de l'écran.
     */
    .modal-dialog-scrollable .modal-content > form { display: flex; flex-direction: column; min-height: 0; max-height: calc(100vh - 3.5rem); max-height: calc(100dvh - 3.5rem); overflow: hidden; }
    .modal-dialog-scrollable .modal-content > form > .modal-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; }
    .modal-dialog-scrollable .modal-content > form > .modal-header,
    .modal-dialog-scrollable .modal-content > form > .modal-footer { flex: none; }

    .pt-pick { cursor: pointer; margin: 0; }
    .pt-pick__pastille { display: block; width: 26px; height: 26px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px #dee2e6; }
    .pt-pick__icone { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border: 1px solid #dee2e6; border-radius: 8px; background: #fff; color: #495057; }
    .pt-pick:hover .pt-pick__icone { border-color: var(--tblr-primary); }
    .pt-pick input:checked + .pt-pick__pastille { box-shadow: 0 0 0 2px var(--tblr-primary); }
    .pt-pick input:checked + .pt-pick__icone { border-color: var(--tblr-primary); background: color-mix(in srgb, var(--tblr-primary) 12%, white); color: var(--tblr-primary); }
    .pt-pick input:focus-visible + span { outline: 2px solid var(--tblr-primary); outline-offset: 2px; }
    .pt-pick-etat { margin: 0; cursor: pointer; }
    .pt-pick-etat input:checked + .pt-pastille { border-color: var(--pc); background: color-mix(in srgb, var(--pc) 13%, white); box-shadow: inset 0 0 0 1px var(--pc); }

    .pt-seg { display: inline-flex; padding: 3px; border-radius: 10px; background: #e9ecef; }
    .pt-seg__btn { display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; border-radius: 8px; color: #495057; font-size: 13px; font-weight: 600; text-decoration: none; }
    .pt-seg__btn:hover { color: #182433; }
    .pt-seg__btn.on { background: #fff; color: var(--tblr-primary); box-shadow: 0 1px 3px rgba(24, 36, 51, .15); }

    .pt-personne { padding: 0; border: 2px solid transparent; border-radius: 50%; background: none; line-height: 0; cursor: pointer; opacity: .75; }
    .pt-personne:hover { opacity: 1; }
    .pt-personne.on { border-color: var(--tblr-primary); opacity: 1; }

    .pt-groupe { margin-bottom: 10px; border: 1px solid #e6e7e9; border-radius: 12px; background: #fff; overflow: hidden; }
    .pt-groupe__tete { display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 14px; border: 0; border-bottom: 1px solid #f1f3f5; background: none; color: #182433; text-align: left; cursor: pointer; }
    .pt-groupe--ferme .pt-groupe__tete { border-bottom-color: transparent; }
    .pt-groupe__nom { font-size: 14px; font-weight: 700; }
    .pt-groupe__nb { font-size: 11.5px; color: #667382; }
    .pt-groupe__fait { padding: 2px 8px; border-radius: 6px; font-size: 12px; font-weight: 700; }
    .pt-groupe__corps { padding: 12px 14px; background: #fafbfc; }
    .pt-chevron { display: inline-flex; color: #9aa0ac; transition: transform .15s; }
    .pt-groupe--ferme .pt-chevron { transform: rotate(-90deg); }
    .pt-mini--clic { cursor: pointer; transition: box-shadow .15s; }
    .pt-mini--clic:hover, .pt-mini--clic:focus-visible { box-shadow: 0 3px 10px rgba(24, 36, 51, .12); outline: none; }

    @media (max-width: 576px) { .pt-meta { grid-template-columns: 1fr; } .pt-kpi { width: calc(50% - 5px); padding: 8px 10px; } }
</style>
