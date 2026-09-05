<?php

namespace App\Http\Controllers;

use App\Models\Adherents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use setasign\Fpdi\Fpdi;

class ReglementSignatureController extends Controller
{
    public function showPdf()
    {
        $path = public_path('assets/files/reglement_interieur.pdf');

        abort_unless(file_exists($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function adherentsCurrentYear()
    {
        return Adherents::query()
            ->whereYear('date_adh', '>=', now()->year)
            // ->where('isSigned', false)
            ->orderBy('nom_adh')
            ->get(['id', 'nom_adh']);
    }

    public function sign(Request $request)
    {
        $validated = $request->validate([
            'adherent_id'    => ['required', 'exists:adherents,id'],
            'admin_password' => ['required', 'string'],
            'signature'      => ['required', 'string'],
            'refus_photo'    => ['nullable', 'boolean'],
        ]);

        if (!Hash::check($validated['admin_password'], env('ADMIN_PASSWORD_HASH'))) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe administrateur incorrect.',
            ], 403);
        }

        $adherent = Adherents::whereYear('date_adh', '>=', now()->year)
            ->where('id', $validated['adherent_id'])
            ->firstOrFail();

        $templatePath = public_path('assets/files/reglement_interieur_fpdi.pdf');
        abort_unless(file_exists($templatePath), 404, 'PDF vierge introuvable.');

        $refusPhoto = !empty($validated['refus_photo']);
        $signatureTempPath = null;

        try {
            $signatureTempPath = $this->saveSignatureTemporarily($validated['signature']);

            $pdfContent = $this->generateSignedPdf(
                templatePath: $templatePath,
                signaturePath: $signatureTempPath,
                adherentName: $adherent->nom_adh,
                date: now()->format('d/m/Y'),
                refusPhoto: $refusPhoto
            );

            $response = Http::withHeaders([
                'api-key' => env('BREVO_API_KEY'),
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name'  => env('BREVO_SENDER_NAME'),
                    'email' => env('BREVO_SENDER_EMAIL'),
                ],
                'to' => [
                    [
                        'email' => 'contact@mdnicalaisis.com',
                        'name'  => 'MDNI Calaisis',
                    ],
                ],
                'subject' => 'Signature du règlement - ' . $adherent->nom_adh,
                'htmlContent' => view('emails.reglement-signe', [
                    'adherent' => $adherent,
                    'date' => now()->format('d/m/Y'),
                ])->render(),
                'attachment' => [
                    [
                        'name' => 'reglement-signe-' . str($adherent->nom_adh)->slug('-') . '.pdf',
                        'content' => base64_encode($pdfContent),
                    ],
                ],
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur Brevo lors de l’envoi du mail.',
                    'details' => $response->json(),
                ], 500);
            }

            $adherent->update(['isSigned' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Règlement signé et envoyé par mail.',
            ]);

        } finally {
            if ($signatureTempPath && file_exists($signatureTempPath)) {
                @unlink($signatureTempPath);
            }
        }
    }

    private function saveSignatureTemporarily(string $base64): string
    {
        $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        $binary = base64_decode($base64);

        abort_if(!$binary, 422, 'Signature invalide.');

        $path = tempnam(sys_get_temp_dir(), 'signature_') . '.png';

        file_put_contents($path, $binary);

        return $path;
    }

    private function generateSignedPdf(
        string $templatePath,
        string $signaturePath,
        string $adherentName,
        string $date,
        bool $refusPhoto
    ): string {
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($templatePath);

        for ($page = 1; $page <= $pageCount; $page++) {
            $tpl = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($tpl);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);

            if ($page === $pageCount) {
                $pdf->SetFont('Arial', '', 11);
                $pdf->SetTextColor(0, 0, 0);

                // Case à cocher refus photo
                if ($refusPhoto) {
                    $pdf->SetFont('Arial', 'B', 10);
                    $pdf->SetXY(131.75, 152);
                    $pdf->Write(6, 'X');
                }

                // Date sur la ligne pointillée
                $pdf->SetXY(20, 212);
                $pdf->Write(6, $date);

                // Nom sous "Lu et approuvé"
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->SetXY(20, 233.25);
                $pdf->Write(6, mb_convert_encoding($adherentName, 'ISO-8859-1', 'UTF-8'));

                // Signature
                $pdf->Image($signaturePath, 115, 240, 62.5, 32.5);
            }
        }

        return $pdf->Output('S');
    }

}
