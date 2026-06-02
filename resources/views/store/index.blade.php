@extends('layouts.app', ['title' => ($network?->name ?? 'Net Zone')])

@section('content')
    @php
        $siteName = 'Net Zone';
        $logo = $network?->logo ? asset('storage/'.$network->logo) : asset('images/net-zone-logo.png');
    @endphp

    <main class="shop-shell">
        @if(! $network)
            <section class="customer-empty">
                <img class="brand-image-lg" src="{{ asset('images/net-zone-logo.png') }}" alt="">
                <h1>لا توجد شبكة متاحة حاليا</h1>
                <p>سيظهر المتجر هنا بعد تفعيل الشبكة.</p>
            </section>
        @else
            <section class="shop-stage">
                <header class="shop-nav">
                    <a href="{{ route('store.network', $network->slug) }}" class="shop-brand">
                        <span class="brand-image"><img src="{{ $logo }}" alt=""></span>
                        <div>
                            <strong>{{ $siteName }}</strong>
                            <small>{{ $network->name }}</small>
                        </div>
                    </a>

                    <nav class="shop-actions" aria-label="روابط الزبائن">
                        <a href="{{ route('orders.recover') }}">
                            <i data-lucide="search-check"></i>
                            تتبع طلب
                        </a>
                    </nav>
                </header>

                <form action="{{ route('checkout.start') }}" method="POST" data-cart-form class="shop-grid">
                    @csrf
                    <input type="hidden" name="network_id" value="{{ $network->id }}">

                    <section class="shop-main">
                        <div class="shop-intro compact-shop-intro">
                            <span class="eyebrow">شراء سريع</span>
                            <h1>اختر عدد البطاقات وارفع وصل الدفع.</h1>
                            <p>ستظهر لك أرقام البطاقات وكلمات السر بعد موافقة صاحب الشبكة.</p>
                        </div>

                        <div class="package-grid">
                            @foreach($network->activePackages as $package)
                                <article class="customer-package" data-package-card>
                                    <div class="package-top">
                                        <span class="package-icon"><i data-lucide="ticket"></i></span>
                                        <span class="price-pill">{{ number_format($package->price) }} NIS</span>
                                    </div>

                                    <h2>{{ $package->name }}</h2>
                                    <p>{{ $package->description }}</p>

                                    <dl class="package-specs">
                                        <div>
                                            <dt>المدة</dt>
                                            <dd>{{ $package->duration_hours }} ساعة</dd>
                                        </div>
                                        <div>
                                            <dt>السرعة</dt>
                                            <dd>{{ $package->speed ?: 'قياسية' }}</dd>
                                        </div>
                                        <div>
                                            <dt>المتاح</dt>
                                            <dd>{{ $package->available_cards_count }}</dd>
                                        </div>
                                    </dl>

                                    <div class="qty-control" aria-label="الكمية">
                                        <button type="button" data-qty-minus data-target="qty-{{ $package->id }}">-</button>
                                        <input id="qty-{{ $package->id }}" name="quantities[{{ $package->id }}]" value="0" inputmode="numeric" min="0" max="{{ min(100, $package->available_cards_count) }}" data-cart-input data-name="{{ $package->name }}" data-price="{{ $package->price }}" data-stock="{{ $package->available_cards_count }}">
                                        <button type="button" data-qty-plus data-target="qty-{{ $package->id }}">+</button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <aside class="customer-summary order-summary" data-order-summary>
                        <div class="summary-head">
                            <span class="icon-chip"><i data-lucide="shopping-bag"></i></span>
                            <div>
                                <p>السلة</p>
                                <h2>ملخص الطلب</h2>
                            </div>
                            <strong data-summary-count>0 بطاقة</strong>
                        </div>

                        <div class="summary-items" data-summary-items>
                            <p class="empty-cart">اختر الكمية من البطاقة المتاحة.</p>
                        </div>

                        <div class="summary-total">
                            <span>الإجمالي</span>
                            <strong data-summary-total>0 شيكل</strong>
                        </div>

                        <button class="btn btn-primary w-full" type="submit">
                            <i data-lucide="credit-card"></i>
                            متابعة الدفع
                        </button>
                    </aside>
                </form>
            </section>
        @endif
    </main>
@endsection
