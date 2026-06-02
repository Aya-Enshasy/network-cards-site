@extends('layouts.app', ['title' => 'لوحة التحكم'])

@section('content')
    @php
        $paymentLabels = ['pending' => 'قيد المراجعة', 'paid' => 'مدفوع', 'rejected' => 'مرفوض'];
        $orderLabels = ['pending' => 'بانتظار المراجعة', 'approved' => 'مقبول', 'completed' => 'مكتمل', 'rejected' => 'مرفوض'];
    @endphp

    <main class="premium-shell min-h-screen px-4 py-7 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <section class="mb-6 grid gap-4 lg:grid-cols-[1fr_360px] lg:items-stretch">
                <div class="glass-panel overflow-hidden p-6">
                    <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-black text-violet-600">أهلًا {{ auth()->user()->name }}</p>
                            <h1 class="mt-2 text-4xl font-black leading-tight text-slate-950">إدارة بطاقات الشبكة من مكان واحد</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-500">
                                راقب الطلبات، ارفع ملفات Excel، وتابع المخزون قبل تسليم أي بطاقة للمشتري.
                            </p>
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <a class="btn btn-primary" href="{{ route('dashboard.inventory.index') }}">
                                <i data-lucide="upload-cloud"></i>
                                رفع بطاقات Excel
                            </a>
                            <a class="btn btn-muted" href="{{ route('dashboard.orders.index') }}">
                                <i data-lucide="receipt-text"></i>
                                مراجعة الطلبات
                            </a>
                        </div>
                    </div>
                </div>

                <div class="stat-card stat-lilac">
                    <span>طلبات معلقة</span>
                    <strong>{{ $pendingOrders }}</strong>
                    <small>تحتاج مراجعة وصل الدفع</small>
                </div>
            </section>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="stat-card stat-mint">
                    <span>طلبات اليوم</span>
                    <strong>{{ $todayOrders }}</strong>
                    <small>طلب جديد خلال اليوم</small>
                </div>
                <div class="stat-card stat-sun">
                    <span>مبيعات اليوم</span>
                    <strong>{{ number_format($todaySales, 2) }} NIS</strong>
                    <small>بعد الموافقة على الطلبات</small>
                </div>
                <div class="stat-card stat-rose">
                    <span>بطاقات متاحة</span>
                    <strong>{{ $availableCards }}</strong>
                    <small>جاهزة للبيع من المخزون</small>
                </div>
                <div class="stat-card stat-lilac">
                    <span>الشبكات</span>
                    <strong>{{ $networks->count() }}</strong>
                    <small>شبكة مرتبطة بهذا الحساب</small>
                </div>
            </section>

            <section class="mt-6 grid gap-6 xl:grid-cols-[1fr_380px]">
                <div class="tool-panel overflow-hidden p-0">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div>
                            <p class="text-xs font-black text-violet-600">آخر النشاط</p>
                            <h2 class="text-xl font-black text-slate-950">آخر الطلبات</h2>
                        </div>
                        <a class="table-action" href="{{ route('dashboard.orders.index') }}">كل الطلبات</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الجوال</th>
                                    <th>الباقات</th>
                                    <th>المبلغ</th>
                                    <th>الدفع</th>
                                    <th>الطلب</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                    <tr>
                                        <td>{{ $order->created_at->format('m/d H:i') }}</td>
                                        <td>{{ $order->phone }}</td>
                                        <td>{{ $order->items->pluck('package.name')->filter()->join('، ') }}</td>
                                        <td>{{ number_format($order->total_amount, 2) }} NIS</td>
                                        <td><span class="badge">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</span></td>
                                        <td><span class="badge">{{ $orderLabels[$order->order_status] ?? $order->order_status }}</span></td>
                                        <td><a class="table-action" href="{{ route('dashboard.orders.show', $order) }}">عرض</a></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-slate-500">لا توجد طلبات بعد.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <aside class="space-y-6">
                    <div class="tool-panel">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-black text-slate-950">التنبيهات</h2>
                            <span class="icon-chip"><i data-lucide="bell"></i></span>
                        </div>
                        <div class="mt-4 space-y-3">
                            @if($pendingOrders > 0)
                                <div class="notice notice-green">طلبات جديدة بانتظار المراجعة: {{ $pendingOrders }}</div>
                            @endif
                            @forelse($lowStockPackages as $package)
                                <div class="notice {{ $package->available_cards_count < 10 ? 'notice-urgent' : ($package->available_cards_count < 20 ? 'notice-critical' : 'notice-amber') }}">
                                    {{ $package->available_cards_count < 10 ? 'تنبيه عاجل' : ($package->available_cards_count < 20 ? 'تنبيه حرج' : 'مخزون منخفض') }}:
                                    {{ $package->network->name }} / {{ $package->name }}
                                    <strong class="block">{{ $package->available_cards_count }} بطاقة</strong>
                                </div>
                            @empty
                                <div class="notice notice-green">كل الباقات فوق حد التنبيه.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="tool-panel">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-black text-slate-950">الشبكات</h2>
                            <span class="icon-chip"><i data-lucide="wifi"></i></span>
                        </div>
                        <div class="mt-4 space-y-3">
                            @foreach($networks as $network)
                                <div class="summary-line">
                                    <div>
                                        <strong>{{ $network->name }}</strong>
                                        <span>{{ $network->orders_count }} طلب / {{ $network->cards_count }} بطاقة</span>
                                    </div>
                                    <a class="table-action" href="{{ route('store.network', $network->slug) }}">المتجر</a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </aside>
            </section>
        </div>
    </main>
@endsection
