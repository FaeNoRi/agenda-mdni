<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Evenements;
use App\Models\Conge;
use App\Models\ChangementHoraire;
use App\Models\Adherents;

class SyncController extends Controller
{
    public function version(Request $request)
    {
        $scopes = collect(explode(',', $request->query('scope', 'dashboard')))
            ->map(fn($s) => trim($s))->filter()->values();

        $out = [];

        if ($scopes->contains('dashboard')) {
            $out['dashboard'] = $this->dashboardSignature();
        }

        if ($scopes->contains('presence')) {
            $out['presence'] = $this->presenceSignature();
        }

        return response()->json($out)->header('Cache-Control', 'no-store');
    }

    protected function dashboardSignature(): array
    {
        $buckets = [
            'events'  => $this->maxAndCount(Evenements::query()),
            'conges'  => $this->maxAndCount(Conge::query()),
            'changes' => $this->maxAndCount(ChangementHoraire::query()),
        ];

        return ['buckets' => $buckets, 'sig' => md5(json_encode($buckets, JSON_THROW_ON_ERROR))];
    }

    protected function presenceSignature(): array
    {
        // un seul bucket suffit si `updated_at` bouge quand on toggle la présence
        $adherents = $this->maxAndCount(Adherents::query());

        // un peu plus de granularité (optionnel)
        $present   = $this->maxAndCount(Adherents::where('isPresent', true));
        $absent    = $this->maxAndCount(Adherents::where('isPresent', false));

        $buckets = compact('adherents', 'present', 'absent');

        return ['buckets' => $buckets, 'sig' => md5(json_encode($buckets, JSON_THROW_ON_ERROR))];
    }

    protected function maxAndCount($query): array
    {
        return [
            'max'   => optional($query->max('updated_at'))->timestamp ?? 0,
            'count' => (int) $query->count(),
        ];
    }
}
