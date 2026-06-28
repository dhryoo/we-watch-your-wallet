<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_leads', function (Blueprint $table)
        {
            $table->id();
            $table->string('email', 254);
            $table->string('wallet_address', 42)->nullable();   // wallet the user wants monitored
            $table->unsignedBigInteger('chain_id')->default(1);
            $table->string('source', 16);                        // home | result
            $table->string('scanned_wallet', 42)->nullable();    // point-in-time snapshot context
            $table->string('scan_severity', 8)->nullable();      // URGENT | WARN | WATCH | INFO at signup
            $table->char('ip_hash', 64)->nullable();             // sha256(ip + app.key) — never the raw IP
            $table->boolean('consent')->default(false);
            $table->string('unsubscribe_token', 64)->unique();   // reserved for Phase-4 unsubscribe
            $table->timestamp('confirmed_at')->nullable();       // reserved for Phase-4 double opt-in
            $table->timestamps();

            $table->unique(['email', 'wallet_address', 'chain_id']); // dedup: one row per email+wallet+chain
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_leads');
    }
};
