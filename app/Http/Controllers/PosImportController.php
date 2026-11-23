<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePosImportRequest;
use App\Models\PosImport;
use App\Services\Imports\PlatePilotCsvImporter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PosImportController extends Controller
{
    //Blade templates are replaced with Inertia responses
    // public function index(): View
    // {
    //     $imports = PosImport::query()
    //         ->with('errors')
    //         ->where('restaurant_id', auth()->user()->restaurant_id)
    //         ->latest()
    //         ->paginate(20);

    //     return view('pos-imports.index', compact('imports'));
    // }

    public function index(): Response
    {
        $imports = PosImport::query()
            ->where('restaurant_id', auth()->user()->restaurant_id)
            ->latest()
            ->paginate(20);

        $imports->through(function (PosImport $import) {
            return [
                'id' => $import->id,
                'original_filename' => $import->original_filename,
                'status' => $import->status,
                'rows_processed' => $import->rows_processed,
                'rows_successful' => $import->rows_successful,
                'rows_failed' => $import->rows_failed,
                'created_at' => $import->created_at->format('d M Y H:i'),
            ];
        });

        return Inertia::render('PosImports/Index', [
            'imports' => $imports,
            'success' => session('success'),
        ]);
    }

    public function errors(PosImport $posImport): Response
    {
        abort_unless(
            $posImport->restaurant_id === auth()->user()->restaurant_id,
            404
        );

        $posImport->load('errors');

        return Inertia::render('PosImports/Errors', [
            'posImport' => [
                'id' => $posImport->id,
                'original_filename' => $posImport->original_filename,
                'status' => $posImport->status,
                'rows_processed' => $posImport->rows_processed,
                'rows_successful' => $posImport->rows_successful,
                'rows_failed' => $posImport->rows_failed,

                'errors' => $posImport->errors->map(function ($error) {
                    return [
                        'id' => $error->id,
                        'row_number' => $error->row_number,
                        'message' => $error->message,
                    ];
                })->values(),
            ],
        ]);
    }

    //Blade templates are replaced with Inertia responses
    // public function create(): View
    // {
    //     return view('pos-imports.create');
    // }

    public function create(): Response
    {
        return Inertia::render('PosImports/Create');
    }

    public function store(
        StorePosImportRequest $request,
        PlatePilotCsvImporter $importer
    ): RedirectResponse {
        $user = $request->user();
        $file = $request->file('csv_file');

        $hash = hash_file('sha256', $file->getRealPath());

        $alreadyImported = PosImport::query()
            ->where('restaurant_id', $user->restaurant_id)
            ->where('file_hash', $hash)
            ->exists();

        if ($alreadyImported) {
            return back()->withErrors([
                'csv_file' => 'This exact CSV file has already been uploaded.',
            ]);
        }

        $storedPath = $file->store(
            "restaurants/{$user->restaurant_id}/pos-imports",
            'local'
        );

        $posImport = PosImport::create([
            'restaurant_id' => $user->restaurant_id,
            'user_id' => $user->id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'file_hash' => $hash,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $importer->import($posImport);

            return redirect()
                ->route('pos-imports.index')
                ->with('success', 'POS CSV imported successfully.');
        } catch (Throwable $exception) {
            report($exception);

            $posImport->update([
                'status' => 'failed',
                'failure_reason' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            return redirect()
                ->route('pos-imports.index')
                ->withErrors([
                    'csv_file' => 'The CSV could not be imported: '
                        .$exception->getMessage(),
                ]);
        }
    }
}
