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
        Schema::create('user_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('id_proof_front')->nullable();
            $table->string('id_proof_back')->nullable();
            $table->string('id_proof_number')->nullable()->index();
            $table->string('pan_card')->nullable();
            $table->string('pan_number')->nullable()->index();
            $table->string('cancelled_cheque')->nullable();
            $table->string('electricity_bill')->nullable();
            $table->string('consumer_number')->nullable()->index();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_documents');
    }
};
