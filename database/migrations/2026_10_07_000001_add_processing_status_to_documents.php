<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('status')->default('queued')->index();
            $table->text('processing_error')->nullable();
        });
        DB::table('documents')->whereNotNull('summary')->update(['status' => 'completed']);
        DB::table('documents')->where('summary', 'like', 'This is a sample % summary.%')
            ->update(['summary' => null, 'key_points' => null, 'status' => 'failed', 'processing_error' => 'Previous AI processing failed. Please retry.']);
    }

    public function down(): void
    {
        Schema::table('documents', fn (Blueprint $table) => $table->dropColumn(['status', 'processing_error']));
    }
};
