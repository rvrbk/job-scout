<?php

namespace App\Http\Controllers;

use App\Jobs\ImportLedger;
use App\Services\Cv\CvTextExtractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use RuntimeException;

class LedgerImportController extends Controller
{
    public function store(Request $request, CvTextExtractor $extractor): RedirectResponse
    {
        $request->validate([
            'cv' => ['required', 'file', 'mimes:pdf,docx,txt,md', 'max:5120'],
        ]);

        try {
            $text = $extractor->extract($request->file('cv'));
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['cv' => $exception->getMessage()]);
        }

        if (! Cache::add(ImportLedger::runningKey($request->user()), true, 1800)) {
            throw ValidationException::withMessages(['cv' => __('An import is already running.')]);
        }

        Cache::forget(ImportLedger::resultKey($request->user()));

        ImportLedger::dispatchAfterResponse($request->user(), $text);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Import started. It can take a minute; reload the page to see the entries.')]);

        return to_route('ledger.index');
    }
}
