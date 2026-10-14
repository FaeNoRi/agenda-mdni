{{--
    Couleurs de l'interface (surfaces, bordures, textes) sous forme de variables, claires par défaut.
    Le mode sombre (profil → Apparence) pose data-bs-theme="dark" sur <html> et redéfinit ces variables ;
    Tabler fournit lui-même les siennes pour ses composants (cartes, formulaires, modales, tableaux).
    Les écrans écrivent leurs couleurs avec var(--c-…) plutôt qu'avec des valeurs fixes.
--}}
<style id="variables-ui">
    :root {
        --c-page: #f3f4f6;
        --c-surface: #fff;
        --c-surface-2: #f6f8fb;
        --c-surface-3: #f1f3f5;
        --c-bord: #e6e7e9;
        --c-bord-soft: #f1f3f5;
        --c-fill: #e9ecef;
        --c-text: #182433;
        --c-muted: #667382;
    }

    html[data-bs-theme="dark"] {
        color-scheme: dark;
        --c-page: #111827;
        --c-surface: #1f2937;
        --c-surface-2: #1a2331;
        --c-surface-3: #273244;
        --c-bord: #374151;
        --c-bord-soft: #2b3646;
        --c-fill: #374151;
        --c-text: #e5e7eb;
        --c-muted: #9ca3af;
        --c-accent: color-mix(in srgb, var(--tblr-primary) 45%, #fff);   /* accent lisible sur fond sombre */
    }

    /* ---- Classes utilitaires encore présentes dans les écrans historiques (Tailwind, Bootstrap) ---- */
    html[data-bs-theme="dark"] body { background-color: var(--c-page); color: var(--c-text); }
    html[data-bs-theme="dark"] .bg-gray-100:not(nav *) { background-color: var(--c-page) !important; }
    html[data-bs-theme="dark"] .bg-gray-50:not(nav *),
    html[data-bs-theme="dark"] .bg-light:not(nav *) { background-color: var(--c-surface-2) !important; }
    html[data-bs-theme="dark"] .bg-white:not(nav *) { background-color: var(--c-surface) !important; }
    html[data-bs-theme="dark"] .text-gray-900:not(nav *),
    html[data-bs-theme="dark"] .text-gray-800:not(nav *),
    html[data-bs-theme="dark"] .text-gray-700:not(nav *),
    html[data-bs-theme="dark"] .text-dark:not(nav *) { color: var(--c-text) !important; }
    html[data-bs-theme="dark"] .text-gray-600:not(nav *),
    html[data-bs-theme="dark"] .text-gray-500:not(nav *),
    html[data-bs-theme="dark"] .text-gray-400:not(nav *) { color: var(--c-muted) !important; }
    html[data-bs-theme="dark"] .border-gray-100:not(nav *),
    html[data-bs-theme="dark"] .border-gray-200:not(nav *),
    html[data-bs-theme="dark"] .border-gray-300:not(nav *),
    html[data-bs-theme="dark"] .border:not(nav *) { border-color: var(--c-bord) !important; }
    html[data-bs-theme="dark"] .hover\:bg-gray-100:hover:not(nav *) { background-color: var(--c-surface-3) !important; }

    /* ---- Champs de formulaire non Tabler ---- */
    html[data-bs-theme="dark"] input:not([type="checkbox"]):not([type="radio"]):not([type="color"]):not([type="range"]):not([type="submit"]):not([type="button"]),
    html[data-bs-theme="dark"] select,
    html[data-bs-theme="dark"] textarea {
        background-color: var(--c-surface-2);
        color: var(--c-text);
        border-color: var(--c-bord);
    }
    html[data-bs-theme="dark"] ::placeholder { color: var(--c-muted); opacity: .8; }

    /* ---- Couleur d'accent sur fond sombre : éclaircie pour rester lisible (bleu #0000ff, indigo, vert…) ---- */
    html[data-bs-theme="dark"] .btn-outline-primary { --tblr-btn-color: color-mix(in srgb, var(--tblr-primary) 45%, #fff); --tblr-btn-border-color: color-mix(in srgb, var(--tblr-primary) 70%, #fff); }
    html[data-bs-theme="dark"] .text-primary { color: color-mix(in srgb, var(--tblr-primary) 45%, #fff) !important; }
    html[data-bs-theme="dark"] .btn.pt-btn-doux:not(:hover):not(:focus-visible) { color: color-mix(in srgb, var(--bc) 50%, #fff); }
    html[data-bs-theme="dark"] .fpill.on,
    html[data-bs-theme="dark"] .pt-pastille--texte.on { color: color-mix(in srgb, var(--pc) 50%, #fff); }
    html[data-bs-theme="dark"] .pt-seg__btn.on,
    html[data-bs-theme="dark"] .dock-rond--blanc,
    html[data-bs-theme="dark"] .pt-avatar--moi,
    html[data-bs-theme="dark"] .pt-pick input:checked + .pt-pick__icone { color: var(--c-accent); }
    html[data-bs-theme="dark"] main a:not([class]) { color: var(--c-accent); }
    html[data-bs-theme="dark"] .pt-personne.on { border-color: color-mix(in srgb, var(--tblr-primary) 55%, #fff); }

    /* ---- Composants tiers ---- */
    html[data-bs-theme="dark"] .fc {
        --fc-page-bg-color: var(--c-surface);
        --fc-neutral-bg-color: var(--c-surface-2);
        --fc-neutral-text-color: var(--c-muted);
        --fc-border-color: var(--c-bord);
        --fc-list-event-hover-bg-color: var(--c-surface-3);
        --fc-today-bg-color: rgba(255, 255, 255, .06);
        --fc-non-business-color: rgba(255, 255, 255, .03);
        --fc-event-text-color: #fff;
    }
    html[data-bs-theme="dark"] .fc a { color: inherit; }
    html[data-bs-theme="dark"] .swal2-popup { background: var(--c-surface); color: var(--c-text); }
    html[data-bs-theme="dark"] .swal2-title,
    html[data-bs-theme="dark"] .swal2-html-container { color: var(--c-text); }
    html[data-bs-theme="dark"] .flatpickr-calendar { background: var(--c-surface); color: var(--c-text); box-shadow: 0 0 0 1px var(--c-bord), 0 8px 24px rgba(0, 0, 0, .4); }
    html[data-bs-theme="dark"] .flatpickr-day,
    html[data-bs-theme="dark"] .flatpickr-weekday,
    html[data-bs-theme="dark"] .flatpickr-month,
    html[data-bs-theme="dark"] .flatpickr-current-month input.cur-year { color: var(--c-text); fill: var(--c-text); }
    html[data-bs-theme="dark"] .flatpickr-day:hover { background: var(--c-surface-3); border-color: var(--c-bord); }
    html[data-bs-theme="dark"] table.dataTable tbody tr { background-color: transparent; }
    html[data-bs-theme="dark"] .dataTables_wrapper,
    html[data-bs-theme="dark"] .dataTables_wrapper .dataTables_info,
    html[data-bs-theme="dark"] .dataTables_wrapper .dataTables_length,
    html[data-bs-theme="dark"] .dataTables_wrapper .dataTables_filter { color: var(--c-text); }
</style>
