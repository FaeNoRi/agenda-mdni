<?php

namespace App\Http\Controllers;

use App\Models\Adherents;

use Carbon\Carbon;
use App\Models\PresenceDay;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

class PresenceController extends Controller
{

    public function index()
    {
        $equipe = Adherents::query()->where('situation_adh', 'MDNI')->orderByDesc('isPresent')->orderBy('nom_adh', 'asc')->get();
        $presents = Adherents::where('isPresent', true)->where('situation_adh', '!=', "MDNI")->get();
        $absents = Adherents::where('isPresent', false)->where('situation_adh', '!=', "MDNI")->get();

        $extractNomFamille = function ($fullName) {
            if (preg_match('/[A-Z]{2,}.*$/u', $fullName, $matches)) {
                return $matches[0];
            }
            return $fullName;
        };

        $equipe = $equipe->sortBy(fn($adh) => $extractNomFamille($adh->nom_adh));
        $presents = $presents->sortBy(fn($adh) => $extractNomFamille($adh->nom_adh));
        $absents = $absents->sortBy(fn($adh) => $extractNomFamille($adh->nom_adh));

        return view('adherents.presence', compact('equipe', 'presents', 'absents'));
    }

    public function changerPresence(Request $request, $id)
    {
        $validated = $request->validate([
            'isPresent' => 'required|boolean',
            // optionnel : permet d’enregistrer une "source"
            'source'    => 'nullable|string|max:32',
        ]);

        $adh = Adherents::findOrFail($id);

        $wasPresent = (bool) $adh->isPresent;
        $adh->isPresent = $validated['isPresent'];
        $adh->save();

        // ---- Journalisation côté serveur : 0 -> 1 uniquement
        if (!$wasPresent && $validated['isPresent']) {
            $this->logPresenceDay($adh->id, $request->input('source', 'presence_ui'));
        }
        // -----------------------------------

        return response()->json([
            'success' => true,
            'message' => $validated['isPresent']
                ? "{$adh->nom_adh}, bienvenue à la MDNI."
                : "{$adh->nom_adh}, à bientôt à la MDNI.",
        ]);
    }

    private function checkAdminPassword($password): bool
    {
        return Hash::check($password, config('app.admin_password_hash'));
    }

    public function store(Request $request)
    {
        if ($request->has('admin_password')) {
            $request->validate([
                'admin_password' => ['required', function($attr, $value, $fail) {
                    if (!Hash::check($value, config('app.admin_password_hash'))) {
                        $fail('Mot de passe administrateur incorrect.');
                    }
                }],
            ]);
        }

        $validated = $request->validate([
            'nom_adh'        => ['required','string','max:255'],
            'situation_adh'  => ['required','string','max:255'],
            'dom_adh'        => ['required','string','max:255'],
            'date_adh'       => ['required','date'],
            'photo_adh'      => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'isCGU'          => ['boolean'],
            'isPresent'      => ['boolean'],
        ]);

        // on crée l'adhérent sans la photo d'abord (adapte si besoin)
        $adherent = Adherents::create([
            'nom_adh'       => $validated['nom_adh'],
            'situation_adh' => $validated['situation_adh'],
            'dom_adh'       => $validated['dom_adh'],
            'type_adh'      => '',
            'date_adh'      => $validated['date_adh'],
            'isCGU'         => (bool)($validated['isCGU'] ?? false),
            'isPresent'     => (bool)($validated['isPresent'] ?? false),
        ]);

        // upload direct dans public/assets/adherents
        if ($request->hasFile('photo_adh')) {
            $file   = $request->file('photo_adh');
            $ext    = strtolower($file->getClientOriginalExtension());
            $name   = Str::uuid()->toString().'.'.$ext;  // nom anonymisé
            $dest   = public_path('assets/adherents');

            if (!is_dir($dest)) { @mkdir($dest, 0755, true); }

            // déplace le fichier physique
            $file->move($dest, $name);

            // chemin relatif stocké en BDD, utilisable par asset()
            $adherent->update([
                'photo_adh' => 'assets/adherents/'.$name
            ]);
        }

        return back()->with('success', 'Adhérent ajouté avec succès.');
    }

