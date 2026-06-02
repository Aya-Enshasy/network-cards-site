@extends('layouts.app', ['title' => 'استرجاع طلباتي'])

@section('content')
    <main class="premium-shell grid min-h-screen place-items-center px-4 py-10">
        <form action="{{ route('orders.recover.submit') }}" method="POST" class="glass-panel w-full max-w-2xl space-y-5 p-6">
            @csrf
            <div>
                <p class="text-sm font-black text-emerald-700">استرجاع الطلبات</p>
                <h1 class="mt-2 text-4xl font-black text-slate-950">ابحث عن مشترياتك</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    إذا فقدت رابط الطلب أو استخدمت جهازا جديدا، أدخل رقم الطلب أو رقم الجوال لإظهار رابط الوصول الآمن.
                </p>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="field">
                    <span>رقم الطلب</span>
                    <input name="order_number" value="{{ old('order_number') }}" placeholder="ORD-2026-000001">
                </label>
                <label class="field">
                    <span>رقم الجوال</span>
                    <input name="phone" value="{{ old('phone') }}" placeholder="0599000000">
                </label>
            </div>
            <button class="btn btn-primary w-full" type="submit">استرجاع الطلبات</button>
            <a class="block text-center text-sm font-black text-emerald-800" href="{{ route('store.home') }}">رجوع للمتجر</a>
        </form>
    </main>
@endsection
