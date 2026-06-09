@extends('layouts.app', ['title' => 'الدفع - '.$network->name])

@section('content')
    <main class="premium-shell min-h-screen px-4 py-6 sm:px-6 lg:px-8">
        <section class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[1fr_390px]">
            <div class="space-y-6">
                <a class="btn btn-glass" href="{{ route('store.network', $network->slug) }}">
                    <i data-lucide="arrow-right"></i>
                    رجوع للمتجر
                </a>

                <div class="glass-panel p-6">
                    <p class="text-sm font-black text-violet-600">{{ $network->name }}</p>
                    <h1 class="mt-2 text-4xl font-black text-slate-950">إتمام الطلب والدفع</h1>
                    <p class="mt-3 max-w-2xl leading-7 text-slate-600">
                        لن يتم حجز أو بيع أي بطاقة الآن. سيتم تسليم رقم البطاقة وكلمة السر فقط بعد مراجعة الوصل والموافقة.
                    </p>
                </div>

                <div class="payment-method-grid">
                    <div class="payment-card payment-method-card">
                        <div class="payment-method-head">
                            <span class="icon-chip">JP</span>
                            <div>
                                <small>Pal Pay</small>
                                <h2>بال باي</h2>
                            </div>
                        </div>
                        <strong class="payment-value" dir="ltr">{{ $network->wallet_number ?: 'غير محدد' }}</strong>
                        @if(filled($network->wallet_number))
                            <button class="payment-copy-button" type="button" data-copy-text="{{ $network->wallet_number }}" data-copy-label="تم نسخ رقم Jawwal Pay">
                                <i data-lucide="copy"></i>
                                نسخ الرقم
                            </button>
                        @endif
                    </div>
                    <div class="payment-card payment-method-card">
                        <div class="payment-method-head">
                            <span class="icon-chip">BP</span>
                            <div>
                                <small>Bank of Palestine</small>
                                <h2>حساب البنك</h2>
                            </div>
                        </div>
                        <strong class="payment-value payment-value-bank" dir="ltr">{{ $network->bank_account ?: 'غير محدد' }}</strong>
                        @if(filled($network->bank_account))
                            <button class="payment-copy-button" type="button" data-copy-text="{{ $network->bank_account }}" data-copy-label="تم نسخ حساب البنك">
                                <i data-lucide="copy"></i>
                                نسخ الحساب
                            </button>
                        @endif
                    </div>
                    <div class="payment-card payment-method-card">
                        <div class="payment-method-head">
                            <span class="icon-chip">BT</span>
                            <div>
                                <small>تحويل بنكي</small>
                                <h2>تعليمات التحويل</h2>
                            </div>
                        </div>
                        <p class="payment-instructions">{{ $network->bank_transfer_details ?: 'غير محدد' }}</p>
                        @if(filled($network->bank_transfer_details))
                            <button class="payment-copy-button" type="button" data-copy-text="{{ $network->bank_transfer_details }}" data-copy-label="تم نسخ تعليمات التحويل">
                                <i data-lucide="copy"></i>
                                نسخ التعليمات
                            </button>
                        @endif
                    </div>
                </div>

                <form
                    action="{{ route('orders.store', $network->slug) }}"
                    method="POST"
                    class="glass-panel space-y-4 p-6"
                    data-cloudinary-receipt-form
                    data-cloudinary-signature-url="{{ route('checkout.receipt-signature', $network->slug) }}"
                    data-cloudinary-max-bytes="{{ (int) config('services.cloudinary.max_receipt_kb', 4096) * 1024 }}"
                >
                    @csrf
                    <div class="flex items-center gap-3">
                        <span class="icon-chip"><i data-lucide="user-round"></i></span>
                        <div>
                            <h2 class="text-xl font-black text-slate-950">معلومات العميل</h2>
                            <p class="mt-1 text-sm text-slate-500">لا تحتاج إلى حساب. سيتم حفظ رابط الطلب الآمن على جهازك.</p>
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="field">
                            <span>الاسم الكامل</span>
                            <input name="customer_name" value="{{ old('customer_name') }}" required>
                        </label>
                        <label class="field">
                            <span>رقم الجوال</span>
                            <input name="phone" value="{{ old('phone') }}" required>
                        </label>
                    </div>
                    <label class="field">
                        <span>ملاحظات اختيارية</span>
                        <textarea name="notes" rows="3">{{ old('notes') }}</textarea>
                    </label>
                    <label class="field upload-drop">
                        <span>صورة وصل الدفع</span>
                        <input type="file" accept="image/*" required data-cloudinary-receipt-file>
                        <input name="receipt_url" type="hidden" value="{{ old('receipt_url') }}" data-cloudinary-receipt-url>
                        <input name="receipt_public_id" type="hidden" value="{{ old('receipt_public_id') }}" data-cloudinary-receipt-public-id>
                        <input name="receipt_original_name" type="hidden" value="{{ old('receipt_original_name') }}" data-cloudinary-receipt-original-name>
                        <small data-cloudinary-receipt-status role="status" aria-live="polite"></small>
                    </label>
                    <button class="btn btn-primary w-full md:w-auto" type="submit">إرسال للمراجعة</button>
                </form>
            </div>

            <aside class="premium-summary h-fit lg:sticky lg:top-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black text-slate-950">البطاقات المختارة</h2>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-500">قيد المراجعة</span>
                </div>
                <div class="mt-4 space-y-3">
                    @foreach($summary as $item)
                        <div class="summary-line">
                            <div>
                                <strong>{{ $item['package']->name }}</strong>
                                <span>{{ $item['quantity'] }} × {{ number_format($item['package']->price, 2) }} NIS</span>
                            </div>
                            <strong>{{ number_format($item['subtotal'], 2) }}</strong>
                        </div>
                    @endforeach
                </div>
                <div class="mt-5 flex items-center justify-between border-t border-slate-200 pt-4">
                    <span class="text-sm text-slate-500">الإجمالي</span>
                    <strong class="text-3xl font-black text-slate-950">{{ number_format($total, 2) }} NIS</strong>
                </div>
            </aside>
        </section>
    </main>
@endsection
