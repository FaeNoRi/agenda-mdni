<?php

namespace App\Http\Controllers;

use App\Exports\GenericExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ExportController extends Controller
{

    public function export(Request $request)
    {
        $model = $request->input('model');
        $columns = $request->input('columns', []);
        $filters = $request->input('filters', []);

        return Excel::download(new GenericExport($model, $columns, $filters), $model . '_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function getModelColumns(string $model)
    {
        $class = '\\App\\Models\\' . ucfirst($model);

        if (!class_exists($class)) {
            return response()->json(['error' => 'Modèle introuvable'], 404);
        }

        $instance = new $class;
        $table = $instance->getTable();

        if (!Schema::hasTable($table)) {
            return response()->json(['error' => 'Table introuvable'], 404);
        }

        return response()->json(Schema::getColumnListing($table));
    }
}
