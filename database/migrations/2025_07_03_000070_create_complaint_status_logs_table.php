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
        Schema::create('complaint_status_logs', function (Blueprint $table) {
            $table->id()->comment('상태 변경 로그 ID');
            
            // 민원 ID
            $table->unsignedBigInteger('complaint_id')
                ->comment('민원 ID');
            
            // 상태 변경한 사용자 ID
            $table->unsignedBigInteger('user_id')
                ->comment('상태 변경한 사용자 ID');
            
            // 이전 상태
            $table->enum('from_status', [
                'submitted', 'in_review', 'in_progress', 
                'resolved', 'closed', 'rejected'
            ])->nullable()
                ->comment('이전 상태');
            
            // 변경된 상태
            $table->enum('to_status', [
                'submitted', 'in_review', 'in_progress', 
                'resolved', 'closed', 'rejected'
            ])->comment('변경된 상태');
            
            // 변경 사유/메모
            $table->text('reason')
                ->nullable()
                ->comment('변경 사유/메모');
            
            $table->timestamps();
            
            // 외래키 제약조건
            $table->foreign('complaint_id')
                ->references('id')
                ->on('complaints')
                ->onDelete('cascade');
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            
            // 인덱스
            $table->index('complaint_id');
            $table->index('user_id');
            $table->index('to_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaint_status_logs');
    }
};
