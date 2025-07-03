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
        Schema::create('comments', function (Blueprint $table) {
            $table->id()->comment('댓글 ID');
            
            // 민원 ID
            $table->unsignedBigInteger('complaint_id')
                ->comment('민원 ID');
            
            // 작성자 ID
            $table->unsignedBigInteger('user_id')
                ->comment('작성자 ID');
            
            // 댓글 내용
            $table->text('content')
                ->comment('댓글 내용');
            
            // 댓글 타입 (일반 댓글, 내부 메모, 상태 변경 알림 등)
            $table->enum('type', ['comment', 'internal_memo', 'status_update', 'system_message'])
                ->default('comment')
                ->comment('댓글 타입: comment(일반), internal_memo(내부메모), status_update(상태변경), system_message(시스템메시지)');
            
            // 공개 여부 (민원 제기자가 볼 수 있는지)
            $table->boolean('is_public')
                ->default(true)
                ->comment('공개 여부 (민원 제기자 가시성)');
            
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
            $table->index('type');
            $table->index('is_public');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
