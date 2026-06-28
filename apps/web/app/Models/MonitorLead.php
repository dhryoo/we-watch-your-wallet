<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A monitoring lead (Pro-conversion funnel). Holds only an email + the wallet the
 * user wants watched and point-in-time context — never keys, seeds, or funds.
 */
#[Fillable(['email', 'wallet_address', 'chain_id', 'source', 'scanned_wallet', 'scan_severity', 'ip_hash', 'consent', 'unsubscribe_token', 'confirmed_at'])]
class MonitorLead extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consent' => 'boolean',
            'chain_id' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }
}
