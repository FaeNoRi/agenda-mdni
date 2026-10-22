<?php

namespace Tests\Feature;

use App\Models\Adherents;
use App\Models\User;
use App\Services\SignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chemins qui s'appuient sur des bibliothèques tierces (PhpSpreadsheet, FPDF/FPDI) : à rejouer à chaque changement
 * de version de PHP, car ce sont elles qui cassent le plus volontiers lors d'une montée de version.
 */
class ExportsEtPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_l_export_excel_produit_un_vrai_fichier_xlsx(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(3)->create();

        $reponse = $this->actingAs($admin)->post('/export', [
            'model' => 'User',
            'columns' => ['id', 'name', 'email'],
        ]);

        $reponse->assertOk();
        $fichier = $reponse->baseResponse->getFile()->getPathname();
        $this->assertFileExists($fichier);

        // Un .xlsx est une archive zip contenant le classeur.
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($fichier) === true, 'le fichier exporté est une archive xlsx valide');
        $this->assertNotFalse($zip->locateName('xl/workbook.xml'));
        $zip->close();
    }

    public function test_le_pdf_de_reglement_signe_se_genere(): void
    {
        $source = storage_path('app/reglement_interieur.pdf');
        $copie = !file_exists($source);
        if ($copie) {
            copy(public_path('assets/files/reglement_interieur_fpdi.pdf'), $source);
        }

        try {
            $adherent = new Adherents(['nom_adh' => 'Test', 'situation_adh' => 'Adhérent']);

            $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
            $methode = new \ReflectionMethod(SignatureService::class, 'genererPdfSigne');
            $pdf = $methode->invoke(new SignatureService(), 'data:image/png;base64,'.$png, $adherent);

            $this->assertStringStartsWith('%PDF', $pdf);
            $this->assertGreaterThan(1000, strlen($pdf));
        } finally {
            if ($copie) {
                @unlink($source);
            }
        }
    }
}
