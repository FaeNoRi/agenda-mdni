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
    <p class="pf-sous" style="margin-top: 4px;">La couleur de votre interface : barre de navigation, boutons, pastilles. Elle ne concerne que votre compte.</p>
</header>

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
    <span style="color: #667382; font-size: 12.5px;">Le texte (blanc ou sombre) s'adapte pour rester lisible.</span>
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
        const s = racine.style;
        s.setProperty('--tblr-primary', hex);
        s.setProperty('--tblr-primary-rgb', rgb(hex).join(', '));
        s.setProperty('--tblr-primary-fg', fg);
        s.setProperty('--tblr-primary-darken', melanger(hex, '#000000', .12));
        s.setProperty('--tblr-primary-lt', lt);
        s.setProperty('--tblr-primary-lt-rgb', rgb(lt).join(', '));
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
})();
</script>
