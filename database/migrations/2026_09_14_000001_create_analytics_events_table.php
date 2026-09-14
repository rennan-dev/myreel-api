<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 64);
            $table->string('event_type', 32)->default('pageview');
            $table->string('path', 255)->default('/');
            $table->string('referrer', 512)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('country', 64)->nullable();
            $table->string('city', 96)->nullable();
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index('visitor_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};