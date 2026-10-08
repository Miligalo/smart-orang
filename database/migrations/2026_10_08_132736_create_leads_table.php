<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 50)->unique();
            $table->dateTime('created_at_source')->nullable();
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('source', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('product', 150)->nullable();
            $table->decimal('budget_uah', 12, 2)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('manager', 100)->nullable();
            $table->text('comment')->nullable();
            $table->dateTime('next_contact_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('manager');
            $table->index('created_at_source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
