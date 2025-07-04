<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '학교 민원 관리 시스템')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2c5aa0;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #0dcaf0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .navbar-brand {
            font-weight: bold;
            color: var(--primary-color) !important;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color), #1e3d72);
            min-height: calc(100vh - 56px);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 4px 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .main-content {
            padding: 25px;
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-radius: 12px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-bottom: 1px solid #dee2e6;
            border-radius: 12px 12px 0 0 !important;
            padding: 16px 20px;
        }
        
        .btn {
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-assigned { background-color: #cce5ff; color: #004085; }
        .status-in-progress { background-color: #d1ecf1; color: #0c5460; }
        .status-resolved { background-color: #d4edda; color: #155724; }
        .status-closed { background-color: #e2e3e5; color: #383d41; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        
        .priority-urgent { color: var(--danger-color); font-weight: bold; }
        .priority-high { color: #fd7e14; font-weight: 500; }
        .priority-normal { color: var(--secondary-color); }
        .priority-low { color: #6c757d; }
        
        .alert {
            border: none;
            border-radius: 10px;
            padding: 16px 20px;
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            border-radius: 10px;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            border-bottom: 1px solid #dee2e6;
            border-radius: 15px 15px 0 0;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 10px 15px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(44, 90, 160, 0.25);
        }
        
        .spinner-wrapper {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 56px;
                left: -250px;
                width: 250px;
                transition: left 0.3s ease;
                z-index: 1000;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- 로딩 스피너 -->
    <div class="spinner-wrapper" id="loadingSpinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">로딩 중...</span>
        </div>
    </div>
    
    <!-- 네비게이션 바 -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container-fluid">
            <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="bi bi-shield-check me-2"></i>
                학교 민원 관리
            </a>
            
            <div class="navbar-nav ms-auto">
                @auth
                    <!-- 알림 -->
                    <div class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-bell fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationCount" style="display: none;">
                                0
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" style="width: 320px;">
                            <li><h6 class="dropdown-header">알림</h6></li>
                            <div id="notificationList">
                                <li class="dropdown-item text-muted text-center py-3">
                                    새로운 알림이 없습니다.
                                </li>
                            </div>
                        </ul>
                    </div>
                    
                    <!-- 사용자 메뉴 -->
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-2 fs-5"></i>
                            {{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('profile.show') }}">
                                <i class="bi bi-person me-2"></i>프로필
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi bi-box-arrow-right me-2"></i>로그아웃
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endauth
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            @auth
            <!-- 사이드바 -->
            <div class="col-lg-2 p-0">
                <nav class="sidebar" id="sidebar">
                    <div class="py-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                    <i class="bi bi-speedometer2"></i>
                                    대시보드
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('complaints.*') ? 'active' : '' }}" href="{{ route('complaints.index') }}">
                                    <i class="bi bi-file-earmark-text"></i>
                                    민원 관리
                                </a>
                            </li>
                            
                            @if(auth()->user()->hasRole(['admin', 'department_head']))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                                    <i class="bi bi-people"></i>
                                    사용자 관리
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                                    <i class="bi bi-bar-chart"></i>
                                    통계 및 보고서
                                </a>
                            </li>
                            @endif
                            
                            @if(auth()->user()->hasRole('admin'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                                    <i class="bi bi-gear"></i>
                                    시스템 설정
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </nav>
            </div>
            
            <!-- 메인 컨텐츠 -->
            <div class="col-lg-10">
                <main class="main-content">
                    @endif
                    
                    <!-- 알림 메시지 -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="bi bi-info-circle me-2"></i>
                            {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    @yield('content')
                    
                    @auth
                </main>
            </div>
            @endauth
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- 공통 JavaScript -->
    <script>
        // CSRF 토큰 설정
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        // 로딩 스피너 함수
        function showLoading() {
            $('#loadingSpinner').css('display', 'flex');
        }
        
        function hideLoading() {
            $('#loadingSpinner').hide();
        }
        
        // AJAX 요청 시 자동으로 로딩 표시
        $(document).ajaxStart(function() {
            showLoading();
        }).ajaxStop(function() {
            hideLoading();
        });
        
        // 알림 자동 숨기기
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
        
        // 모바일 사이드바 토글
        $('.navbar-toggler').click(function() {
            $('#sidebar').toggleClass('show');
        });
        
        // 확인 다이얼로그
        $('.confirm-delete').click(function(e) {
            e.preventDefault();
            if (confirm('정말 삭제하시겠습니까?')) {
                $(this).closest('form').submit();
            }
        });
        
        // 실시간 알림 체크 (인증된 사용자만)
        @auth
        function checkNotifications() {
            $.get('{{ route("ajax.complaints.stats") }}', function(data) {
                if (data.urgent_count > 0) {
                    $('#notificationCount').text(data.urgent_count).show();
                    updateNotificationList(data.urgent_complaints);
                } else {
                    $('#notificationCount').hide();
                }
            });
        }
        
        function updateNotificationList(notifications) {
            var html = '';
            if (notifications.length > 0) {
                notifications.forEach(function(notification) {
                    html += '<li class="dropdown-item py-2">';
                    html += '<div class="fw-bold text-danger">긴급 민원</div>';
                    html += '<div class="small text-muted">' + notification.title + '</div>';
                    html += '</li>';
                });
            } else {
                html = '<li class="dropdown-item text-muted text-center py-3">새로운 알림이 없습니다.</li>';
            }
            $('#notificationList').html(html);
        }
        
        // 30초마다 알림 체크
        setInterval(checkNotifications, 30000);
        checkNotifications(); // 초기 로드
        @endauth
    </script>
    
    @stack('scripts')
</body>
</html>
