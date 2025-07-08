@extends('layouts.app')

@section('content')
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

                    <div class="mt-6">
                        <h2 class="text-xl font-semibold mb-2">민원 목록</h2>
                        @if($complaints->count() > 0)
                            <ul class="space-y-2">
                                @foreach($complaints as $complaint)
                                    <li class="border p-2 rounded">
                                        <strong>{{ $complaint->title }}</strong> - {{ $complaint->status }}
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-500">등록된 민원이 없습니다.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
