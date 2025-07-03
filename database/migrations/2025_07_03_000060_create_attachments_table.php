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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id()->comment('첨부파일 ID');
            
            // 민원 ID
            $table->unsignedBigInteger('complaint_id')
                ->comment('민원 ID');
            
            // 업로드한 사용자 ID
            $table->unsignedBigInteger('user_id')
                ->comment('업로드한 사용자 ID');
            
            // 원본 파일명
            $table->string('original_name', 255)
                ->comment('원본 파일명');
            
            // 저장된 파일명
            $table->string('stored_name', 255)
                ->comment('저장된 파일명');
            
            // 파일 경로
            $table->string('file_path', 500)
                ->comment('파일 저장 경로');
            
            // 파일 크기 (bytes)
            $table->unsignedBigInteger('file_size')
                ->comment('파일 크기 (bytes)');
            
            // MIME 타입
            $table->string('mime_type', 100)
                ->comment('MIME 타입');
            
            // 파일 확장자
            $table->string('extension', 10)
                ->comment('파일 확장자');
            
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
            $table->index('mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
