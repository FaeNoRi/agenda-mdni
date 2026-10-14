{{--
    Choix de la couleur de l'interface : palette classique, pastels et couleur libre. Aperçu en direct sur
    toute la page ; rien n'est enregistré avant « Enregistrer la couleur ». Variables : $user.
--}}
@php
    use App\Support\ThemeColors;

    $actuelle = ThemeColors::resolve($user->theme);
    $libre = !isset(ThemeColors::CLASSIQUES[$actuelle['nom']]) && !isset(ThemeColors::PASTELS[$actuelle['nom']]);
@endphp

<header>
    <h2 class="text-lg font-medium text-gray-900">Apparence</h2>
    <p class="pf-sous" style="margin-top: 4px;">Le mode d'affichage et la couleur de votre interface. Ces réglages ne concernent que votre compte.</p>
</header>

{{-- Mode d'affichage : clair (par défaut) ou sombre. Appliqué et enregistré dès le choix. --}}
<style>
    .pf-modes { display: grid; grid-template-columns: repeat(2, minmax(0, 220px)); gap: 12px; margin-bottom: 6px; }
    .pf-mode { display: flex; flex-direction: column; gap: 8px; padding: 10px; border: 2px solid var(--c-bord); border-radius: 12px; background: var(--c-surface); color: var(--c-text); text-align: left; cursor: pointer; }
    .pf-mode:hover { border-color: var(--tblr-primary); }
    .pf-mode[aria-pressed="true"] { border-color: var(--tblr-primary); box-shadow: 0 0 0 1px var(--tblr-primary); }
    .pf-mode__vue { display: flex; flex-direction: column; gap: 5px; padding: 8px; border-radius: 8px; border: 1px solid rgba(128, 128, 128, .25); }
    .pf-mode__vue i { display: block; height: 7px; border-radius: 4px; }
    .pf-mode__vue--clair { background: #f3f4f6; }
    .pf-mode__vue--clair i:nth-child(1) { background: var(--tblr-primary); width: 100%; height: 9px; }
    .pf-mode__vue--clair i:nth-child(2) { background: #fff; border: 1px solid #e6e7e9; }
    .pf-mode__vue--clair i:nth-child(3) { background: #fff; border: 1px solid #e6e7e9; width: 70%; }
    .pf-mode__vue--sombre { background: #111827; }
    .pf-mode__vue--sombre i:nth-child(1) { background: var(--tblr-primary); width: 100%; height: 9px; }
    .pf-mode__vue--sombre i:nth-child(2) { background: #1f2937; border: 1px solid #374151; }
    .pf-mode__vue--sombre i:nth-child(3) { background: #1f2937; border: 1px solid #374151; width: 70%; }
    .pf-mode__titre { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700; }
    @media (max-width: 420px) { .pf-modes { grid-template-columns: 1fr; } }
</style>

<div class="pf-pal-titre"><span>Mode d'affichage</span><span id="pfModeEtat" role="status" style="text-transform: none; letter-spacing: 0; font-weight: 500;"></span></div>
<div class="pf-modes" role="group" aria-label="Mode d'affichage">
    <button type="button" class="pf-mode" data-mode="clair" aria-pressed="{{ $user->modeSombre() ? 'false' : 'true' }}">
        <span class="pf-mode__vue pf-mode__vue--clair"><i></i><i></i><i></i></span>
        <span class="pf-mode__titre">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 12a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7"/></svg>
            Clair
        </span>
    </button>
    <button type="button" class="pf-mode" data-mode="sombre" aria-pressed="{{ $user->modeSombre() ? 'true' : 'false' }}">
        <span class="pf-mode__vue pf-mode__vue--sombre"><i></i><i></i><i></i></span>
        <span class="pf-mode__titre">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"/></svg>
            Sombre
        </span>
    </button>
</div>

<div class="pf-pal-titre" style="margin-top: 22px;"><span>Couleur</span></div>
<div class="pf-pal-titre"><span>Classiques</span></div>
<div class="pf-puces" role="group" aria-label="Couleurs classiques">
    @foreach(ThemeColors::CLASSIQUES as $cle => [$nom, $hex])
        <button type="button" class="pf-puce" data-couleur="{{ $cle }}" data-hex="{{ $hex }}" aria-pressed="{{ $actuelle['nom'] === $cle ? 'true' : 'false' }}" aria-label="{{ $nom }}">
            <i style="background: {{ $hex }}; color: {{ ThemeColors::resolve($cle)['fg'] }};"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5l10 -10"/></svg></i>
            <span>{{ $nom }}</span>
        </button>
    @endforeach
</div>

<div class="pf-pal-titre"><span>Pastels</span><span style="text-transform: none; letter-spacing: 0; font-weight: 500;">Plus légers, texte sombre automatique</span></div>
<div class="pf-puces" role="group" aria-label="Couleurs pastels">
    @foreach(ThemeColors::PASTELS as $cle => [$nom, $hex])
        <button type="button" class="pf-puce" data-couleur="{{ $cle }}" data-hex="{{ $hex }}" aria-pressed="{{ $actuelle['nom'] === $cle ? 'true' : 'false' }}" aria-label="{{ $nom }}">
            <i style="background: {{ $hex }}; color: {{ ThemeColors::resolve($cle)['fg'] }};"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5l10 -10"/></svg></i>
            <span>{{ $nom }}</span>
        </button>
    @endforeach
</div>

<div class="pf-libre">
    <label for="pfLibre">Couleur libre</label>
    <input type="color" id="pfLibre" class="pf-rond" value="{{ $actuelle['hex'] }}" aria-label="Choisir une couleur libre">
    <span style="color: var(--c-muted); font-size: 12.5px;">Le texte (blanc ou sombre) s'adapte pour rester lisible.</span>
</div>

<div class="pf-apercu" aria-live="polite">
    <span class="lib">Aperçu</span>
    <button type="button" class="pf-ap-btn">Bouton principal</button>
    <button type="button" class="pf-ap-btn2">Bouton secondaire</button>
    <span class="pf-ap-badge">Pastille</span>
    <span class="pf-ratio">Lisibilité du texte : <b id="pfRatio">—</b></span>
</div>

<div style="margin-top: 14px; display: flex; align-items: center; flex-wrap: wrap;">
    <button type="button" class="pf-btn" id="pfEnregistrerCouleur">Enregistrer la couleur</button>
    <span class="pf-etat" id="pfEtat" role="status"></span>
</div>

<script>
(function () {
    const racine = document.documentElement;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const puces = [...document.querySelectorAll('.pf-puce')];
    const libre = document.getElementById('pfLibre');
    const bouton = document.getElementById('pfEnregistrerCouleur');
    const etat = document.getElementById('pfEtat');
    let choix = @json($actuelle['nom']);        // nom de la palette ou #rrggbb
    let enregistre = choix;

    // Mêmes formules que App\Support\ThemeColors : le texte blanc ou sombre qui contraste le plus.
    const rgb = (h) => [1, 3, 5].map((i) => parseInt(h.substr(i, 2), 16));
    const lum = (h) => { const c = rgb(h).map((v) => { v /= 255; return v <= .03928 ? v / 12.92 : Math.pow((v + .055) / 1.055, 2.4); }); return .2126 * c[0] + .7152 * c[1] + .0722 * c[2]; };
    const contraste = (a, b) => { const l1 = Math.max(lum(a), lum(b)), l2 = Math.min(lum(a), lum(b)); return (l1 + .05) / (l2 + .05); };
    const melanger = (h, avec, part) => '#' + rgb(h).map((v, i) => Math.round(v * (1 - part) + rgb(avec)[i] * part).toString(16).padStart(2, '0')).join('');
    const SOMBRE = '#182433';

    function appliquer(hex) {
        const fg = contraste(hex, '#ffffff') >= contraste(hex, SOMBRE) ? '#ffffff' : SOMBRE;
        const lt = melanger(hex, '#ffffff', .9);
        // Même feuille (#theme-vars) que celle écrite par le serveur : l'aperçu a donc la même priorité.
        document.getElementById('theme-vars').textContent =
            ':root:root:root {'
            + ' --tblr-primary: ' + hex + ' !important;'
            + ' --tblr-primary-rgb: ' + rgb(hex).join(', ') + ' !important;'
            + ' --tblr-primary-fg: ' + fg + ' !important;'
            + ' --tblr-primary-darken: ' + melanger(hex, '#000000', .12) + ' !important;'
            + ' --tblr-primary-lt: ' + lt + ' !important;'
            + ' --tblr-primary-lt-rgb: ' + rgb(lt).join(', ') + ' !important; }';
        racine.setAttribute('data-primary-fg', fg === '#ffffff' ? 'light' : 'dark');

        const r = contraste(hex, fg);
        document.getElementById('pfRatio').textContent = r.toFixed(1) + ' : 1 ' + (r >= 4.5 ? '(bon)' : '(à éviter)');
        libre.value = hex;
    }

    function marquer() {
        puces.forEach((p) => p.setAttribute('aria-pressed', String(p.dataset.couleur === choix)));
        bouton.disabled = choix === enregistre;
        etat.textContent = choix === enregistre ? '' : 'Aperçu : pas encore enregistré.';
    }

    puces.forEach((p) => p.addEventListener('click', () => { choix = p.dataset.couleur; appliquer(p.dataset.hex); marquer(); }));
    libre.addEventListener('input', () => { choix = libre.value.toLowerCase(); appliquer(choix); marquer(); });

    bouton.addEventListener('click', async () => {
        bouton.disabled = true;
        etat.textContent = 'Enregistrement…';
        try {
            const r = await fetch('/user/theme-color', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ color: choix }),
            });
            if (!r.ok) throw new Error(r.status);
            enregistre = choix;
            etat.textContent = 'Couleur enregistrée.';
        } catch (e) {
            etat.textContent = 'Impossible d’enregistrer, réessayez.';
        } finally {
            bouton.disabled = choix === enregistre;
        }
    });

    appliquer(@json($actuelle['hex']));
    marquer();

    // Mode d'affichage : appliqué tout de suite, puis enregistré.
    const modes = [...document.querySelectorAll('.pf-mode')];
    const etatMode = document.getElementById('pfModeEtat');
    let modeEnregistre = @json($user->modeSombre() ? 'sombre' : 'clair');

    modes.forEach((b) => b.addEventListener('click', async () => {
        const mode = b.dataset.mode;
        if (mode === modeEnregistre) return;
        const precedent = modeEnregistre;
        const poser = (m) => {
            if (m === 'sombre') racine.setAttribute('data-bs-theme', 'dark'); else racine.removeAttribute('data-bs-theme');
            modes.forEach((x) => x.setAttribute('aria-pressed', String(x.dataset.mode === m)));
        };

        poser(mode);
        etatMode.textContent = 'Enregistrement…';
        try {
            const r = await fetch('/user/mode', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ mode: mode }),
            });
            if (!r.ok) throw new Error(r.status);
            modeEnregistre = mode;
            etatMode.textContent = 'Mode enregistré.';
        } catch (e) {
            poser(precedent);
            etatMode.textContent = 'Impossible d’enregistrer, réessayez.';
        }
    }));
})();
</script>
