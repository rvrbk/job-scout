<?php

use App\Mcp\Servers\LedgerServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/ledger', LedgerServer::class)
    ->middleware(['auth:sanctum', 'throttle:60,1'])
    ->name('mcp.ledger');
