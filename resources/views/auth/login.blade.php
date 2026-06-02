@extends('layouts.app', ['title' => 'دخول لوحة التحكم'])

@section('content')
    <main class="grid min-h-screen place-items-center px-4 py-10">
        <form action="{{ route('login.submit') }}" method="POST" class="login-card w-full max-w-xl space-y-5 p-6 sm:p-8">
            @csrf

            <a href="{{ route('store.home') }}" class="mx-auto flex w-fit items-center gap-3">
                <span class="brand-mark">VX</span>
                <span>
                    <strong class="block text-xl font-black text-slate-950">Vinex Hotspot</strong>
                    <small class="block text-sm font-bold text-slate-500">إدارة بطاقات الإنترنت والطلبات</small>
                </span>
            </a>

            <div class="pt-4 text-center">
                <p class="text-sm font-black text-violet-600">لوحة التحكم</p>
                <h1 class="mt-2 text-4xl font-black text-slate-950">تسجيل الدخول</h1>
                <p class="mt-2 text-sm text-slate-500">أدخل بيانات صاحب الشبكة للوصول إلى الطلبات والمخزون.</p>
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
                <input class="h-4 w-4 rounded border-slate-300 text-violet-700" type="checkbox" name="remember" value="1">
                تذكرني
            </label>

            <button class="btn btn-dark w-full" type="submit">دخول</button>

            <div class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
                <strong class="block text-slate-900">حسابات التجربة</strong>
                <span class="mt-1 block">المالك: owner@gptnet.test / password</span>
                <span class="block">المدير: admin@vinex.test / password</span>
            </div>
        </form>
    </main>
@endsection
