@extends('layouts.app', ['title' => 'الطلبات'])

@section('content')
    <main class="premium-shell min-h-screen px-4 py-7 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-bold text-emerald-700">إدارة الطلبات</p>
                <h1 class="text-3xl font-black">الطلبات</h1>
            </div>
            <div class="flex gap-2 overflow-x-auto">
                <a class="filter-chip {{ ! $status ? 'filter-chip-active' : '' }}" href="{{ route('dashboard.orders.index') }}">الكل</a>
                <a class="filter-chip {{ $status === 'pending' ? 'filter-chip-active' : '' }}" href="{{ route('dashboard.orders.index', ['status' => 'pending']) }}">معلقة</a>
                <a class="filter-chip {{ $status === 'completed' ? 'filter-chip-active' : '' }}" href="{{ route('dashboard.orders.index', ['status' => 'completed']) }}">مكتملة</a>
                <a class="filter-chip {{ $status === 'rejected' ? 'filter-chip-active' : '' }}" href="{{ route('dashboard.orders.index', ['status' => 'rejected']) }}">مرفوضة</a>
            </div>
        </div>

        <section class="tool-panel overflow-hidden p-0">
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
                                <td>{{ number_format($order->total_amount, 2) }}</td>
                                <td><span class="badge">{{ $order->payment_status }}</span></td>
                                <td><span class="badge">{{ $order->order_status }}</span></td>
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