    public function update(Request $request, Adherents $adherent)
    {
        if ($request->has('admin_password')) {
            $request->validate([
                'admin_password' => ['required', function($attr, $value, $fail) {
                    if (!Hash::check($value, config('app.admin_password_hash'))) {
                        $fail('Mot de passe administrateur incorrect.');
                    }
                }],
            ]);
        }

        $validated = $request->validate([
            'nom_adh'        => ['required','string','max:255'],
            'situation_adh'  => ['required','string','max:255'],
            'dom_adh'        => ['required','string','max:255'],
            'date_adh'       => ['required','date'],
            'photo_adh'      => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'isCGU'          => ['boolean'],
            'isPresent'      => ['boolean'],
        ]);

        $adherent->fill([
            'nom_adh'       => $validated['nom_adh'],
            'situation_adh' => $validated['situation_adh'],
            'dom_adh'       => $validated['dom_adh'],
            'date_adh'      => $validated['date_adh'],
            'isCGU'         => (bool)($validated['isCGU'] ?? $adherent->isCGU),
            'isPresent'     => (bool)($validated['isPresent'] ?? $adherent->isPresent),
        ]);

        // nouvelle photo ?
        if ($request->hasFile('photo_adh')) {
            // supprime l'ancienne si elle existe
            if (!empty($adherent->photo_adh)) {
                $oldPath = public_path($adherent->photo_adh); // ex: public/assets/adherents/...
                if (is_file($oldPath)) { @unlink($oldPath); }
            }

            $file   = $request->file('photo_adh');
            $ext    = strtolower($file->getClientOriginalExtension());
            $name   = Str::uuid()->toString().'.'.$ext;
            $dest   = public_path('assets/adherents');

            if (!is_dir($dest)) { @mkdir($dest, 0755, true); }

            $file->move($dest, $name);

            $adherent->photo_adh = 'assets/adherents/'.$name;
        }

        $adherent->save();

        return back()->with('success', 'Profil mis à jour.');

    }

    public function destroy(Request $request, Adherents $adherent)
    {
        if ($request->has('admin_password')) {
            $request->validate([
                'admin_password' => ['required', function($attr, $value, $fail) {
                    if (!Hash::check($value, config('app.admin_password_hash'))) {
                        $fail('Mot de passe administrateur incorrect.');
                    }
                }],
            ]);
        }

        if (!empty($adherent->photo_adh)) {
            $p = public_path($adherent->photo_adh);
            if (is_file($p)) { @unlink($p); }
        }

        $adherent->delete();

        return back()->with('success', 'Profil supprimé avec succès.');
    }

