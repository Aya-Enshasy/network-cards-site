@extends('layouts.app', ['title' => 'الشبكات'])

@section('content')
    <main class="premium-shell min-h-screen px-4 py-7 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-bold text-emerald-700">المدير العام</p>
                <h1 class="text-3xl font-black">الشبكات</h1>
            </div>
            <a class="btn btn-primary" href="{{ route('admin.networks.create') }}">إضافة شبكة</a>
        </div>

        <section class="tool-panel overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>الشبكة</th>
                            <th>المالك</th>
                            <th>الحالة</th>
                            <th>الطلبات</th>
                            <th>البطاقات</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($networks as $network)
                            <tr>
                                <td class="font-black">{{ $network->name }}</td>
                                <td>{{ $network->owner->name }}</td>
                                <td><span class="badge">{{ $network->status }}</span></td>
                                <td>{{ $network->orders_count }}</td>
                                <td>{{ $network->cards_count }}</td>
                                <td><a class="table-action" href="{{ route('store.network', $network->slug) }}">المتجر</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-slate-500">لا توجد شبكات.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        </div>
    </main>
@endsection
