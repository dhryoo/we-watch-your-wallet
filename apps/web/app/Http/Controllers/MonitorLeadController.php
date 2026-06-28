<?php

namespace App\Http\Controllers;

use App\Models\MonitorLead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MonitorLeadController extends Controller
{
    private const CHAIN_ID = 1;
    private const ADDRESS_RE = '/^0x[0-9a-fA-F]{40}$/';

    /**
     * Capture a monitoring lead (Pro funnel). Writes ONLY a lead row — no scan / GoPlus / LLM
     * call (keeps the scan budget and plaintext-only invariant intact). Non-custodial: stores
     * an email + the wallet to watch, never keys or funds.
     */
    public function store(Request $request): RedirectResponse
    {
        // Layer 1 — honeypot: bots fill the hidden 'website' field. Silent success: no row, no signal.
        if ($request->filled('website'))
        {
            return back()->with('monitorStatus', 'ok');
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'max:254', 'email:rfc'],
            'wallet_address' => ['nullable', 'regex:' . self::ADDRESS_RE],
            'source' => ['required', Rule::in(['home', 'result'])],
            'scanned_wallet' => ['nullable', 'regex:' . self::ADDRESS_RE],
            'scan_severity' => ['nullable', Rule::in(['URGENT', 'WARN', 'WATCH', 'INFO'])],
            'consent' => ['accepted'],
        ], [
            'email.required' => 'Enter a valid email address.',
            'email.email' => 'Enter a valid email address.',
            'consent.accepted' => 'Please accept to continue.',
        ]);

        // Normalize the bounded, validated email so dedup is case/whitespace-insensitive.
        $email = Str::lower(trim($validated['email']));
        $wallet = $validated['wallet_address'] ?? null;

        // Layer 4 — dedup: one row per (email, wallet, chain). A repeat is an idempotent refresh.
        $lead = MonitorLead::firstOrNew([
            'email' => $email,
            'wallet_address' => $wallet,
            'chain_id' => self::CHAIN_ID,
        ]);

        // Layer 3 — per-IP daily limit on a bucket separate from the scan budget. Only a genuinely
        // NEW lead spends a slot; refreshing an existing lead (re-scan, typo fix, refresh) is free.
        $ip = $request->ip() ?? 'unknown';
        $ipKey = 'monitor-ip:' . $ip;
        $isNew = !$lead->exists;

        if ($isNew && RateLimiter::tooManyAttempts($ipKey, (int) config('scan.monitor_ip_daily_limit', 10)))
        {
            return back()
                ->withErrors(['monitor' => "You have reached today's submission limit. Please try again tomorrow."])
                ->withInput();
        }

        $lead->source = $validated['source'];
        $lead->scanned_wallet = $validated['scanned_wallet'] ?? null;
        $lead->scan_severity = $validated['scan_severity'] ?? null;
        $lead->consent = true;
        $lead->ip_hash = hash('sha256', $ip . ':' . config('app.key'));
        $lead->unsubscribe_token ??= Str::random(40);
        $lead->save();

        if ($isNew)
        {
            RateLimiter::hit($ipKey, 86400);
        }

        return back()->with('monitorStatus', 'ok');
    }
}
