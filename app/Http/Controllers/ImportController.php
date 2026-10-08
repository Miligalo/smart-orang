<?php

namespace App\Http\Controllers;

use App\Jobs\ImportLeadsJob;
use App\Models\Import;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function index()
    {
        $imports = Import::latest()->paginate(10);

        return view('import.index', compact('imports'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:102400'],
        ]);

        $file = $request->file('file');
        $storedName = 'imports/' . uniqid('import_', true) . '.' . $file->getClientOriginalExtension();

        $file->storeAs('', $storedName, 'local');

        $import = Import::create([
            'filename'      => $storedName,
            'original_name' => $file->getClientOriginalName(),
            'status'        => 'pending',
        ]);

        ImportLeadsJob::dispatch($import->id);

        return redirect()->route('import.status', $import->id)
            ->with('success', 'Файл прийнятий. Імпорт розпочато.');
    }

    public function status(Import $import)
    {
        return view('import.status', compact('import'));
    }

    public function statusJson(Import $import)
    {
        return response()->json([
            'status'          => $import->status,
            'total_rows'      => $import->total_rows,
            'processed_rows'  => $import->processed_rows,
            'failed_rows'     => $import->failed_rows,
            'progress'        => $import->getProgressPercent(),
            'error_message'   => $import->error_message,
        ]);
    }
}
