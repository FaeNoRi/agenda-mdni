<?php

namespace App\Services;

use App\Mail\ReglementSigne;
use App\Models\Adherents;
use Illuminate\Support\Facades\Mail;
use setasign\Fpdi\Fpdi;

class SignatureService
{

    public function signerEtEnvoyer(string $base64Sig, Adherents $adherent): void
    {
        $pdfContent = $this->genererPdfSigne($base64Sig, $adherent);

        Mail::to(config('coworking.mail_transit'))
            ->send(new ReglementSigne(
                pdfContent:    $pdfContent,
                nomAdherent:   $adherent->nom_adh,
                dateSignature: now()->format('d/m/Y à H:i'),
            ));
    }

    private function genererPdfSigne(string $base64Sig, Adherents $adherent): string
    {
        // 1. Décoder la signature → fichier temporaire système (jamais dans storage/)
        $imageData = base64_decode(
            preg_replace('#^data:image/\w+;base64,#i', '', $base64Sig)
        );
        $tmpImg = tempnam(sys_get_temp_dir(), 'sig_') . '.png';
        file_put_contents($tmpImg, $imageData);

        try {
            // 2. Charger le PDF source (le vierge, en lecture seule)
            $sourcePdf = storage_path('app/reglement_interieur.pdf'); // Hors public/
            $pdf       = new Fpdi();
            $pageCount = $pdf->setSourceFile($sourcePdf);

            for ($i = 1; $i <= $pageCount; $i++) {
                $tpl = $pdf->importPage($i);
                $pdf->AddPage();
                $pdf->useTemplate($tpl, 0, 0, null, null, true);

                if ($i === $pageCount) {
                    $this->ajouterBandeauSignature($pdf, $adherent, $tmpImg);
                }
            }

            // 3. Retourner le binaire en mémoire — 'S' = string, aucun fichier créé
            return $pdf->Output('S');

        } finally {
            // 4. Nettoyage garanti même en cas d'exception
            @unlink($tmpImg);
        }
    }

    private function ajouterBandeauSignature(Fpdi $pdf, Adherents $adherent, string $tmpImg): void
    {
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(80, 80, 80);

        // Ligne séparatrice
        $yBase = $pdf->GetPageHeight() - 50;
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(10, $yBase, 200, $yBase);

        // Métadonnées texte
        $pdf->SetXY(10, $yBase + 3);
        $pdf->Cell(0, 5, 'Signé électroniquement par : ' . $adherent->nom_adh, 0, 1);
        $pdf->Cell(0, 5, 'Date : ' . now()->format('d/m/Y à H:i:s'), 0, 1);
        $pdf->Cell(0, 5, 'Situation : ' . $adherent->situation_adh, 0, 1);

        // Image de la signature
        $pdf->Image($tmpImg, 140, $yBase + 3, 55);
    }
}