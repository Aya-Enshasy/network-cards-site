@extends('layouts.app', ['title' => 'دخول صاحب الشركة'])

@section('content')
    <main class="owner-login-shell centered-login-shell saas-login-shell">
        <section class="login-showcase" aria-label="Net Zone">
            <div class="login-showcase-brand">
                <span class="brand-image"><img src="{{ asset('images/net-zone-logo.png') }}" alt=""></span>
                <div>
                    <strong>Net Zone</strong>
                    <small>مركز التحكم</small>
                </div>
            </div>

            <div class="login-dashboard-preview">
                <div class="preview-sidebar">
                    <span></span>
                    <b></b>
                    <b></b>
                    <b></b>
                </div>
                <div class="preview-main">
                    <div class="preview-topline">
                        <span></span>
                        <strong></strong>
                    </div>
                    <div class="preview-metrics">
                        <div>
                            <small>طلبات اليوم</small>
                            <strong>48</strong>
                        </div>
                        <div>
                            <small>المبيعات</small>
                            <strong>2.4K</strong>
                        </div>
                        <div>
                            <small>المخزون</small>
                            <strong>814</strong>
                        </div>
                    </div>
                    <div class="preview-chart">
                        <i style="height: 34%"></i>
                        <i style="height: 58%"></i>
                        <i style="height: 44%"></i>
                        <i style="height: 72%"></i>
                        <i style="height: 62%"></i>
                        <i style="height: 86%"></i>
                    </div>
                </div>
            </div>
        </section>

        <section class="owner-login-container">
            <form action="{{ route('login.submit') }}" method="POST" class="login-card centered-login-card">
                @csrf

                <div class="center-login-brand">
                    <span class="brand-image login-brand-logo"><img src="{{ asset('images/net-zone-logo.png') }}" alt=""></span>
                    <div>
                        <strong>Net Zone</strong>
                        <small>بوابة صاحب الشركة</small>
                    </div>
                </div>

                <div class="login-card-head">
                    <div>
                        <p>تسجيل الدخول</p>
                        <h2>أهلا بعودتك</h2>
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
            </form>
        </section>
    </main>
@endsection
