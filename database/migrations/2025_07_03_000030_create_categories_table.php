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
        Schema::create('categories', function (Blueprint $table) {
            $table->id()->comment('카테고리 ID');
            
            // 카테고리명
            $table->string('name', 50)
                ->comment('카테고리명');
            
            // 카테고리 설명
            $table->text('description')
                ->nullable()
                ->comment('카테고리 설명');
            
            // 상위 카테고리 (계층 구조)
            $table->unsignedBigInteger('parent_id')
                ->nullable()
                ->comment('상위 카테고리 ID');
            
            // 정렬 순서
            $table->integer('sort_order')
                ->default(0)
                ->comment('정렬 순서');
            
            // 활성 상태
            $table->boolean('is_active')
                ->default(true)
                ->comment('카테고리 활성 상태');
            
            $table->timestamps();
            
            // 외래키 제약조건
            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->onDelete('cascade');
            
            // 인덱스
            $table->index('parent_id');
            $table->index('sort_order');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
