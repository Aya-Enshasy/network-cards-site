<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Net Zone' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen antialiased">
    @php
        $usesOwnerShell = auth()->check() && (request()->routeIs('dashboard') || request()->routeIs('dashboard.*') || request()->routeIs('admin.*'));

        $pageTitle = match (true) {
            request()->routeIs('dashboard') => 'الرئيسية',
            request()->routeIs('dashboard.orders.*') => 'الطلبات',
            request()->routeIs('dashboard.inventory.*'), request()->routeIs('dashboard.packages.*'), request()->routeIs('dashboard.cards.*') => 'المخزون',
            request()->routeIs('dashboard.reports.*') => 'التقارير',
            request()->routeIs('admin.networks.*') => 'الشبكات',
            default => $title ?? 'Net Zone',
        };

        $navItems = [
            ['label' => 'الرئيسية', 'route' => 'dashboard', 'icon' => 'layout-dashboard', 'active' => request()->routeIs('dashboard')],
            ['label' => 'الطلبات', 'route' => 'dashboard.orders.index', 'icon' => 'receipt-text', 'active' => request()->routeIs('dashboard.orders.*')],
            ['label' => 'المخزون', 'route' => 'dashboard.inventory.index', 'icon' => 'package-open', 'active' => request()->routeIs('dashboard.inventory.*') || request()->routeIs('dashboard.packages.*') || request()->routeIs('dashboard.cards.*')],
            ['label' => 'التقارير', 'route' => 'dashboard.reports.index', 'icon' => 'bar-chart-3', 'active' => request()->routeIs('dashboard.reports.*')],
        ];

        if ($usesOwnerShell && auth()->user()->isSuperAdmin()) {
            $navItems[] = ['label' => 'الشبكات', 'route' => 'admin.networks.index', 'icon' => 'network', 'active' => request()->routeIs('admin.networks.*')];
        }

        $siteName = 'Net Zone';
        $defaultLogo = asset('images/net-zone-logo.png');
        $ownerName = auth()->check() ? auth()->user()->name : '';
        $primaryNetwork = null;
        $notificationOrders = collect();

        if ($usesOwnerShell) {
            $primaryNetwork = auth()->user()->isSuperAdmin()
                ? \App\Models\Network::query()->orderBy('name')->first()
                : auth()->user()->networks()->orderBy('name')->first();

            $networkIds = auth()->user()->isSuperAdmin()
                ? \App\Models\Network::query()->pluck('id')
                : auth()->user()->networks()->pluck('id');

            $notificationOrders = \App\Models\Order::query()
                ->whereIn('network_id', $networkIds)
                ->where('order_status', 'pending')
                ->with('network')
                ->latest()
                ->limit(5)
                ->get();
        }

        $brandLogo = $primaryNetwork?->logo ? asset('storage/'.$primaryNetwork->logo) : $defaultLogo;
        $ownerAvatar = auth()->check() && auth()->user()->avatar ? asset('storage/'.auth()->user()->avatar) : $brandLogo;
        $profileUrl = route('dashboard.inventory.index').'#company-profile';
        $notificationCount = $notificationOrders->count();
    @endphp

    @if($usesOwnerShell)
        <div class="owner-shell edu-shell">
            <aside class="owner-sidebar edu-sidebar">
                <a class="owner-brand edu-brand" href="{{ route('dashboard') }}" aria-label="لوحة صاحب المشروع">
                    <span class="owner-brand-mark edu-logo"><img src="{{ $brandLogo }}" alt=""></span>
                    <strong>{{ $siteName }}</strong>
                </a>

                <nav class="owner-nav edu-nav" aria-label="تنقل لوحة صاحب المشروع">
                    @foreach($navItems as $item)
                        <a class="owner-nav-link edu-nav-link {{ $item['active'] ? 'owner-nav-link-active edu-nav-active' : '' }}" href="{{ route($item['route']) }}">
                            <i data-lucide="{{ $item['icon'] }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="edu-premium">
                    <span class="edu-side-chip"><i data-lucide="sparkles"></i></span>
                    <strong>{{ $primaryNetwork?->name ?? 'شبكتي' }}</strong>
                    <p>إدارة البطاقات والطلبات.</p>
                    <a href="{{ route('store.home') }}">
                        <i data-lucide="store"></i>
                        رابط المتجر
                    </a>
                </div>
            </aside>

            <div class="owner-workspace edu-workspace">
                <header class="owner-topbar edu-topbar">
                    <div class="edu-page-name">
                        <small>لوحة صاحب المشروع</small>
                        <h1>{{ $pageTitle }}</h1>
                    </div>

                    <div class="owner-topbar-actions edu-profile">
                        <div class="edu-notifications" data-notifications>
                            <button class="edu-bell" type="button" aria-label="التنبيهات" aria-expanded="false" aria-controls="owner-notifications" data-notification-toggle>
                                <i data-lucide="bell"></i>
                                @if($notificationCount > 0)
                                    <span>{{ $notificationCount }}</span>
                                @endif
                            </button>

                            <div class="edu-notification-panel" id="owner-notifications" data-notification-panel hidden>
                                <div class="edu-notification-head">
                                    <strong>التنبيهات</strong>
                                    <small>{{ $notificationCount }} جديد</small>
                                </div>

                                <div class="edu-notification-list">
                                    @forelse($notificationOrders as $order)
                                        <a class="edu-notification-item" href="{{ route('dashboard.orders.show', $order) }}">
                                            <span><i data-lucide="receipt-text"></i></span>
                                            <div>
                                                <strong>طلب جديد {{ $order->order_number }}</strong>
                                                <small>{{ $order->network?->name }} / {{ number_format($order->total_amount, 2) }} NIS</small>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="edu-notification-empty">لا توجد تنبيهات حاليا</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <a class="owner-avatar edu-avatar" href="{{ $profileUrl }}" aria-label="تعديل بروفايل الشركة">
                            <img src="{{ $ownerAvatar }}" alt="">
                        </a>

                        <div class="edu-user-copy">
                            <strong>{{ $ownerName }}</strong>
                            <small>#{{ str_pad((string) auth()->id(), 5, '0', STR_PAD_LEFT) }}</small>
                        </div>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="edu-logout" type="submit" aria-label="تسجيل الخروج">
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

                <div class="owner-content edu-content">
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
