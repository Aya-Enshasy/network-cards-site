@extends('layouts.app', ['title' => 'دخول صاحب الشركة'])

@section('content')
    <main class="owner-login-shell centered-login-shell">
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
