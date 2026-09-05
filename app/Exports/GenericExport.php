<?php

namespace App\Exports;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GenericExport implements FromCollection, WithHeadings
{
    protected string $modelName;
    protected array $columns;
    protected array $filters;

    public function __construct(string $modelName, array $columns = [], array $filters = [])
    {
        $this->modelName = $modelName;
        $this->columns = $columns;
        $this->filters = $filters;
    }

    public function collection()
    {
        $dateFieldMap = [
            'Evenements' => 'date_heure_debut',
            'Conge' => 'date_debut',
            'ChangementHoraire' => 'jour',
        ];

        $start = $this->filters['start_date'] ?? null;
        $end   = $this->filters['end_date'] ?? null;

        if ($this->modelName === 'Evenements') {
            $columns = Schema::getColumnListing('evenements');
            $createdAtIndex = array_search('created_at', $columns);
            $enrichies = ['Animateurs', 'Salles', 'Matériels', 'Objets'];

            if ($createdAtIndex === false) {
                $finalColumns = array_merge($columns, $enrichies);
            } else {
                $finalColumns = $columns;
                array_splice($finalColumns, $createdAtIndex, 0, $enrichies);
            }

            $query = \App\Models\Evenements::with(['users', 'salles', 'materiels', 'objets']);

            // Appliquer les filtres génériques
            foreach ($this->filters as $field => $value) {
                if (!in_array($field, ['start_date', 'end_date'])) {
                    $query->where($field, $value);
                }
            }

            // Appliquer la période si demandée
            if ($start || $end) {
                $field = $dateFieldMap['Evenements'];
                if ($start) $query->whereDate($field, '>=', $start);
                if ($end) $query->whereDate($field, '<=', $end);
            }

            $evenements = $query->get();

            return $evenements->map(function ($event) use ($finalColumns) {
                $base = $event->getAttributes();

                $base['Animateurs'] = $event->users->pluck('name')->join(', ');
                $base['Salles']     = $event->salles->pluck('nom_salle')->join(', ');
                $base['Matériels']  = $event->materiels->pluck('nom_mat')->join(', ');
                $base['Objets']     = $event->objets->pluck('nom_obj')->join(', ');

                return collect($finalColumns)->mapWithKeys(fn($key) => [$key => $base[$key] ?? '']);
            });
        }

        // Export générique
        $modelClass = '\\App\\Models\\' . Str::studly($this->modelName);
        $query = $modelClass::query();

        foreach ($this->filters as $field => $value) {
            if (!in_array($field, ['start_date', 'end_date'])) {
                $query->where($field, $value);
            }
        }

        if (isset($dateFieldMap[$this->modelName])) {
            $field = $dateFieldMap[$this->modelName];
            if ($start) $query->whereDate($field, '>=', $start);
            if ($end) $query->whereDate($field, '<=', $end);
        }

        return $query->get($this->columns ?: ['*']);
    }

    public function headings(): array
    {
        if ($this->modelName === 'Evenements') {
            $columns = Schema::getColumnListing('evenements');

            // Place les colonnes enrichies avant created_at
            $createdAtIndex = array_search('created_at', $columns);

            $enrichies = ['Animateurs', 'Salles', 'Matériels', 'Objets'];

            if ($createdAtIndex !== false) {
                array_splice($columns, $createdAtIndex, 0, $enrichies);
            } else {
                $columns = array_merge($columns, $enrichies);
            }

            return $columns;
        }

        return $this->columns ?: ['*'];
    }


}