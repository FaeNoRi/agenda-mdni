import './bootstrap';
import * as bootstrap from 'bootstrap';

import Alpine from 'alpinejs';
import SignaturePad from 'signature_pad';

window.SignaturePad = SignaturePad;
window.Alpine = Alpine;

Alpine.start();

window.openUserForm = function (userId = null) {
    const url = userId
        ? `/users/${userId}/edit`
        : `/users/create`;

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        document.getElementById('userFormContainer').innerHTML = html;
    })
    .catch(() => {
        document.getElementById('userFormContainer').innerHTML = '<div class="alert alert-danger">Erreur de chargement du formulaire.</div>';
    });
}

document.querySelectorAll(".form-colorinput-input").forEach((input) => {
    input.addEventListener("change", (event) => {
        const color = event.target.value;

        // Met à jour visuellement le thème actif
        document.documentElement.setAttribute("data-bs-theme-primary", color);
        document.documentElement.style.setProperty(
            "--tblr-primary",
            `var(--tblr-${color})`
        );

        // Enregistre en base
        fetch('/user/theme-color', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ color: color })
        }).then(() => location.reload()); // 🔁 recharge la page pour refléter le thème
    });
});


