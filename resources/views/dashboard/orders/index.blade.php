@extends('layouts.app', ['title' => 'الطلبات'])

@section('content')
    @php
        $ordersCollection = $orders->getCollection();
        $pendingCount = $ordersCollection->where('order_status', 'pending')->count();
        $completedCount = $ordersCollection->where('order_status', 'completed')->count();
        $rejectedCount = $ordersCollection->where('order_status', 'rejected')->count();
        $paymentLabels = ['pending' => 'قيد المراجعة', 'paid' => 'مدفوع', 'rejected' => 'مرفوض'];
        $orderLabels = ['pending' => 'بانتظار المراجعة', 'approved' => 'مقبول', 'completed' => 'مكتمل', 'rejected' => 'مرفوض'];
    @endphp

    <main class="premium-shell min-h-screen px-4 py-7 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-6 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-sm font-black text-violet-600">إدارة الطلبات</p>
                    <h1 class="text-4xl font-black text-slate-950">طلبات الشراء</h1>
                </div>
                <div class="flex gap-2 overflow-x-auto">
                    <a class="filter-chip {{ ! $status ? 'filter-chip-active' : '' }}" href="{{ route('dashboard.orders.index') }}">الكل</a>
                    <a class="filter-chip {{ $status === 'pending' ? 'filter-chip-active' : '' }}" href="{{ route('dashboard.orders.index', ['status' => 'pending']) }}">معلقة</a>
                    <a class="filter-chip {{ $status === 'completed' ? 'filter-chip-active' : '' }}" href="{{ route('dashboard.orders.index', ['status' => 'completed']) }}">مكتملة</a>
                    <a class="filter-chip {{ $status === 'rejected' ? 'filter-chip-active' : '' }}" href="{{ route('dashboard.orders.index', ['status' => 'rejected']) }}">مرفوضة</a>
                </div>
            </div>

            <section class="mb-6 grid gap-4 sm:grid-cols-3">
                <div class="stat-card stat-sun">
                    <span>قيد المراجعة</span>
                    <strong>{{ $pendingCount }}</strong>
                    <small>ضمن الصفحة الحالية</small>
                </div>
                <div class="stat-card stat-mint">
                    <span>مكتملة</span>
                    <strong>{{ $completedCount }}</strong>
                    <small>تم تسليم بطاقاتها</small>
                </div>
                <div class="stat-card stat-rose">
                    <span>مرفوضة</span>
                    <strong>{{ $rejectedCount }}</strong>
                    <small>وصل أو مبلغ غير مطابق</small>
                </div>
            </section>

            <section class="tool-panel overflow-hidden p-0">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <p class="text-xs font-black text-violet-600">المعاملات</p>
                        <h2 class="text-xl font-black text-slate-950">قائمة الطلبات</h2>
                    </div>
                    <span class="badge">{{ $orders->total() }} طلب</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>التاريخ</th>
                                <th>الشبكة</th>
                                <th>الجوال</th>
                                <th>الباقات</th>
                                <th>المبلغ</th>
                                <th>الدفع</th>
                                <th>الحالة</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                <tr>
                                    <td class="font-black">{{ $order->order_number }}</td>
                                    <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ $order->network->name }}</td>
                                    <td>{{ $order->phone }}</td>
                                    <td>{{ $order->items->pluck('package.name')->filter()->join('، ') }}</td>
                                    <td>{{ number_format($order->total_amount, 2) }} NIS</td>
                                    <td><span class="badge">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</span></td>
                                    <td><span class="badge">{{ $orderLabels[$order->order_status] ?? $order->order_status }}</span></td>
                                    <td><a class="table-action" href="{{ route('dashboard.orders.show', $order) }}">عرض</a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-slate-500">لا توجد طلبات مطابقة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="mt-5">
                {{ $orders->links() }}
            </div>
        </div>
    </main>
@endsection
