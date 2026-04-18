<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('buyer_emails', function (Blueprint $table) {
            $table->id(); // kolom id auto increment
            $table->string('buyer_code', 50)->index(); // kode buyer
            $table->string('buyer_name', 150)->nullable(); // nama buyer
            $table->string('email', 150)->index(); // alamat email
            $table->string('contact_name', 150)->nullable(); // nama kontak
            $table->string('email_type', 50)->nullable(); // jenis email (mis. To, CC, BCC)
            $table->boolean('is_primary')->default(false); // apakah email utama
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyer_emails');
    }
};
