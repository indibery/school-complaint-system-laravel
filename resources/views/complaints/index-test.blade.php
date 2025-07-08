@extends('layouts.app')

@section('content')
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            민원 관리 테스트
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h1 class="text-2xl font-bold mb-4">민원 목록 페이지입니다</h1>
                    <p>페이지가 정상적으로 로드되었습니다.</p>
                    
                    <div class="mt-4">
                        <p>총 민원 수: {{ $complaints->count() }}</p>
                        <p>통계 - 전체: {{ $stats['total'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
