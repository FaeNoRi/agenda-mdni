<div style="font-family: 'AvenirNextCondensed-Regular', 'Avenir Next Condensed', 'Segoe UI', 'Helvetica Neue', 'Arial', sans-serif; max-width:600px; margin:auto; border:1px solid #ddd; padding:20px; border-radius:8px;">

    <h2 style="color:{{ $color ?? '#206bc4' }}; margin-bottom:5px;"> Règlement signé avec succès </h2>

    <p><strong>Signataire :</strong><br>
        {{ $adherent->nom_adh }}
    </p>

    <p><strong>Date de signature :</strong><br>
        {{ \Carbon\Carbon::parse($signedAt ?? now())->translatedFormat('l d F Y') }}<br>
        {{ \Carbon\Carbon::parse($signedAt ?? now())->format('H:i') }}
    </p>

    <hr style="margin-top:30px;">

    <p style="font-size:12px; color:#999;">
        Ce message est une confirmation automatique envoyée à la suite de la signature du règlement dans l’application Floppy là.
    </p>

</div>
