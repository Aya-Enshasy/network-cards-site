<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\Order;
use App\Services\CloudinaryReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class OrderController extends Controller
{
    public function receiptSignature(Network $network, CloudinaryReceiptService $cloudinary): JsonResponse
    {
        abort_if($network->status !== 'active', 404);

        if (session("checkout.{$network->id}", []) === []) {
            return response()->json([
                'message' => 'اختر البطاقات أولا قبل رفع وصل الدفع.',
            ], 409);
        }

        try {
            return response()->json($cloudinary->signedUploadPayload($network));
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'تعذر تجهيز رفع وصل الدفع. تأكد من إعدادات Cloudinary.',
            ], 422);
        }
    }

    public function store(Request $request, Network $network, CloudinaryReceiptService $cloudinary): RedirectResponse
    {
        abort_if($network->status !== 'active', 404);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt_url' => ['required', 'url', 'max:2048'],
            'receipt_public_id' => ['required', 'string', 'max:255'],
            'receipt_original_name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $validReceiptReference = $cloudinary->isValidReceiptReference($network, $data['receipt_url'], $data['receipt_public_id']);
        } catch (Throwable $exception) {
            report($exception);
            $validReceiptReference = false;
        }

        if (! $validReceiptReference) {
            throw ValidationException::withMessages([
                'receipt_url' => 'تعذر تأكيد صورة وصل الدفع. أعد اختيار الصورة وحاول مرة أخرى.',
            ]);
        }

        $cart = session("checkout.{$network->id}", []);

        if ($cart === []) {
            return redirect()->route('store.network', $network->slug)->with('error', 'انتهت جلسة الطلب، اختر البطاقات مرة أخرى.');
        }

        $packages = $network->activePackages()
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        if ($packages->isEmpty()) {
            return redirect()->route('store.network', $network->slug)->with('error', 'الباقات المختارة غير متاحة حاليًا.');
        }

        $order = DB::transaction(function () use ($network, $packages, $cart, $data) {
            $total = 0;

            foreach ($cart as $packageId => $quantity) {
                $package = $packages->get((int) $packageId);

                if ($package) {
                    $total += $package->price * $quantity;
                }
            }

            $order = Order::create([
                'order_number' => $this->newOrderNumber($network),
                'access_token' => $this->newAccessToken(),
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'notes' => $data['notes'] ?? null,
                'network_id' => $network->id,
                'total_amount' => $total,
                'payment_status' => 'pending',
                'order_status' => 'pending',
            ]);

            foreach ($cart as $packageId => $quantity) {
                $package = $packages->get((int) $packageId);

                if (! $package) {
                    continue;
                }

                $order->items()->create([
                    'package_id' => $package->id,
                    'quantity' => $quantity,
                    'price' => $package->price,
                    'subtotal' => $package->price * $quantity,
                ]);
            }

            $order->receipt()->create([
                'image' => $data['receipt_url'],
                'image_public_id' => $data['receipt_public_id'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
            ]);

            return $order;
        });

        session()->forget("checkout.{$network->id}");

        return redirect()
            ->to($this->signedOrderUrl($order))
            ->with('new_order_token', $order->access_token)
            ->with('new_order_url', $this->signedOrderUrl($order))
            ->with('success', 'تم إنشاء الطلب بنجاح. احتفظ برقم الطلب للمتابعة.');
    }

    public function recoverForm(): View
    {
        return view('orders.track');
    }

    public function recover(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'order_number' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        if (blank($data['order_number'] ?? null) && blank($data['phone'] ?? null)) {
            return back()->withErrors(['search' => 'أدخل رقم الطلب أو رقم الجوال لاسترجاع الطلبات.'])->withInput();
        }

        $orders = Order::query()
            ->with('network')
            ->when(filled($data['order_number'] ?? null), fn ($query) => $query->where('order_number', trim($data['order_number'])))
            ->when(filled($data['phone'] ?? null), fn ($query) => $query->orWhere('phone', trim($data['phone'])))
            ->latest()
            ->limit(20)
            ->get();

        $orderLinks = $orders->mapWithKeys(fn (Order $order) => [
            $order->id => $this->signedOrderUrl($order),
        ]);

        return view('orders.recover-results', compact('orders', 'orderLinks'));
    }

    public function show(Order $order): View
    {
        $order->load(['network', 'items.package', 'receipt', 'deliveredCards.package']);

        return view('orders.show', [
            'order' => $order,
            'signedOrderUrl' => $this->signedOrderUrl($order),
        ]);
    }

    private function newOrderNumber(Network $network): string
    {
        $year = now()->year;
        $next = Order::where('order_number', 'like', "ORD-{$year}-%")->count() + 1;

        do {
            $number = sprintf('ORD-%s-%06d', $year, $next);
            $next++;
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    private function newAccessToken(): string
    {
        do {
            $token = Str::upper(Str::random(48));
        } while (Order::where('access_token', $token)->exists());

        return $token;
    }

    private function signedOrderUrl(Order $order): string
    {
        return URL::signedRoute('orders.show', $order->access_token, null, false);
    }
}
