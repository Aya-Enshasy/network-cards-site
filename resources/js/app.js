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
    Copy,
    CreditCard,
    ExternalLink,
    FileSpreadsheet,
    History,
    Image,
    KeyRound,
    LayoutDashboard,
    LogIn,
    LogOut,
    Network,
    PackageOpen,
    Plus,
    ReceiptText,
    Rows3,
    Save,
    Search,
    SearchCheck,
    ShoppingBag,
    Sparkles,
    Store,
    Ticket,
    TicketCheck,
    TrendingUp,
    Upload,
    UploadCloud,
    UserRound,
    WalletCards,
    Wifi,
    WifiOff,
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
            empty.className = 'empty-cart';
            empty.textContent = 'اختر عدد البطاقات من الباقات المتاحة.';
            summaryItems.append(empty);
        } else {
            selected.forEach((item) => {
                const row = document.createElement('div');
                row.className = 'summary-line summary-line-compact';

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

async function copyToClipboard(text) {
    if (navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(text);
            return;
        } catch {
            // Fall back for non-secure deployments where Clipboard API is blocked.
        }
    }

    const input = document.createElement('textarea');
    input.value = text;
    input.setAttribute('readonly', '');
    input.style.position = 'fixed';
    input.style.inset = '0 auto auto 0';
    input.style.opacity = '0';
    document.body.append(input);
    input.select();
    document.execCommand('copy');
    input.remove();
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

        await copyToClipboard(text);
        showToast(button.dataset.copyLabel || 'تم النسخ بنجاح');
    });
}

function playNotificationTone() {
    const AudioContext = window.AudioContext || window.webkitAudioContext;

    if (!AudioContext) {
        return;
    }

    const context = new AudioContext();
    const gain = context.createGain();
    gain.connect(context.destination);
    gain.gain.setValueAtTime(0.0001, context.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.06, context.currentTime + 0.015);
    gain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + 0.28);

    [880, 1174].forEach((frequency, index) => {
        const oscillator = context.createOscillator();
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(frequency, context.currentTime + (index * 0.08));
        oscillator.connect(gain);
        oscillator.start(context.currentTime + (index * 0.08));
        oscillator.stop(context.currentTime + 0.18 + (index * 0.08));
    });

    window.setTimeout(() => context.close(), 420);
}

function setupNotifications() {
    const wrapper = document.querySelector('[data-notifications]');

    if (!wrapper) {
        return;
    }

    const toggle = wrapper.querySelector('[data-notification-toggle]');
    const panel = wrapper.querySelector('[data-notification-panel]');

    if (!toggle || !panel) {
        return;
    }

    const setOpen = (open, withSound = false) => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));

        if (open && withSound) {
            playNotificationTone();
        }
    };

    toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        setOpen(panel.hidden, true);
    });

    panel.addEventListener('click', (event) => event.stopPropagation());

    document.addEventListener('click', () => setOpen(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });
}