    public function resetAll(Request $request)
    {
        $request->validate([
            'admin_password' => ['required'],
        ]);

        if (!Hash::check($request->input('admin_password'), config('app.admin_password_hash'))) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe administrateur incorrect.',
            ], 422);
        }

        \App\Models\Adherents::where('isPresent', true)->update(['isPresent' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Tous les adhérents ont été déconnectés.',
        ]);
    }

    private function logPresenceDay(int $adherentId, ?string $source = null): void
    {
        $nowParis = Carbon::now('Europe/Paris');
        $jour = $nowParis->toDateString();

        // Ne crée l’entrée que si elle n’existe pas déjà
        PresenceDay::firstOrCreate(
            ['adherent_id' => $adherentId, 'date' => $jour],
            ['first_checkin_at' => $nowParis, 'source' => $source]
        );
    }

    public function listStatsPeriods(): \Illuminate\Http\JsonResponse
    {
        $rowsMonths = DB::table('presence_days')
            ->selectRaw('YEAR(`date`) AS y, MONTH(`date`) AS m')
            ->groupByRaw('y, m')
            ->orderByDesc('y')->orderByDesc('m')
            ->get();

        $months = $rowsMonths->map(function($r){
            $label = \Carbon\Carbon::createFromDate($r->y, $r->m, 1, 'Europe/Paris')
                ->locale('fr')->translatedFormat('F Y');
            $label = mb_convert_case($label, MB_CASE_TITLE, 'UTF-8'); // "Octobre 2025"

            return [
                'key'   => sprintf('%04d-%02d', $r->y, $r->m), // "YYYY-MM"
                'label' => $label,
            ];
        });

        $rowsYears = DB::table('presence_days')
            ->selectRaw('YEAR(`date`) AS y')
            ->groupByRaw('y')
            ->orderByDesc('y')
            ->get();

        $years = $rowsYears->map(fn($r) => [
            'key'   => (string) $r->y,
            'label' => 'Année ' . $r->y,
        ]);

        return response()->json([
            'success' => true,
            'months'  => $months,
            'years'   => $years,
        ]);
    }

    private function frMonthLabel(int $y, int $m): string
    {
        $label = Carbon::createFromDate($y, $m, 1, 'Europe/Paris')
            ->locale('fr')
            ->translatedFormat('F Y'); // "octobre 2025"
        return mb_convert_case($label, MB_CASE_TITLE, 'UTF-8'); // "Octobre 2025"
    }

    private function buildStatsData(string $period): array
    {
        $q = DB::table('presence_days as pd')
            ->join('adherents as a', 'a.id', '=', 'pd.adherent_id')
            ->selectRaw('a.id as adherent_id, a.nom_adh as nom, YEAR(pd.date) as y, MONTH(pd.date) as m, COUNT(*) as nb')
            ->groupBy('a.id','a.nom_adh','y','m');

        $periodDesc = 'Toutes les stats';
        $fileSuffix = 'all';

        if ($period === 'last_30d') {
            $from = Carbon::now('Europe/Paris')->subDays(30)->toDateString();
            $q->where('pd.date', '>=', $from);
            $periodDesc = '30 derniers jours';
            $fileSuffix = 'last30d';
        } elseif (preg_match('/^\d{4}-\d{2}$/', $period)) {
            [$y,$m] = array_map('intval', explode('-', $period));
            $q->whereYear('pd.date', $y)->whereMonth('pd.date', $m);
            $periodDesc = $this->frMonthLabel($y, $m);
            $fileSuffix = sprintf('%04d-%02d', $y, $m);
        }

        $rows = $q->orderByDesc('y')->orderByDesc('m')->orderBy('nom')->get()->map(function($r){
            return [
                'adherent_id' => (int)$r->adherent_id,
                'nom'         => (string)$r->nom,
                'y'           => (int)$r->y,
                'm'           => (int)$r->m,
                'nb'          => (int)$r->nb,
            ];
        })->all();

        return ['rows' => $rows, 'period_desc' => $periodDesc, 'file_suffix' => $fileSuffix];
    }

    private function makeCsv(array $rows, string $fileSuffix): array
    {
        $filename = 'stats_presence_'.$fileSuffix.'.csv';
        $fh = fopen('php://temp', 'r+');

        // BOM UTF-8 pour Excel
        fwrite($fh, "\xEF\xBB\xBF");

        // En-têtes
        fputcsv($fh, ['Prénom NOM', 'Nombre de présences', 'Période'], ';');

        foreach ($rows as $r) {
            $label = $r['label'] ?? ((isset($r['m']) && $r['m'])
                ? $this->frMonthLabel($r['y'], $r['m'])
                : (string)$r['y']);
            fputcsv($fh, [$r['nom'], $r['nb'], $label], ';');
        }

        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return ['filename' => $filename, 'content' => $csv ?: ''];
    }

    private function sendViaBrevo(string $toEmail, string $subject, string $html, string $filename, string $rawContent): void
    {
        $apiKey = config('services.brevo.api_key', env('BREVO_API_KEY'));
        $senderEmail = config('services.brevo.sender_email', env('BREVO_SENDER_EMAIL'));
        $senderName  = config('services.brevo.sender_name',  env('BREVO_SENDER_NAME'));

        if (!$apiKey || !$senderEmail) {
            throw new \RuntimeException("Clés Brevo manquantes (BREVO_API_KEY/BREVO_SENDER_EMAIL).");
        }

        $payload = [
            'sender' => [
                'email' => $senderEmail,
                'name'  => $senderName ?: 'MDNI',
            ],
            'to' => [
                ['email' => $toEmail],
            ],
            'subject' => $subject,
            'htmlContent' => $html,
            'attachment' => [[
                'name'    => $filename,
                'content' => base64_encode($rawContent),
                // 'contentType' => 'text/csv', // optionnel côté Brevo
            ]],
        ];

        $resp = Http::withHeaders([
            'api-key'      => $apiKey,
            'accept'       => 'application/json',
            'content-type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', $payload);

        if (!$resp->successful()) {
            $msg = 'Envoi Brevo échoué';
            try {
                $body = $resp->json();
                if (isset($body['message'])) { $msg .= ' : '.$body['message']; }
            } catch (\Throwable $e) { /* ignore */ }
            throw new \RuntimeException($msg);
        }
    }

    public function exportStats(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'period'         => 'required|string',           // "all" | "last_30d" | "YYYY-MM"
            'recipient'      => 'nullable|email',
            'admin_password' => 'required|string',
        ]);

        if (!Hash::check($validated['admin_password'], config('app.admin_password_hash'))) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe administrateur incorrect.',
            ], 422);
        }

        // Récup agrégée
        $stats = $this->buildStatsData($validated['period']);
        $rows  = $stats['rows'];
        $periodDesc = $stats['period_desc'];
        $fileSuffix = $stats['file_suffix'];

        // CSV
        $csv = $this->makeCsv($rows, $fileSuffix);

        // Destinataire (par défaut si vide)
        $to = trim($validated['recipient'] ?? '');
        if ($to === '') {
            $to = 'contact@mdnicalaisis.com';
        }

        // Sujet + corps
        $subject = "Statistiques de fréquentation — {$periodDesc}";
        $html = '<p>Bonjour,</p>'
            . '<p>Veuillez trouver en pièce jointe les statistiques de fréquentation (jours présents distincts) par adhérent et par mois — période : <strong>'
            . e($periodDesc)
            . '</strong>.</p>'
            . '<p>— Floppy Là</p>';

        // Envoi Brevo
        try {
            $this->sendViaBrevo($to, $subject, $html, $csv['filename'], $csv['content']);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Échec de l’envoi de l’email.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "Export envoyé à {$to} ({$periodDesc}).",
        ]);
    }
}
