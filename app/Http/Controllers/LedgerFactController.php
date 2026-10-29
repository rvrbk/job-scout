<?php

namespace App\Http\Controllers;

use App\Http\Requests\LedgerFactRequest;
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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Fact updated.')]);

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
