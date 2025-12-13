<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('search_queries', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20);
            $table->string('query');
            $table->unsignedInteger('duration_ms');
            $table->timestamp('performed_at');
            $table->timestamps();

            $table->index('performed_at');
            $table->index(['query', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
