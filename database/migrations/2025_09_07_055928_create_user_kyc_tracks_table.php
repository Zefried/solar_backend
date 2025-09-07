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
        Schema::create('user_kyc_tracks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->boolean('user_doc_status')->default(false);
            $table->boolean('user_profile_status')->default(false);
            $table->boolean('user_bank_status')->default(false);
            $table->boolean('user_extra_status')->default(false);
            $table->enum('user_kyc_status', ['pending','completed'])->default('pending');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_kyc_tracks');
    }
};
