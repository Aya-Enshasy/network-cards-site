<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Vinex Hotspot' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen antialiased">
    @php
        $usesDashboardShell = auth()->check() && (request()->routeIs('dashboard') || request()->routeIs('dashboard.*') || request()->routeIs('admin.*'));
    @endphp

    @if($usesDashboardShell)
        @php
            $pageTitle = match (true) {
                request()->routeIs('dashboard') => 'لوحة التحكم',
                request()->routeIs('dashboard.orders.*') => 'الطلبات',
                request()->routeIs('dashboard.inventory.*'), request()->routeIs('dashboard.packages.*'), request()->routeIs('dashboard.cards.*') => 'المخزون',
                request()->routeIs('dashboard.reports.*') => 'التقارير',
                request()->routeIs('admin.networks.*') => 'الشبكات',
                default => $title ?? 'Vinex Hotspot',
            };

            $navItems = [
                ['label' => 'الرئيسية', 'route' => 'dashboard', 'icon' => 'layout-dashboard', 'active' => request()->routeIs('dashboard')],
                ['label' => 'الطلبات', 'route' => 'dashboard.orders.index', 'icon' => 'receipt-text', 'active' => request()->routeIs('dashboard.orders.*')],
                ['label' => 'المخزون', 'route' => 'dashboard.inventory.index', 'icon' => 'boxes', 'active' => request()->routeIs('dashboard.inventory.*') || request()->routeIs('dashboard.packages.*') || request()->routeIs('dashboard.cards.*')],
                ['label' => 'التقارير', 'route' => 'dashboard.reports.index', 'icon' => 'bar-chart-3', 'active' => request()->routeIs('dashboard.reports.*')],
            ];

            if (auth()->user()->isSuperAdmin()) {
                $navItems[] = ['label' => 'الشبكات', 'route' => 'admin.networks.index', 'icon' => 'network', 'active' => request()->routeIs('admin.networks.*')];
            }
        @endphp

        <div class="app-canvas">
            <aside class="app-sidebar">
                <a class="brand-lockup" href="{{ route('dashboard') }}" aria-label="Vinex Hotspot">
                    <span class="brand-mark">VX</span>
                    <span>
                        <strong>Vinex</strong>
                        <small>Hotspot Cards</small>
                    </span>
                </a>

                <nav class="side-nav" aria-label="التنقل الرئيسي">
                    @foreach($navItems as $item)
                        <a class="side-link {{ $item['active'] ? 'side-link-active' : '' }}" href="{{ route($item['route']) }}">
                            <i data-lucide="{{ $item['icon'] }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="sidebar-profile">
                    <span class="profile-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                    <div>
                        <strong>{{ auth()->user()->name }}</strong>
                        <small>صاحب الشبكة</small>
                    </div>
                </div>
            </aside>

            <div class="app-workspace">
                <header class="app-topbar">
                    <div>
                        <p>لوحة الشبكة</p>
                        <h1>{{ $pageTitle }}</h1>
                    </div>
                    <div class="topbar-actions">
                        <a class="quick-action" href="{{ route('dashboard.inventory.index') }}">
                            <i data-lucide="upload-cloud"></i>
                            <span>رفع بطاقات</span>
                        </a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="icon-button" type="submit" aria-label="خروج">
                                <i data-lucide="log-out"></i>
                            </button>
                        </form>
                    </div>
                </header>

                @if(session('success') || session('error') || $errors->any())
                    <div class="app-alerts">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-error">{{ session('error') }}</div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-error">
                                <strong>راجع الحقول التالية:</strong>
                                <div class="mt-1">{{ $errors->first() }}</div>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="app-content">
                    {{ $slot ?? '' }}
                    @yield('content')
                </div>
            </div>
        </div>
    @else
        @if(session('success') || session('error') || $errors->any())
            <div class="guest-alerts">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                    <div class="alert alert-error">{{ session('error') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-error">
                        <strong>راجع الحقول التالية:</strong>
                        <div class="mt-1">{{ $errors->first() }}</div>
                    </div>
                @endif
            </div>
        @endif

        {{ $slot ?? '' }}
        @yield('content')
    @endif
</body>
</html>
