<?php

namespace App\Http\Controllers;

use App\Enums\LedgerEntryType;
use App\Http\Requests\LedgerEntryRequest;
use App\Jobs\ImportLedger;
use App\Jobs\RunFirstSearch;
use App\Models\LedgerEntry;
use App\Models\LedgerFact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LedgerEntryController extends Controller
{
    /**
     * Show the facts ledger.
     */
    public function index(Request $request): Response
    {
        $entries = $request->user()->ledgerEntries()
            ->with(['facts' => fn ($query) => $query->orderBy('id')])
            ->orderByRaw('started_on is null')
            ->orderByDesc('started_on')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('ledger/Index', [
            'entries' => $entries->map(fn (LedgerEntry $entry) => [
                'id' => $entry->id,
                'type' => $entry->type->value,
                'title' => $entry->title,
                'organization' => $entry->organization,
                'location' => $entry->location,
                'started_on' => $entry->started_on?->format('Y-m'),
                'ended_on' => $entry->ended_on?->format('Y-m'),
                'period' => $entry->period(),
                'skills' => $entry->skills ?? [],
                'evidence_url' => $entry->evidence_url,
                'confirmed' => $entry->confirmed_at !== null,
                'facts' => $entry->facts->map(fn (LedgerFact $fact) => [
                    'id' => $fact->id,
                    'statement' => $fact->statement,
                    'evidence_url' => $fact->evidence_url,
                    'confirmed' => $fact->confirmed_at !== null,
                ]),
            ]),
            'import' => [
                'running' => Cache::has(ImportLedger::runningKey($request->user())),
                'source' => Cache::get(ImportLedger::runningKey($request->user())),
                'result' => Cache::get(ImportLedger::resultKey($request->user())),
            ],
            'types' => collect(LedgerEntryType::cases())->map(fn (LedgerEntryType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
        ]);
    }

    public function store(LedgerEntryRequest $request): RedirectResponse
    {
        $request->user()->ledgerEntries()->create($request->entryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Entry added.')]);

        return to_route('ledger.index');
    }

    public function update(LedgerEntryRequest $request, LedgerEntry $ledgerEntry): RedirectResponse
    {
        Gate::authorize('update', $ledgerEntry);

        $ledgerEntry->update($request->entryAttributes($ledgerEntry->confirmed_at));

        $started = RunFirstSearch::startFor($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => $started
            ? __('Entry updated. Your first job search has started; matches appear under Jobs in a few minutes.')
            : __('Entry updated.')]);

        return to_route('ledger.index');
    }

    public function confirm(Request $request, LedgerEntry $ledgerEntry): RedirectResponse
    {
        Gate::authorize('update', $ledgerEntry);

        $ledgerEntry->update(['confirmed_at' => $ledgerEntry->confirmed_at ?? now()]);

        $started = RunFirstSearch::startFor($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => $started
            ? __('Entry confirmed. Your first job search has started; matches appear under Jobs in a few minutes.')
            : __('Entry confirmed.')]);

        return to_route('ledger.index');
    }

    public function destroy(LedgerEntry $ledgerEntry): RedirectResponse
    {
        Gate::authorize('delete', $ledgerEntry);

        $ledgerEntry->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Entry deleted.')]);

        return to_route('ledger.index');
    }
}
