@extends('layouts.app', ['title' => 'دخول لوحة التحكم'])

@section('content')
    <main class="grid min-h-screen grid-cols-1 bg-white lg:grid-cols-[1fr_520px]">
        <section class="hidden bg-login-panel px-10 py-12 text-white lg:flex lg:flex-col lg:justify-between">
            <a href="{{ route('store.home') }}" class="flex items-center gap-3">
                <span class="grid h-12 w-12 place-items-center rounded-xl bg-white/18 text-lg font-black ring-1 ring-white/30">VX</span>
                <span>
                    <span class="block text-xl font-black">Vinex Hotspot</span>
                    <span class="block text-sm text-emerald-50">إدارة بطاقات الإنترنت والطلبات</span>
                </span>
            </a>
            <div>
                <h1 class="max-w-lg text-5xl font-black leading-tight">لوحة عربية سريعة لأصحاب شبكات الهوتسبوت.</h1>
                <p class="mt-4 max-w-md text-emerald-50">مخزون، طلبات، دفعات، وتقارير في مكان واحد.</p>
            </div>
        </section>

        <section class="grid place-items-center px-4 py-10">
            <form action="{{ route('login.submit') }}" method="POST" class="w-full max-w-md space-y-4">
                @csrf
                <div class="lg:hidden">
                    <a href="{{ route('store.home') }}" class="mb-8 flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-700 text-white">VX</span>
                        <span class="font-black">Vinex Hotspot</span>
                    </a>
                </div>

                <div>
                    <p class="text-sm font-bold text-emerald-700">لوحة التحكم</p>
                    <h1 class="text-3xl font-black">تسجيل الدخول</h1>
                </div>

                <label class="field">
                    <span>البريد الإلكتروني</span>
                    <input name="email" type="email" value="{{ old('email', 'owner@gptnet.test') }}" required autofocus>
                </label>

                <label class="field">
                    <span>كلمة المرور</span>
                    <input name="password" type="password" value="password" required>
                </label>

                <label class="flex items-center gap-2 text-sm font-bold text-slate-600">
                    <input class="h-4 w-4 rounded border-slate-300 text-emerald-700" type="checkbox" name="remember" value="1">
                    تذكرني
                </label>

                <button class="btn btn-primary w-full" type="submit">دخول</button>

                <div class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
                    <strong class="block text-slate-900">حسابات التجربة</strong>
                    <span class="mt-1 block">المالك: owner@gptnet.test / password</span>
                    <span class="block">المدير: admin@vinex.test / password</span>
                </div>
            </form>
        </section>
    </main>
@endsection
