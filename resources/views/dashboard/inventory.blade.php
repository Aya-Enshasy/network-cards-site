@extends('layouts.app', ['title' => 'المخزون'])

@section('content')
    @php
        $totalAvailable = $packages->sum('available_cards_count');
        $totalSold = $packages->sum('sold_cards_count');
        $totalCards = $packages->sum('cards_count');
    @endphp

    <main class="premium-shell min-h-screen px-4 py-7 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-black text-violet-600">بطاقات الهوتسبوت</p>
                    <h1 class="text-4xl font-black text-slate-950">المخزون والباقات</h1>
                </div>
                @if($network)
                    <a class="btn btn-glass" href="{{ route('store.network', $network->slug) }}">
                        <i data-lucide="external-link"></i>
                        عرض المتجر
                    </a>
                @endif
            </div>

            @if($networks->isEmpty())
                <div class="glass-panel p-8 text-center">
                    <h2 class="text-xl font-black text-slate-950">لا توجد شبكات مرتبطة بهذا الحساب.</h2>
                </div>
            @else
                <div class="mb-5 flex gap-2 overflow-x-auto">
                    @foreach($networks as $item)
                        <a class="network-tab-light {{ $network?->is($item) ? 'network-tab-light-active' : '' }}" href="{{ route('dashboard.inventory.index', ['network_id' => $item->id]) }}">
                            {{ $item->name }}
                        </a>
                    @endforeach
                </div>

                <section class="mb-6 grid gap-4 sm:grid-cols-3">
                    <div class="stat-card stat-mint">
                        <span>المتاح</span>
                        <strong>{{ $totalAvailable }}</strong>
                        <small>بطاقات جاهزة للبيع</small>
                    </div>
                    <div class="stat-card stat-sun">
                        <span>المستعملة</span>
                        <strong>{{ $totalSold }}</strong>
                        <small>بطاقات سُلّمت بعد الدفع</small>
                    </div>
                    <div class="stat-card stat-lilac">
                        <span>إجمالي المخزون</span>
                        <strong>{{ $totalCards }}</strong>
                        <small>داخل {{ $packages->count() }} باقة</small>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[1fr_410px]">
                    <div class="min-w-0 space-y-6">
                        <div class="tool-panel overflow-hidden p-0">
                            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                                <div>
                                    <p class="text-xs font-black text-violet-600">إدارة البيع</p>
                                    <h2 class="text-xl font-black text-slate-950">الباقات</h2>
                                </div>
                                <span class="badge">{{ $packages->where('active', true)->count() }} فعالة</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>الباقة</th>
                                            <th>المدة</th>
                                            <th>السعر</th>
                                            <th>المتاح</th>
                                            <th>المستعمل</th>
                                            <th>الحالة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($packages as $package)
                                            <tr>
                                                <td class="font-black">{{ $package->name }}</td>
                                                <td>{{ $package->duration_hours }} ساعة</td>
                                                <td>{{ number_format($package->price, 2) }} NIS</td>
                                                <td>{{ $package->available_cards_count }}</td>
                                                <td>{{ $package->sold_cards_count }}</td>
                                                <td><span class="badge">{{ $package->active ? 'فعالة' : 'مخفية' }}</span></td>
                                                <td>
                                                    <details class="package-details">
                                                        <summary>تعديل</summary>
                                                        <form action="{{ route('dashboard.packages.update', $package) }}" method="POST" class="mt-3 grid min-w-72 gap-3">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input name="name" value="{{ $package->name }}" required>
                                                            <div class="grid grid-cols-2 gap-2">
                                                                <input name="duration_hours" type="number" min="1" value="{{ $package->duration_hours }}" required>
                                                                <input name="price" type="number" step="0.01" min="0" value="{{ $package->price }}" required>
                                                            </div>
                                                            <input name="speed" value="{{ $package->speed }}">
                                                            <textarea name="description" rows="2">{{ $package->description }}</textarea>
                                                            <label class="flex items-center gap-2 text-xs font-black text-slate-600">
                                                                <input type="checkbox" name="active" value="1" @checked($package->active)>
                                                                فعالة في المتجر
                                                            </label>
                                                            <button class="btn btn-primary" type="submit">حفظ</button>
                                                        </form>
                                                        <form action="{{ route('dashboard.packages.destroy', $package) }}" method="POST" class="mt-2">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-danger w-full" type="submit">حذف أو إخفاء</button>
                                                        </form>
                                                    </details>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-slate-500">أضف باقة للبدء.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tool-panel overflow-hidden p-0">
                            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                                <div>
                                    <p class="text-xs font-black text-violet-600">البطاقات الفعلية</p>
                                    <h2 class="text-xl font-black text-slate-950">جدول البطاقات</h2>
                                </div>
                                <span class="badge">آخر {{ $cards->count() }} بطاقة</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>رقم البطاقة / Username</th>
                                            <th>كلمة السر</th>
                                            <th>الباقة</th>
                                            <th>نص الملف</th>
                                            <th>الحالة</th>
                                            <th>الطلب</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($cards as $card)
                                            <tr>
                                                <td dir="ltr" class="font-black">{{ $card->card_code }}</td>
                                                <td dir="ltr">{{ $card->card_password }}</td>
                                                <td>{{ $card->package?->name }}</td>
                                                <td dir="ltr">{{ $card->package_label ?: '—' }}</td>
                                                <td>
                                                    @if($card->status === 'sold')
                                                        <span class="badge badge-sold">مستعملة</span>
                                                    @else
                                                        <span class="badge badge-available">متاحة</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($card->used_order_number)
                                                        <span class="font-black">{{ $card->used_order_number }}</span>
                                                        <span class="block text-xs text-slate-500">{{ $card->used_customer_name }}</span>
                                                    @else
                                                        <span class="text-slate-400">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-slate-500">لم يتم رفع بطاقات بعد.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <form action="{{ route('dashboard.packages.store') }}" method="POST" class="glass-panel space-y-4 p-5">
                            @csrf
                            <input type="hidden" name="network_id" value="{{ $network->id }}">
                            <div class="flex items-center gap-3">
                                <span class="icon-chip"><i data-lucide="plus"></i></span>
                                <h2 class="text-xl font-black text-slate-950">إضافة باقة</h2>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="field">
                                    <span>اسم الباقة</span>
                                    <input name="name" placeholder="بطاقة 2 شيكل" required>
                                </label>
                                <label class="field">
                                    <span>المدة بالساعات</span>
                                    <input name="duration_hours" type="number" min="1" required>
                                </label>
                                <label class="field">
                                    <span>السعر</span>
                                    <input name="price" type="number" step="0.01" min="0" required>
                                </label>
                                <label class="field">
                                    <span>السرعة</span>
                                    <input name="speed" placeholder="3 ميجا">
                                </label>
                            </div>
                            <label class="field">
                                <span>الوصف</span>
                                <textarea name="description" rows="3"></textarea>
                            </label>
                            <label class="flex items-center gap-2 text-sm font-black text-slate-600">
                                <input type="checkbox" name="active" value="1" checked>
                                فعالة في المتجر
                            </label>
                            <button class="btn btn-primary" type="submit">حفظ الباقة</button>
                        </form>

                        <div class="glass-panel p-5">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xl font-black text-slate-950">سجل الاستيراد</h2>
                                <span class="icon-chip"><i data-lucide="history"></i></span>
                            </div>
                            <div class="mt-4 space-y-3">
                                @forelse($imports as $import)
                                    <div class="summary-line">
                                        <div>
                                            <strong>{{ $import->file_name }}</strong>
                                            <span>{{ $import->package?->name }} · {{ $import->created_at->format('Y-m-d H:i') }}</span>
                                        </div>
                                        <div class="text-left text-xs font-black text-slate-500">
                                            <div>نجح: {{ $import->imported_count }}</div>
                                            <div>مكرر: {{ $import->duplicate_count }} · فشل: {{ $import->failed_count }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">لا توجد عمليات استيراد بعد.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <aside class="space-y-6">
                        <form action="{{ route('dashboard.cards.import') }}" method="POST" enctype="multipart/form-data" class="upload-panel space-y-4 p-5">
                            @csrf
                            <input type="hidden" name="network_id" value="{{ $network->id }}">
                            <div class="flex items-start gap-3">
                                <span class="icon-chip"><i data-lucide="file-spreadsheet"></i></span>
                                <div>
                                    <p class="text-xs font-black text-violet-600">صاحب الشبكة</p>
                                    <h2 class="text-2xl font-black text-slate-950">رفع بطاقات الشبكة</h2>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">
                                        اختر الباقة من النظام، ثم ارفع ملف Excel. البرنامج يقرأ كل بطاقة من 3 صفوف:
                                        <strong>Username</strong>
                                        ثم
                                        <strong>Password</strong>
                                        ثم
                                        <strong>Package</strong>.
                                    </p>
                                </div>
                            </div>
                            <label class="field">
                                <span>الباقة التي ستباع في المتجر</span>
                                <select name="package_id" required>
                                    @foreach($packages->where('active', true) as $package)
                                        <option value="{{ $package->id }}">{{ $package->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="field upload-drop">
                                <span>ملف Excel أو CSV</span>
                                <input name="file" type="file" accept=".xlsx,.xls,.csv" required>
                            </label>
                            <div class="upload-drop text-xs font-bold leading-6 text-slate-600">
                                مثال بطاقة واحدة داخل الملف:
                                <div dir="ltr" class="mt-2 grid grid-cols-[90px_1fr] overflow-hidden rounded-lg border border-slate-200 bg-white font-mono text-slate-900">
                                    <span class="border-b border-r border-slate-200 px-2 py-1">Username</span>
                                    <span class="border-b border-slate-200 px-2 py-1">777742667150</span>
                                    <span class="border-b border-r border-slate-200 px-2 py-1">Password</span>
                                    <span class="border-b border-slate-200 px-2 py-1">734781</span>
                                    <span class="border-r border-slate-200 px-2 py-1">Package</span>
                                    <span class="px-2 py-1">10 3 2</span>
                                </div>
                                <p class="mt-2 text-slate-500">يمكن تكرار نفس الشكل أفقيًا: A/B ثم C/D ثم E/F.</p>
                            </div>
                            <button class="btn btn-primary w-full" type="submit">
                                <i data-lucide="upload"></i>
                                استيراد البطاقات
                            </button>

                            @if(session('import_errors'))
                                <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                                    @foreach(session('import_errors') as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </form>

                        <form action="{{ route('dashboard.payment.update') }}" method="POST" class="glass-panel space-y-4 p-5">
                            @csrf
                            <input type="hidden" name="network_id" value="{{ $network->id }}">
                            <div class="flex items-center gap-3">
                                <span class="icon-chip"><i data-lucide="wallet-cards"></i></span>
                                <h2 class="text-xl font-black text-slate-950">طرق الدفع</h2>
                            </div>
                            <label class="field">
                                <span>رقم Jawwal Pay</span>
                                <input name="wallet_number" value="{{ old('wallet_number', $network->wallet_number) }}">
                            </label>
                            <label class="field">
                                <span>حساب Bank of Palestine</span>
                                <input name="bank_account" value="{{ old('bank_account', $network->bank_account) }}">
                            </label>
                            <label class="field">
                                <span>تفاصيل التحويل البنكي</span>
                                <textarea name="bank_transfer_details" rows="3">{{ old('bank_transfer_details', $network->bank_transfer_details) }}</textarea>
                            </label>
                            <label class="field">
                                <span>وصف المتجر</span>
                                <textarea name="description" rows="4">{{ old('description', $network->description) }}</textarea>
                            </label>
                            <button class="btn btn-primary w-full" type="submit">تحديث</button>
                        </form>
                    </aside>
                </section>
            @endif
        </div>
    </main>
@endsection
