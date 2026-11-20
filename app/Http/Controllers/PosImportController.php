<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePosImportRequest;
use App\Models\PosImport;
use App\Services\Imports\PlatePilotCsvImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class PosImportController extends Controller
{
    public function index(): View
    {
        $imports = PosImport::query()
            ->with('errors')
            ->where('restaurant_id', auth()->user()->restaurant_id)
            ->latest()
            ->paginate(20);

        return view('pos-imports.index', compact('imports'));
    }

    public function create(): View
    {
        return view('pos-imports.create');
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
