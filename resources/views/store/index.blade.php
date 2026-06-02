@extends('layouts.app', ['title' => ($network?->name ?? 'المتجر')])

@section('content')
    <main class="premium-shell pb-80 lg:pb-12">
        @if(! $network)
            <section class="mx-auto grid min-h-[75vh] max-w-3xl place-items-center px-4 text-center">
                <div class="glass-panel p-8">
                    <div class="brand-mark mx-auto mb-5">VX</div>
                    <h1 class="text-3xl font-black text-slate-950">لا توجد شبكات متاحة حاليًا</h1>
                    <p class="mt-3 text-slate-600">أضف شبكة من لوحة الإدارة لبدء بيع بطاقات الهوتسبوت.</p>
                    <a class="btn btn-primary mt-6" href="{{ route('login') }}">دخول الإدارة</a>
                </div>
            </section>
        @else
            <section>
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <nav class="glass-nav flex flex-col gap-4 p-3 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('store.network', $network->slug) }}" class="flex items-center gap-3">
                            <span class="grid h-12 w-12 place-items-center rounded-lg bg-[#171b25] text-xl font-black text-white">
                                {{ mb_substr($network->name, 0, 1) }}
                            </span>
                            <span>
                                <span class="block text-lg font-black text-slate-950">{{ $network->name }}</span>
                                <span class="block text-xs font-bold text-slate-500">متجر بطاقات الإنترنت</span>
                            </span>
                        </a>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <a data-last-order-link class="btn btn-glass hidden" href="#">آخر عملية شراء</a>
                            <a class="btn btn-glass" href="{{ route('orders.recover') }}">
                                <i data-lucide="search"></i>
                                استرجاع طلباتي
                            </a>
                            <a class="btn btn-dark" href="{{ route('login') }}">لوحة التحكم</a>
                        </div>
                    </nav>

                    <div class="grid gap-8 py-10 lg:grid-cols-[1.05fr_.95fr] lg:items-center">
                        <div>
                            <div class="inline-flex rounded-full border border-violet-100 bg-white px-3 py-1 text-xs font-black text-violet-700 shadow-sm">
                                شراء سريع بدون إنشاء حساب
                            </div>
                            <h1 class="mt-5 max-w-3xl text-4xl font-black leading-tight text-slate-950 sm:text-6xl">
                                اختر بطاقتك وارفع وصل الدفع، والباقي علينا.
                            </h1>
                            <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600">
                                بعد موافقة صاحب الشبكة تظهر لك بيانات البطاقة من رابط آمن: Username وكلمة السر.
                            </p>
                        </div>

                        <div class="glass-panel p-5">
                            <form action="{{ route('orders.recover.submit') }}" method="POST" class="grid gap-3">
                                @csrf
                                <div class="flex items-center gap-3">
                                    <span class="icon-chip"><i data-lucide="search-check"></i></span>
                                    <div>
                                        <p class="text-sm font-black text-slate-950">بحث سريع عن طلب</p>
                                        <p class="text-xs text-slate-500">استخدم رقم الطلب أو رقم الجوال.</p>
                                    </div>
                                </div>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <input name="order_number" placeholder="ORD-2026-000001">
                                    <input name="phone" placeholder="0599000000">
                                </div>
                                <button class="btn btn-primary" type="submit">استرجاع الطلب</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>

            <form action="{{ route('checkout.start') }}" method="POST" data-cart-form>
                @csrf
                <input type="hidden" name="network_id" value="{{ $network->id }}">

                <section class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_390px] lg:px-8">
                    <div>
                        <div class="mb-5 flex items-end justify-between gap-4">
                            <div>
                                <p class="text-sm font-black text-violet-600">الباقات المتاحة</p>
                                <h2 class="text-3xl font-black text-slate-950">اختر باقتك</h2>
                            </div>
                            <span class="hidden rounded-full bg-white px-3 py-1 text-xs font-black text-slate-500 shadow-sm sm:inline-flex">
                                لا يتم خصم المخزون قبل الموافقة
                            </span>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach($network->activePackages as $package)
                                <article class="premium-package" data-package-card>
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 class="text-2xl font-black text-slate-950">{{ $package->name }}</h3>
                                            <p class="mt-2 min-h-12 text-sm leading-6 text-slate-500">{{ $package->description }}</p>
                                        </div>
                                        <span class="price-chip">{{ number_format($package->price) }} NIS</span>
                                    </div>

                                    <div class="mt-6 grid grid-cols-3 gap-2">
                                        <div class="metric-tile">
                                            <dt>المدة</dt>
                                            <dd>{{ $package->duration_hours }} ساعة</dd>
                                        </div>
                                        <div class="metric-tile">
                                            <dt>السرعة</dt>
                                            <dd>{{ $package->speed ?: 'قياسية' }}</dd>
                                        </div>
                                        <div class="metric-tile">
                                            <dt>المتاح</dt>
                                            <dd>{{ $package->available_cards_count }}</dd>
                                        </div>
                                    </div>

                                    <div class="mt-6 flex items-center justify-between gap-3">
                                        <div class="qty-control" aria-label="الكمية">
                                            <button type="button" data-qty-minus data-target="qty-{{ $package->id }}">-</button>
                                            <input id="qty-{{ $package->id }}" name="quantities[{{ $package->id }}]" value="0" inputmode="numeric" min="0" max="{{ min(100, $package->available_cards_count) }}" data-cart-input data-name="{{ $package->name }}" data-price="{{ $package->price }}" data-stock="{{ $package->available_cards_count }}">
                                            <button type="button" data-qty-plus data-target="qty-{{ $package->id }}">+</button>
                                        </div>
                                        <span class="text-xs font-black text-slate-400">تحديث فوري</span>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>

                    <aside class="order-summary premium-summary" data-order-summary>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-black text-violet-600">السلة</p>
                                <h2 class="text-xl font-black text-slate-950">ملخص الطلب</h2>
                            </div>
                            <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-black text-violet-800" data-summary-count>0 بطاقة</span>
                        </div>
                        <div class="mt-4 min-h-28 space-y-2" data-summary-items>
                            <p class="rounded-lg bg-slate-100 px-3 py-3 text-sm text-slate-500">لم يتم اختيار بطاقات بعد.</p>
                        </div>
                        <div class="mt-5 flex items-center justify-between border-t border-slate-200 pt-4">
                            <span class="text-sm text-slate-500">المجموع</span>
                            <strong class="text-3xl font-black text-slate-950" data-summary-total>0 شيكل</strong>
                        </div>
                        <button class="btn btn-primary mt-5 w-full" type="submit">متابعة الدفع</button>
                    </aside>
                </section>
            </form>
        @endif
    </main>
@endsection
