@extends('layouts.app', ['title' => 'طلب '.$order->order_number])

@section('content')
    @php
        $paymentLabels = ['pending' => 'قيد المراجعة', 'paid' => 'مدفوع', 'rejected' => 'مرفوض'];
        $orderLabels = ['pending' => 'بانتظار مراجعة الدفع', 'approved' => 'مقبول', 'completed' => 'مكتمل', 'rejected' => 'مرفوض'];
        $deliveredCodes = $order->deliveredCards
            ->map(function ($card) {
                $code = $card->pivot->card_code ?: $card->card_code;
                $password = $card->pivot->card_password ?: $card->card_password;
                $packageLabel = $card->pivot->package_label ?: $card->package_label;

                return trim(($packageLabel ? 'Package: '.$packageLabel.' | ' : '').'Username: '.$code.' | Password: '.$password);
            })
            ->filter()
            ->values();
    @endphp

    <main class="premium-shell min-h-screen px-4 py-6 sm:px-6 lg:px-8">
        <div data-order-access data-token="{{ $order->access_token }}" data-url="{{ $signedOrderUrl }}" hidden></div>

        <section class="mx-auto max-w-6xl space-y-6">
            <div class="glass-panel p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm font-black text-emerald-700">{{ $order->network->name }}</p>
                        <h1 class="mt-2 text-4xl font-black text-slate-950">طلب {{ $order->order_number }}</h1>
                        <p class="mt-2 text-sm text-slate-500">احتفظ بهذا الرابط لمتابعة الطلب واستلام البطاقات بعد الموافقة.</p>
                    </div>
                    <a class="btn btn-glass" href="{{ route('store.network', $order->network->slug) }}">العودة للمتجر</a>
                </div>
            </div>

            <section class="grid gap-4 md:grid-cols-3">
                <div class="stat-card">
                    <span>حالة الدفع</span>
                    <strong>{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</strong>
                </div>
                <div class="stat-card">
                    <span>حالة الطلب</span>
                    <strong>{{ $orderLabels[$order->order_status] ?? $order->order_status }}</strong>
                </div>
                <div class="stat-card">
                    <span>الإجمالي</span>
                    <strong>{{ number_format($order->total_amount, 2) }} NIS</strong>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-[1fr_360px]">
                <div class="glass-panel p-6">
                    <h2 class="text-xl font-black text-slate-950">تفاصيل الطلب</h2>
                    <div class="mt-4 divide-y divide-slate-200">
                        @foreach($order->items as $item)
                            <div class="flex items-center justify-between gap-4 py-3">
                                <div>
                                    <strong class="block text-slate-950">{{ $item->package->name }}</strong>
                                    <span class="text-sm text-slate-500">{{ $item->quantity }} بطاقة × {{ number_format($item->price, 2) }} NIS</span>
                                </div>
                                <strong class="text-slate-950">{{ number_format($item->subtotal, 2) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="glass-panel p-6">
                    <h2 class="text-xl font-black text-slate-950">بيانات العميل</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">الاسم</dt>
                            <dd class="font-bold text-slate-950">{{ $order->customer_name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">الجوال</dt>
                            <dd class="font-bold text-slate-950">{{ $order->phone }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">التاريخ</dt>
                            <dd class="font-bold text-slate-950">{{ $order->created_at->format('Y-m-d H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="glass-panel p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-black text-emerald-700">البطاقات المسلمة</p>
                        <h2 class="text-2xl font-black text-slate-950">بطاقاتك</h2>
                    </div>
                    @if($deliveredCodes->isNotEmpty())
                        <button class="btn btn-dark" type="button" data-copy-all data-copy-label="تم نسخ كل البطاقات">نسخ الكل</button>
                    @endif
                </div>

                @if($order->order_status === 'completed' && $deliveredCodes->isNotEmpty())
                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($order->deliveredCards as $card)
                            @php($code = $card->pivot->card_code ?: $card->card_code)
                            @php($password = $card->pivot->card_password ?: $card->card_password)
                            @php($packageLabel = $card->pivot->package_label ?: $card->package_label)
                            @php($copyText = trim(($packageLabel ? 'Package: '.$packageLabel.' | ' : '').'Username: '.$code.' | Password: '.$password))
                            <div class="code-box premium-code" data-copy-line="{{ $copyText }}">
                                <span>{{ $card->package->name }}</span>
                                @if($packageLabel)
                                    <p class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-800">
                                        نص الباقة في الملف: <span dir="ltr">{{ $packageLabel }}</span>
                                    </p>
                                @endif
                                <div class="delivered-card-fields">
                                    <div>
                                        <small>Username / رقم البطاقة</small>
                                        <strong data-card-code>{{ $code }}</strong>
                                    </div>
                                    <div>
                                        <small>Password / كلمة السر</small>
                                        <strong data-card-code>{{ $password }}</strong>
                                    </div>
                                </div>
                                <button type="button" data-copy-text="{{ $copyText }}" data-copy-label="تم نسخ بيانات البطاقة">نسخ البطاقة</button>
                            </div>
                        @endforeach
                    </div>
                @elseif($order->order_status === 'rejected')
                    <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-800">
                        {{ $order->rejection_reason ?: 'تم رفض الطلب. يرجى التواصل مع صاحب الشبكة.' }}
                    </div>
                @else
                    <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-800">
                        بانتظار مراجعة الدفع. ستظهر هنا بيانات البطاقة بعد موافقة صاحب الشبكة: Username وكلمة السر، ولن يتم خصم أي بطاقة من المخزون قبل الموافقة.
                    </div>
                @endif
            </section>
        </section>
    </main>
@endsection
