@extends('layouts.app', ['title' => 'التقارير'])

@section('content')
    @php
        $maxDaily = max(1, $dailySales->max('amount'));
        $maxMonthly = max(1, $monthlySales->max('amount'));
        $maxPackage = max(1, $topPackages->max('quantity'));
    @endphp

    <main class="premium-shell min-h-screen px-4 py-7 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-6">
                <p class="text-sm font-black text-violet-600">تقارير وتحليلات</p>
                <h1 class="text-4xl font-black text-slate-950">المبيعات</h1>
            </div>

            <section class="grid gap-6 xl:grid-cols-2">
                <div class="tool-panel">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black text-violet-600">آخر 7 أيام</p>
                            <h2 class="text-xl font-black text-slate-950">المبيعات اليومية</h2>
                        </div>
                        <span class="icon-chip"><i data-lucide="trending-up"></i></span>
                    </div>
                    <div class="chart-bars mt-6">
                        @foreach($dailySales as $day)
                            <div class="chart-column">
                                <span class="chart-fill bg-violet-600" style="height: {{ max(6, ($day['amount'] / $maxDaily) * 100) }}%"></span>
                                <small>{{ $day['label'] }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="tool-panel">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black text-violet-600">نظرة شهرية</p>
                            <h2 class="text-xl font-black text-slate-950">المبيعات الشهرية</h2>
                        </div>
                        <span class="icon-chip"><i data-lucide="bar-chart-3"></i></span>
                    </div>
                    <div class="chart-bars mt-6">
                        @foreach($monthlySales as $month)
                            <div class="chart-column">
                                <span class="chart-fill bg-emerald-500" style="height: {{ max(6, ($month['amount'] / $maxMonthly) * 100) }}%"></span>
                                <small>{{ $month['label'] }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="mt-6 tool-panel">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black text-slate-950">أكثر الباقات مبيعًا</h2>
                    <span class="icon-chip"><i data-lucide="award"></i></span>
                </div>
                <div class="mt-5 space-y-4">
                    @forelse($topPackages as $package)
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                                <strong class="text-slate-950">{{ $package['name'] }}</strong>
                                <span class="font-bold text-slate-500">{{ $package['quantity'] }} بطاقة / {{ number_format($package['amount'], 2) }} NIS</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-amber-400" style="width: {{ max(5, ($package['quantity'] / $maxPackage) * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">لا توجد مبيعات مكتملة بعد.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </main>
@endsection
