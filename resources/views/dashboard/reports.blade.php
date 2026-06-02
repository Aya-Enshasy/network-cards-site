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
            <p class="text-sm font-bold text-emerald-700">تقارير وتحليلات</p>
            <h1 class="text-3xl font-black">المبيعات</h1>
        </div>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="tool-panel">
                <h2 class="text-xl font-black">المبيعات اليومية</h2>
                <div class="chart-bars mt-6">
                    @foreach($dailySales as $day)
                        <div class="chart-column">
                            <span class="chart-fill bg-emerald-700" style="height: {{ max(6, ($day['amount'] / $maxDaily) * 100) }}%"></span>
                            <small>{{ $day['label'] }}</small>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="tool-panel">
                <h2 class="text-xl font-black">المبيعات الشهرية</h2>
                <div class="chart-bars mt-6">
                    @foreach($monthlySales as $month)
                        <div class="chart-column">
                            <span class="chart-fill bg-cyan-700" style="height: {{ max(6, ($month['amount'] / $maxMonthly) * 100) }}%"></span>
                            <small>{{ $month['label'] }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mt-6 tool-panel">
            <h2 class="text-xl font-black">أكثر الباقات مبيعا</h2>
            <div class="mt-5 space-y-3">
                @forelse($topPackages as $package)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <strong>{{ $package['name'] }}</strong>
                            <span>{{ $package['quantity'] }} بطاقة / {{ number_format($package['amount'], 2) }} NIS</span>
                        </div>
                        <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-amber-500" style="width: {{ max(5, ($package['quantity'] / $maxPackage) * 100) }}%"></div>
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
