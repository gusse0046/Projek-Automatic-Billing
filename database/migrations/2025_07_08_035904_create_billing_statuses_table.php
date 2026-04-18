<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billing_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('no_billing')->nullable();
            $table->string('status')->nullable();
            $table->integer('progress')->nullable();
            $table->timestamp('sent_at')->nullable(); // Kolom tambahan untuk penanda waktu pengiriman
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('billing_statuses');
    }
}
