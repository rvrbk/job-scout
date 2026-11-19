<?php

namespace App\Http\Controllers;

use App\Http\Requests\LedgerFactRequest;
use App\Jobs\RunFirstSearch;
use App\Models\LedgerEntry;
use App\Models\LedgerFact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class LedgerFactController extends Controller
{
    public function store(LedgerFactRequest $request, LedgerEntry $ledgerEntry): RedirectResponse
    {
        Gate::authorize('update', $ledgerEntry);

        $ledgerEntry->facts()->create($request->factAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fact added.')]);

        return to_route('ledger.index');
    }

    public function update(LedgerFactRequest $request, LedgerFact $ledgerFact): RedirectResponse
    {
        Gate::authorize('update', $ledgerFact->entry);

        $ledgerFact->update($request->factAttributes($ledgerFact->confirmed_at));

        $started = RunFirstSearch::startFor($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => $started
            ? __('Fact updated. Your first job search has started; matches appear under Jobs in a few minutes.')
            : __('Fact updated.')]);

        return to_route('ledger.index');
    }

    public function destroy(LedgerFact $ledgerFact): RedirectResponse
    {
        Gate::authorize('update', $ledgerFact->entry);

        $ledgerFact->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fact deleted.')]);

        return to_route('ledger.index');
    }
}
