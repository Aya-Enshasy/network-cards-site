@extends('layouts.app', ['title' => 'لوحة التحكم'])

@section('content')
    <main class="premium-shell min-h-screen px-4 py-7 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-bold text-emerald-700">نظرة عامة</p>
                <h1 class="text-3xl font-black">لوحة التحكم</h1>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a class="btn btn-primary" href="{{ route('dashboard.inventory.index') }}">رفع بطاقات Excel</a>
                <a class="btn btn-muted" href="{{ route('dashboard.orders.index') }}">مراجعة الطلبات</a>
            </div>
        </div>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="stat-card">
                <span>طلبات اليوم</span>
                <strong>{{ $todayOrders }}</strong>
            </div>
            <div class="stat-card">
                <span>مبيعات اليوم</span>
                <strong>{{ number_format($todaySales, 2) }} NIS</strong>
            </div>
            <div class="stat-card">
                <span>بطاقات متاحة</span>
                <strong>{{ $availableCards }}</strong>
            </div>
            <div class="stat-card">
                <span>طلبات معلقة</span>
                <strong>{{ $pendingOrders }}</strong>
            </div>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
            <div class="tool-panel overflow-hidden p-0">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-xl font-black">آخر الطلبات</h2>
                    <a class="text-sm font-bold text-emerald-800" href="{{ route('dashboard.orders.index') }}">كل الطلبات</a>
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
                                    <td>{{ number_format($order->total_amount, 2) }}</td>
                                    <td><span class="badge">{{ $order->payment_status }}</span></td>
                                    <td><span class="badge">{{ $order->order_status }}</span></td>
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
                    <h2 class="text-xl font-black">التنبيهات</h2>
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
                    <h2 class="text-xl font-black">الشبكات</h2>
                    <div class="mt-4 space-y-3">
                        @foreach($networks as $network)
                            <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-3">
                                <div>
                                    <strong class="block">{{ $network->name }}</strong>
                                    <span class="text-sm text-slate-500">{{ $network->orders_count }} طلب / {{ $network->cards_count }} بطاقة</span>
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
