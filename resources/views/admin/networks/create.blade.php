@extends('layouts.app', ['title' => 'إضافة شبكة'])

@section('content')
    <main class="premium-shell min-h-screen px-4 py-7 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <p class="text-sm font-bold text-emerald-700">المدير العام</p>
            <h1 class="text-3xl font-black">إضافة شبكة جديدة</h1>
        </div>

        <form action="{{ route('admin.networks.store') }}" method="POST" class="tool-panel space-y-5">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                <label class="field">
                    <span>اسم صاحب الشبكة</span>
                    <input name="owner_name" value="{{ old('owner_name') }}" required>
                </label>
                <label class="field">
                    <span>بريد صاحب الشبكة</span>
                    <input name="owner_email" type="email" value="{{ old('owner_email') }}" required>
                </label>
                <label class="field">
                    <span>جوال صاحب الشبكة</span>
                    <input name="owner_phone" value="{{ old('owner_phone') }}">
                </label>
                <label class="field">
                    <span>كلمة المرور</span>
                    <input name="owner_password" type="password" required>
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="field">
                    <span>اسم الشبكة</span>
                    <input name="name" value="{{ old('name') }}" required>
                </label>
                <label class="field">
                    <span>الحالة</span>
                    <select name="status">
                        <option value="active">فعالة</option>
                        <option value="suspended">موقوفة</option>
                    </select>
                </label>
                <label class="field">
                    <span>رقم جوال باي</span>
                    <input name="wallet_number" value="{{ old('wallet_number') }}">
                </label>
                <label class="field">
                    <span>حساب البنك</span>
                    <input name="bank_account" value="{{ old('bank_account') }}">
                </label>
            </div>

            <label class="field">
                <span>تفاصيل التحويل البنكي</span>
                <textarea name="bank_transfer_details" rows="3">{{ old('bank_transfer_details') }}</textarea>
            </label>

            <label class="field">
                <span>وصف المتجر</span>
                <textarea name="description" rows="4">{{ old('description') }}</textarea>
            </label>

            <div class="flex gap-3">
                <button class="btn btn-primary" type="submit">إنشاء</button>
                <a class="btn btn-muted" href="{{ route('admin.networks.index') }}">إلغاء</a>
            </div>
        </form>
        </div>
    </main>
@endsection
