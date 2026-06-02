import './bootstrap';
import Alpine from 'alpinejs';
import {
    ArrowRight,
    Award,
    BadgeCheck,
    BarChart3,
    Bell,
    Boxes,
    CheckCircle2,
    ExternalLink,
    FileSpreadsheet,
    History,
    Image,
    KeyRound,
    LayoutDashboard,
    LogOut,
    Network,
    Plus,
    ReceiptText,
    Search,
    SearchCheck,
    ShoppingBag,
    TrendingUp,
    Upload,
    UploadCloud,
    UserRound,
    WalletCards,
    Wifi,
    createIcons,
} from 'lucide';

window.Alpine = Alpine;

function setupCart() {
    const form = document.querySelector('[data-cart-form]');

    if (!form) {
        return;
    }

    const inputs = Array.from(form.querySelectorAll('[data-cart-input]'));
    const summaryItems = form.querySelector('[data-summary-items]');
    const summaryCount = form.querySelector('[data-summary-count]');
    const summaryTotal = form.querySelector('[data-summary-total]');
    const formatter = new Intl.NumberFormat('ar-PS', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });

    const clampInput = (input) => {
        const stock = Number.parseInt(input.dataset.stock || '100', 10);
        const rawValue = Number.parseInt(input.value || '0', 10);
        const value = Number.isNaN(rawValue) ? 0 : Math.max(0, Math.min(stock, rawValue));
        input.value = String(value);
        return value;
    };

    const updateSummary = () => {
        const selected = inputs
            .map((input) => {
                const quantity = clampInput(input);
                const price = Number.parseFloat(input.dataset.price || '0');

                return {
                    name: input.dataset.name || '',
                    quantity,
                    subtotal: price * quantity,
                };
            })
            .filter((item) => item.quantity > 0);

        summaryItems.replaceChildren();

        if (selected.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'rounded-lg bg-slate-100 px-3 py-3 text-sm text-slate-500';
            empty.textContent = 'لم يتم اختيار بطاقات بعد.';
            summaryItems.append(empty);
        } else {
            selected.forEach((item) => {
                const row = document.createElement('div');
                row.className = 'flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-sm';

                const name = document.createElement('span');
                name.className = 'font-bold';
                name.textContent = `${item.name} × ${item.quantity}`;

                const subtotal = document.createElement('strong');
                subtotal.textContent = `${formatter.format(item.subtotal)} شيكل`;

                row.append(name, subtotal);
                summaryItems.append(row);
            });
        }

        const totalQuantity = selected.reduce((sum, item) => sum + item.quantity, 0);
        const totalPrice = selected.reduce((sum, item) => sum + item.subtotal, 0);
        summaryCount.textContent = `${formatter.format(totalQuantity)} بطاقة`;
        summaryTotal.textContent = `${formatter.format(totalPrice)} شيكل`;
    };

    form.querySelectorAll('[data-qty-plus], [data-qty-minus]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target);

            if (!input) {
                return;
            }

            const delta = button.hasAttribute('data-qty-plus') ? 1 : -1;
            input.value = String((Number.parseInt(input.value || '0', 10) || 0) + delta);
            updateSummary();
        });
    });

    inputs.forEach((input) => input.addEventListener('input', updateSummary));
    updateSummary();
}

function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast-pop';
    toast.textContent = message;
    document.body.append(toast);
    window.setTimeout(() => toast.remove(), 2400);
}

function setupOrderAccess() {
    const marker = document.querySelector('[data-order-access]');

    if (marker?.dataset.token && marker?.dataset.url) {
        localStorage.setItem('last_order_token', marker.dataset.token);
        localStorage.setItem('last_order_url', marker.dataset.url);
    }

    document.querySelectorAll('[data-last-order-link]').forEach((link) => {
        const savedUrl = localStorage.getItem('last_order_url');

        if (!savedUrl) {
            return;
        }

        link.href = savedUrl;
        link.classList.remove('hidden');
    });
}

function collectCopyLines() {
    const cardLines = Array.from(document.querySelectorAll('[data-copy-line]'))
        .map((node) => node.dataset.copyLine || node.textContent.trim())
        .filter(Boolean);

    if (cardLines.length > 0) {
        return cardLines.join('\n');
    }

    return Array.from(document.querySelectorAll('[data-card-code]'))
        .map((node) => node.textContent.trim())
        .filter(Boolean)
        .join('\n');
}

function setupCopyActions() {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-copy-text], [data-copy-all]');

        if (!button) {
            return;
        }

        const text = button.dataset.copyText || collectCopyLines();

        if (!text) {
            return;
        }

        await navigator.clipboard.writeText(text);
        showToast(button.dataset.copyLabel || 'تم النسخ بنجاح');
    });
}

function setupIcons() {
    createIcons({
        icons: {
            ArrowRight,
            Award,
            BadgeCheck,
            BarChart3,
            Bell,
            Boxes,
            CheckCircle2,
            ExternalLink,
            FileSpreadsheet,
            History,
            Image,
            KeyRound,
            LayoutDashboard,
            LogOut,
            Network,
            Plus,
            ReceiptText,
            Search,
            SearchCheck,
            ShoppingBag,
            TrendingUp,
            Upload,
            UploadCloud,
            UserRound,
            WalletCards,
            Wifi,
        },
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setupCart();
        setupOrderAccess();
        setupCopyActions();
        setupIcons();
        Alpine.start();
    });
} else {
    setupCart();
    setupOrderAccess();
    setupCopyActions();
    setupIcons();
    Alpine.start();
}
