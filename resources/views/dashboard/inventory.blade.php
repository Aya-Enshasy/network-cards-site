@extends('layouts.app', ['title' => 'المخزون'])

@section('content')
    @php
        $mainPackage = $packages->first();
        $totalAvailable = $packages->sum('available_cards_count');
        $totalSold = $packages->sum('sold_cards_count');
        $totalCards = $packages->sum('cards_count');
        $latestImport = $imports->first();
        $defaultLogo = asset('images/net-zone-logo.png');
        $networkLogo = $network?->logo ? asset('storage/'.$network->logo) : $defaultLogo;
        $ownerAvatar = auth()->user()->avatar ? asset('storage/'.auth()->user()->avatar) : $networkLogo;
    @endphp

    <main class="edu-page inventory-page">
        @if($networks->isEmpty())
            <section class="edu-card edu-empty">
                <span class="edu-soft-icon"><i data-lucide="wifi-off"></i></span>
                <h2>لا توجد شبكات مرتبطة بهذا الحساب.</h2>
            </section>
        @else
            <section class="edu-overview">
                <div class="edu-section-title">
                    <div>
                        <span>نظرة عامة</span>
                        <h2>{{ $network?->name }}</h2>
                    </div>
                    <a class="edu-inline-link" href="{{ route('store.home') }}">
                        <i data-lucide="store"></i>
                        فتح المتجر
                    </a>
                </div>

                <div class="edu-metrics">
                    <article class="edu-metric metric-green">
                        <span><i data-lucide="ticket-check"></i></span>
                        <p>بطاقات متاحة</p>
                        <strong>{{ $totalAvailable }}</strong>
                    </article>
                    <article class="edu-metric metric-coral">
                        <span><i data-lucide="badge-check"></i></span>
                        <p>بطاقات مستعملة</p>
                        <strong>{{ $totalSold }}</strong>
                    </article>
                    <article class="edu-metric metric-blue">
                        <span><i data-lucide="file-spreadsheet"></i></span>
                        <p>إجمالي المستورد</p>
                        <strong>{{ $totalCards }}</strong>
                    </article>
                    <article class="edu-metric metric-peach">
                        <span><i data-lucide="package-open"></i></span>
                        <p>أنواع البطاقات</p>
                        <strong>{{ $packages->count() }}</strong>
                    </article>
                </div>
            </section>

            <section class="edu-layout">
                <div class="edu-main-column">
                    <section class="edu-card edu-table-card cards-table-card">
                        <div class="edu-card-head">
                            <div>
                                <span>جدول صاحب المشروع</span>
                                <h2>البطاقات المستوردة</h2>
                                <p>كل بطاقة من ملف Excel تظهر هنا مع حالتها. المستعمل يظهر بجانبه رقم الطلب واسم العميل.</p>
                            </div>
                            <span class="edu-count">{{ $cards->count() }} بطاقة</span>
                        </div>

                        <div class="edu-table-scroll">
                            <table class="edu-table">
                                <thead>
                                    <tr>
                                        <th>رقم البطاقة</th>
                                        <th>كلمة السر</th>
                                        <th>نوع البطاقة</th>
                                        <th>الحالة</th>
                                        <th>الطلب المرتبط</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($cards as $card)
                                        <tr>
                                            <td>
                                                <div class="edu-card-code">
                                                    <span class="edu-row-icon"><i data-lucide="ticket"></i></span>
                                                    <strong dir="ltr">{{ $card->card_code }}</strong>
                                                </div>
                                            </td>
                                            <td><span class="number-cell" dir="ltr">{{ $card->card_password }}</span></td>
                                            <td>{{ $card->package?->name ?? $card->package_label }}</td>
                                            <td>
                                                @if($card->status === 'sold')
                                                    <span class="edu-status status-used">مستعملة</span>
                                                @else
                                                    <span class="edu-status status-ready">متاحة</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($card->used_order_number)
                                                    <strong>{{ $card->used_order_number }}</strong>
                                                    <small class="edu-muted-line">{{ $card->used_customer_name }}</small>
                                                @else
                                                    <span class="text-slate-400">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-slate-500">
                                                لا توجد بطاقات مستوردة بعد. ارفع ملف Excel من صندوق الاستيراد.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="edu-card edu-package-card">
                        <div class="edu-card-head">
                            <div>
                                <span>النوع المعروض للزبون</span>
                                <h2>نوع بطاقة واحد فقط</h2>
                                <p>المتجر يعرض هذا النوع فقط، وأي ملف Excel جديد سيتم ربطه به تلقائيا.</p>
                            </div>
                            <span class="edu-count">10 ساعات</span>
                        </div>

                        @if($mainPackage)
                            <div class="edu-one-package">
                                <span class="edu-package-art"><i data-lucide="ticket"></i></span>
                                <div>
                                    <h3>{{ $mainPackage->name }}</h3>
                                    <p>{{ $mainPackage->duration_hours }} ساعات / {{ $mainPackage->speed }} / {{ number_format($mainPackage->price, 2) }} NIS</p>
                                </div>
                                <strong>{{ $mainPackage->available_cards_count }} متاحة</strong>
                            </div>
                        @endif
                    </section>
                </div>

                <aside class="edu-side-column">
                    <section class="edu-card edu-profile-card" id="company-profile">
                        <div class="edu-card-head compact-head">
                            <div>
                                <span>البروفايل</span>
                                <h2>بيانات الشركة</h2>
                            </div>
                        </div>

                        <form action="{{ route('dashboard.payment.update') }}" method="POST" enctype="multipart/form-data" class="edu-upload-form profile-form">
                            @csrf
                            <input type="hidden" name="network_id" value="{{ $network->id }}">
                            <input type="hidden" name="wallet_number" value="{{ $network->wallet_number }}">
                            <input type="hidden" name="bank_account" value="{{ $network->bank_account }}">
                            <input type="hidden" name="bank_transfer_details" value="{{ $network->bank_transfer_details }}">
                            <input type="hidden" name="description" value="{{ $network->description }}">

                            <div class="profile-preview">
                                <img src="{{ $ownerAvatar }}" alt="أفاتار صاحب الشركة">
                                <div>
                                    <strong>{{ auth()->user()->name }}</strong>
                                    <span>{{ $network->name }}</span>
                                </div>
                            </div>

                            <label class="field">
                                <span>اسم الشبكة</span>
                                <input name="name" value="{{ old('name', $network->name) }}" required>
                            </label>

                            <label class="field">
                                <span>اسم صاحب الشركة</span>
                                <input name="owner_name" value="{{ old('owner_name', auth()->user()->name) }}" required>
                            </label>

                            <div class="profile-files">
                                <label class="field">
                                    <span>أفاتار</span>
                                    <input name="avatar" type="file" accept="image/*">
                                </label>
                                <label class="field">
                                    <span>لوغو</span>
                                    <input name="logo" type="file" accept="image/*">
                                </label>
                            </div>

                            <button class="btn btn-primary w-full" type="submit">
                                <i data-lucide="save"></i>
                                حفظ البروفايل
                            </button>
                        </form>
                    </section>

                    <section class="edu-card edu-payment-settings-card" id="payment-settings">
                        <div class="edu-card-head">
                            <div>
                                <span>الدفع والتحويل</span>
                                <h2>بيانات الدفع للزبون</h2>
                                <p>هذه البيانات تظهر في صفحة الدفع ويمكن للزبون نسخها مباشرة.</p>
                            </div>
                            <span class="edu-soft-icon"><i data-lucide="wallet-cards"></i></span>
                        </div>

                        <form action="{{ route('dashboard.payment.update') }}" method="POST" class="edu-upload-form payment-settings-form">
                            @csrf
                            <input type="hidden" name="network_id" value="{{ $network->id }}">
                            <input type="hidden" name="name" value="{{ $network->name }}">
                            <input type="hidden" name="owner_name" value="{{ auth()->user()->name }}">
                            <input type="hidden" name="description" value="{{ $network->description }}">

                            <label class="field">
                                <span>Jawwal Pay</span>
                                <input name="wallet_number" value="{{ old('wallet_number', $network->wallet_number) }}" placeholder="0599 123 456" dir="ltr">
                            </label>

                            <label class="field">
                                <span>Bank of Palestine</span>
                                <input name="bank_account" value="{{ old('bank_account', $network->bank_account) }}" placeholder="PS92 PALS 0000 0000 1234 5678" dir="ltr">
                            </label>

                            <label class="field">
                                <span>تعليمات التحويل البنكي</span>
                                <textarea name="bank_transfer_details" rows="3" placeholder="حوّل المبلغ ثم ارفع صورة الوصل من صفحة الدفع.">{{ old('bank_transfer_details', $network->bank_transfer_details) }}</textarea>
                            </label>

                            <button class="btn btn-primary w-full" type="submit">
                                <i data-lucide="save"></i>
                                حفظ بيانات الدفع
                            </button>
                        </form>
                    </section>

                    <section class="edu-card edu-upload-card">
                        <div class="edu-card-head">
                            <div>
                                <span>استيراد جديد</span>
                                <h2>رفع ملف Excel</h2>
                                <p>Username / Password / Package</p>
                            </div>
                            <span class="edu-soft-icon"><i data-lucide="upload-cloud"></i></span>
                        </div>

                        <form action="{{ route('dashboard.cards.import') }}" method="POST" enctype="multipart/form-data" class="edu-upload-form" data-card-import-form data-max-upload-bytes="4194304">
                            @csrf
                            <input type="hidden" name="network_id" value="{{ $network->id }}">
                            @if($mainPackage)
                                <input type="hidden" name="package_id" value="{{ $mainPackage->id }}">
                            @endif

                            <label class="field">
                                <span>نوع البطاقة</span>
                                <input value="{{ $mainPackage?->name ?? 'بطاقة 10 ساعات' }}" disabled>
                            </label>

                            <label class="field upload-drop">
                                <span>ملف البطاقات</span>
                                <input name="file" type="file" accept=".xlsx,.xls,.csv" required data-card-import-file>
                            </label>

                            <p class="upload-guidance">
                                الصيغة المطلوبة: أعمدة Username / Password / Package. الحد الأقصى على Vercel هو 4MB، وللملفات الكبيرة ارفعيها CSV مقسم على دفعات.
                            </p>

                            <p class="upload-status" data-card-import-status aria-live="polite"></p>

                            <button class="btn btn-dark w-full" type="submit">
                                <i data-lucide="upload-cloud"></i>
                                استيراد الملف
                            </button>
                        </form>

                        @if(session('import_errors'))
                            <div class="edu-import-errors">
                                <strong>ملاحظات آخر استيراد</strong>
                                @foreach(session('import_errors') as $importError)
                                    <span>{{ $importError }}</span>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section class="edu-card edu-tests-card">
                        <div class="edu-card-head">
                            <div>
                                <span>سجل العمليات</span>
                                <h2>آخر الاستيرادات</h2>
                                <p>ملخص سريع لكل ملف تم رفعه.</p>
                            </div>
                        </div>

                        <div class="edu-log-list">
                            @forelse($imports as $import)
                                <div class="edu-log-item">
                                    <span class="edu-log-icon"><i data-lucide="file-spreadsheet"></i></span>
                                    <div>
                                        <strong>{{ $import->file_name }}</strong>
                                        <small>{{ $import->created_at->format('Y-m-d H:i') }}</small>
                                    </div>
                                    <span>{{ $import->imported_count }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">لا يوجد استيراد بعد.</p>
                            @endforelse
                        </div>

                        @if($latestImport)
                            <div class="edu-import-summary">
                                <span>آخر نتيجة</span>
                                <strong>{{ $latestImport->imported_count }} بطاقة مستوردة</strong>
                                <small>{{ $latestImport->duplicate_count }} مكرر / {{ $latestImport->failed_count }} فشل</small>
                            </div>
                        @endif
                    </section>
                </aside>
            </section>
        @endif
    </main>
@endsection
