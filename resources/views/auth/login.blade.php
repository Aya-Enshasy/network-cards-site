@extends('layouts.app', ['title' => 'دخول صاحب الشركة'])

@section('content')
    <main class="owner-login-shell">
        <section class="login-art">
            <div class="login-brand">
                <span class="brand-image"><img src="{{ asset('images/net-zone-logo.png') }}" alt=""></span>
                <div>
                    <strong>Net Zone</strong>
                    <small>بوابة صاحب الشركة</small>
                </div>
            </div>

            <div class="login-art-copy">
                <span class="eyebrow">رابط منفصل للإدارة</span>
                <h1>إدارة الطلبات والمخزون بدون تعقيد.</h1>
                <p>الزبائن يدخلون المتجر، وصاحب الشركة يدخل من هنا فقط.</p>
            </div>

            <div class="login-preview-card">
                <div class="mini-bars">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <strong>استيراد، موافقة، تسليم</strong>
                <small>مسار واحد واضح.</small>
            </div>
        </section>

        <section class="login-form-wrap">
            <form action="{{ route('login.submit') }}" method="POST" class="login-card">
                @csrf

                <div class="login-card-head">
                    <span class="owner-brand-mark brand-image"><img src="{{ asset('images/net-zone-logo.png') }}" alt=""></span>
                    <div>
                        <p>بوابة المالك</p>
                        <h2>تسجيل الدخول</h2>
                    </div>
                </div>

                <label class="field">
                    <span>البريد الإلكتروني</span>
                    <input name="email" type="email" value="{{ old('email', 'owner@gptnet.test') }}" required autofocus>
                </label>

                <label class="field">
                    <span>كلمة المرور</span>
                    <input name="password" type="password" value="password" required>
                </label>

                <label class="remember-line">
                    <input type="checkbox" name="remember" value="1">
                    <span>تذكرني</span>
                </label>

                <button class="btn btn-dark w-full" type="submit">
                    <i data-lucide="log-in"></i>
                    دخول
                </button>

                <div class="login-demo">
                    <strong>حساب التجربة</strong>
                    <span>owner@gptnet.test / password</span>
                </div>
            </form>
        </section>
    </main>
@endsection
