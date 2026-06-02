@extends('layouts.app', ['title' => 'طلب '.$order->order_number])

@section('content')
    @php
        $paymentLabels = ['pending' => 'قيد المراجعة', 'paid' => 'مدفوع', 'rejected' => 'مرفوض'];
        $orderLabels = ['pending' => 'بانتظار المراجعة', 'approved' => 'مقبول', 'completed' => 'مكتمل', 'rejected' => 'مرفوض'];
    @endphp

    <main class="premium-shell min-h-screen px-4 py-7 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-black text-violet-600">{{ $order->network->name }}</p>
                    <h1 class="text-4xl font-black text-slate-950">طلب {{ $order->order_number }}</h1>
                </div>
                <a class="btn btn-muted" href="{{ route('dashboard.orders.index') }}">
                    <i data-lucide="arrow-right"></i>
                    رجوع للطلبات
                </a>
            </div>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="stat-card stat-mint">
                    <span>العميل</span>
                    <strong>{{ $order->customer_name }}</strong>
                    <small>{{ $order->created_at->format('Y-m-d H:i') }}</small>
                </div>
                <div class="stat-card stat-lilac">
                    <span>الجوال</span>
                    <strong>{{ $order->phone }}</strong>
                    <small>رقم التواصل</small>
                </div>
                <div class="stat-card stat-sun">
                    <span>الدفع</span>
                    <strong>{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</strong>
                    <small>حالة وصل الدفع</small>
                </div>
                <div class="stat-card stat-rose">
                    <span>الإجمالي</span>
                    <strong>{{ number_format($order->total_amount, 2) }}</strong>
                    <small>NIS</small>
                </div>
            </section>

            <section class="mt-6 grid gap-6 xl:grid-cols-[1fr_380px]">
                <div class="space-y-6">
                    <div class="tool-panel">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-black text-slate-950">البطاقات المطلوبة</h2>
                            <span class="icon-chip"><i data-lucide="shopping-bag"></i></span>
                        </div>
                        <div class="mt-4 divide-y divide-slate-100">
                            @foreach($order->items as $item)
                                <div class="flex items-center justify-between gap-4 py-3">
                                    <div>
                                        <strong class="block text-slate-950">{{ $item->package->name }}</strong>
                                        <span class="text-sm text-slate-500">{{ $item->quantity }} × {{ number_format($item->price, 2) }} NIS</span>
                                    </div>
                                    <strong class="text-slate-950">{{ number_format($item->subtotal, 2) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="tool-panel">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-black text-slate-950">البطاقات المسلمة</h2>
                            <span class="icon-chip"><i data-lucide="key-round"></i></span>
                        </div>
                        @if($order->deliveredCards->isNotEmpty())
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                @foreach($order->deliveredCards as $card)
                                    @php($code = $card->pivot->card_code ?: $card->card_code)
                                    @php($password = $card->pivot->card_password ?: $card->card_password)
                                    <div class="code-box">
                                        <div class="delivered-card-fields">
                                            <div>
                                                <small>رقم البطاقة</small>
                                                <strong>{{ $code }}</strong>
                                            </div>
                                            <div>
                                                <small>كلمة السر</small>
                                                <strong>{{ $password }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-3 text-sm text-slate-500">لم يتم تسليم بطاقات بعد.</p>
                        @endif
                    </div>
                </div>

                <aside class="space-y-6">
                    <div class="tool-panel">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-black text-slate-950">وصل الدفع</h2>
                            <span class="icon-chip"><i data-lucide="image"></i></span>
                        </div>
                        @if($order->receipt?->image)
                            <a href="{{ asset('storage/'.$order->receipt->image) }}" target="_blank">
                                <img class="mt-4 aspect-[4/5] w-full rounded-lg object-cover ring-1 ring-slate-200" src="{{ asset('storage/'.$order->receipt->image) }}" alt="وصل الدفع">
                            </a>
                        @else
                            <p class="mt-3 text-sm text-slate-500">لا توجد صورة وصل.</p>
                        @endif

                        @if($order->receipt?->notes)
                            <p class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">{{ $order->receipt->notes }}</p>
                        @endif
                    </div>

                    <div class="tool-panel">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-black text-slate-950">إجراءات</h2>
                            <span class="icon-chip"><i data-lucide="check-circle-2"></i></span>
                        </div>
                        <div class="mt-4 grid gap-3">
                            <form action="{{ route('dashboard.orders.approve', $order) }}" method="POST">
                                @csrf
                                <button class="btn btn-primary w-full" type="submit">
                                    <i data-lucide="badge-check"></i>
                                    موافقة وتسليم البطاقات
                                </button>
                            </form>
                            <form action="{{ route('dashboard.orders.reject', $order) }}" method="POST">
                                @csrf
                                <label class="field mb-3">
                                    <span>سبب الرفض</span>
                                    <textarea name="rejection_reason" rows="3" placeholder="مثال: صورة الوصل غير واضحة أو المبلغ غير مطابق."></textarea>
                                </label>
                                <button class="btn btn-danger w-full" type="submit">رفض الطلب</button>
                            </form>
                        </div>
                    </div>
                </aside>
            </section>
        </div>
    </main>
@endsection
