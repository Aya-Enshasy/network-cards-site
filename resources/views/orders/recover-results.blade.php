@extends('layouts.app', ['title' => 'نتائج الاسترجاع'])

@section('content')
    <main class="premium-shell min-h-screen px-4 py-10 sm:px-6 lg:px-8">
        <section class="mx-auto max-w-4xl space-y-5">
            <div class="glass-panel p-6">
                <p class="text-sm font-black text-emerald-700">استرجاع الطلبات</p>
                <h1 class="mt-2 text-4xl font-black text-slate-950">نتائج البحث</h1>
                <p class="mt-2 text-sm text-slate-500">كل رابط أدناه موقّع وآمن للوصول إلى الطلب.</p>
            </div>

            @forelse($orders as $order)
                <article class="glass-panel p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-xl font-black text-slate-950">{{ $order->order_number }}</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $order->network->name }} · {{ $order->created_at->format('Y-m-d H:i') }} · {{ number_format($order->total_amount, 2) }} NIS
                            </p>
                        </div>
                        <a class="btn btn-primary" href="{{ $orderLinks[$order->id] }}">فتح الطلب</a>
                    </div>
                </article>
            @empty
                <div class="glass-panel p-6 text-center">
                    <h2 class="text-xl font-black text-slate-950">لا توجد طلبات مطابقة</h2>
                    <p class="mt-2 text-sm text-slate-500">تأكد من رقم الطلب أو رقم الجوال وحاول مرة أخرى.</p>
                </div>
            @endforelse

            <div class="text-center">
                <a class="btn btn-glass" href="{{ route('orders.recover') }}">بحث جديد</a>
            </div>
        </section>
    </main>
@endsection
