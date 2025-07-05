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
        Schema::table('complaints', function (Blueprint $table) {
            // 이관 관련 필드 추가
            $table->timestamp('transferred_at')->nullable();
            $table->unsignedBigInteger('transferred_by')->nullable();
            $table->string('transfer_reason', 1000)->nullable();
            $table->boolean('auto_transferred')->default(false);
            
            // 상급 이관 관련 필드 추가
            $table->timestamp('escalated_at')->nullable();
            $table->unsignedBigInteger('escalated_by')->nullable();
            
            // 기타 필드 추가
            $table->string('complaint_number', 50)->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->text('resolution_comment')->nullable();
            $table->json('tags')->nullable();
            $table->integer('views_count')->default(0);
            $table->boolean('is_urgent')->default(false);
            
            // 외래키 제약 조건 추가
            $table->foreign('transferred_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('escalated_by')->references('id')->on('users')->onDelete('set null');
            
            // 인덱스 추가
            $table->index('transferred_at');
            $table->index('escalated_at');
            $table->index('auto_transferred');
            $table->index('is_urgent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // 외래키 제약 조건 제거
            $table->dropForeign(['transferred_by']);
            $table->dropForeign(['escalated_by']);
            
            // 인덱스 제거
            $table->dropIndex(['transferred_at']);
            $table->dropIndex(['escalated_at']);
            $table->dropIndex(['auto_transferred']);
            $table->dropIndex(['is_urgent']);
            
            // 새로 추가된 필드 제거
            $table->dropColumn([
                'transferred_at',
                'transferred_by',
                'transfer_reason',
                'auto_transferred',
                'escalated_at',
                'escalated_by',
                'complaint_number',
                'department_id',
                'resolution_comment',
                'tags',
                'views_count',
                'is_urgent'
            ]);
        });
    }
};