function setupCloudinaryReceiptUpload() {
    const form = document.querySelector('[data-cloudinary-receipt-form]');

    if (!form) {
        return;
    }

    const fileInput = form.querySelector('[data-cloudinary-receipt-file]');
    const receiptUrl = form.querySelector('[data-cloudinary-receipt-url]');
    const publicId = form.querySelector('[data-cloudinary-receipt-public-id]');
    const originalName = form.querySelector('[data-cloudinary-receipt-original-name]');
    const status = form.querySelector('[data-cloudinary-receipt-status]');
    const submitButton = form.querySelector('button[type="submit"]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const maxBytes = Number.parseInt(form.dataset.cloudinaryMaxBytes || '0', 10);
    let isUploading = false;

    if (!fileInput || !receiptUrl || !publicId || !form.dataset.cloudinarySignatureUrl) {
        return;
    }

    const setStatus = (message) => {
        if (status) {
            status.textContent = message;
        }
    };

    const setBusy = (busy) => {
        if (!submitButton) {
            return;
        }

        if (!submitButton.dataset.defaultText) {
            submitButton.dataset.defaultText = submitButton.textContent.trim();
        }

        submitButton.disabled = busy;
        submitButton.textContent = busy ? 'جاري رفع الوصل...' : submitButton.dataset.defaultText;
    };

    const clearReceiptReference = () => {
        receiptUrl.value = '';
        publicId.value = '';

        if (originalName) {
            originalName.value = '';
        }
    };

    const formatMegabytes = (bytes) => Math.max(1, Math.floor(bytes / 1024 / 1024));

    const fetchJson = async (url, options = {}) => {
        const response = await fetch(url, options);
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(payload.message || payload.error?.message || 'تعذر رفع صورة الوصل.');
        }

        return payload;
    };

    const uploadReceipt = async (file) => {
        if (!file.type.startsWith('image/')) {
            throw new Error('اختر صورة صالحة لوصل الدفع.');
        }

        if (maxBytes > 0 && file.size > maxBytes) {
            throw new Error(`حجم الصورة يجب ألا يتجاوز ${formatMegabytes(maxBytes)} ميجابايت.`);
        }

        const signature = await fetchJson(form.dataset.cloudinarySignatureUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        if (signature.max_bytes && file.size > signature.max_bytes) {
            throw new Error(`حجم الصورة يجب ألا يتجاوز ${formatMegabytes(signature.max_bytes)} ميجابايت.`);
        }

        const body = new FormData();
        body.append('file', file);
        body.append('api_key', signature.api_key);

        Object.entries(signature.params || {}).forEach(([key, value]) => {
            body.append(key, value);
        });

        const upload = await fetchJson(signature.upload_url, {
            method: 'POST',
            body,
        });

        if (!upload.secure_url || !upload.public_id) {
            throw new Error('تعذر حفظ رابط وصل الدفع.');
        }

        receiptUrl.value = upload.secure_url;
        publicId.value = upload.public_id;

        if (originalName) {
            originalName.value = file.name;
        }
    };

    if (receiptUrl.value && publicId.value) {
        fileInput.required = false;
        setStatus('تم تجهيز صورة الوصل.');
    }

    fileInput.addEventListener('change', () => {
        clearReceiptReference();
        fileInput.required = true;
        setStatus(fileInput.files?.[0]?.name || '');
    });

    form.addEventListener('submit', async (event) => {
        if (receiptUrl.value && publicId.value) {
            fileInput.disabled = true;
            fileInput.required = false;
            return;
        }

        if (isUploading) {
            event.preventDefault();
            return;
        }

        event.preventDefault();

        const file = fileInput.files?.[0];

        if (!file) {
            setStatus('اختر صورة وصل الدفع.');
            fileInput.focus();
            return;
        }

        isUploading = true;
        setBusy(true);
        setStatus('جاري رفع صورة الوصل...');

        try {
            await uploadReceipt(file);
            setStatus('تم رفع صورة الوصل.');
            fileInput.disabled = true;
            fileInput.required = false;
            form.requestSubmit();
        } catch (error) {
            clearReceiptReference();
            setStatus(error.message);
            showToast(error.message);
        } finally {
            isUploading = false;

            if (!receiptUrl.value || !publicId.value) {
                fileInput.disabled = false;
                fileInput.required = true;
                setBusy(false);
            }
        }
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
            Copy,
            CreditCard,
            ExternalLink,
            FileSpreadsheet,
            History,
            Image,
            KeyRound,
            LayoutDashboard,
            LogIn,
            LogOut,
            Network,
            PackageOpen,
            Plus,
            ReceiptText,
            Rows3,
            Save,
            Search,
            SearchCheck,
            ShoppingBag,
            Sparkles,
            Store,
            Ticket,
            TicketCheck,
            TrendingUp,
            Upload,
            UploadCloud,
            UserRound,
            WalletCards,
            Wifi,
            WifiOff,
        },
    });
}

function boot() {
    setupCart();
    setupOrderAccess();
    setupCopyActions();
    setupNotifications();
    setupCloudinaryReceiptUpload();
    setupIcons();
    Alpine.start();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
