<style>
    .hacer-admin-auth .fi-simple-main {
        --hacer-ink: #161513;
        --hacer-gold: #8a7a62;
        --hacer-gold-soft: rgb(138 122 98 / 0.18);
        --hacer-cream: #fbf6ec;
        --hacer-paper: #fffcf8;
        --hacer-line: #e6dfd3;
        --hacer-muted: #6b6560;
    }

    .hacer-login-links {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.7rem;
        margin-top: 1.15rem;
    }

    .hacer-login-link {
        color: var(--hacer-gold);
        font-size: 0.92rem;
        font-weight: 500;
        line-height: 1.2;
        text-decoration: none;
        transition: color 0.18s ease;
    }

    .hacer-login-link:hover {
        color: var(--hacer-ink);
    }

    .hacer-login-link--muted {
        color: var(--hacer-muted);
        font-size: 0.86rem;
        font-weight: 400;
    }

    .hacer-login-link--muted:hover {
        color: var(--hacer-gold);
    }

    .hacer-otp-intro {
        display: flex;
        justify-content: center;
        margin-bottom: 0.35rem;
    }

    .hacer-otp-badge {
        display: grid;
        place-items: center;
        width: 3.25rem;
        height: 3.25rem;
        border-radius: 999px;
        color: var(--hacer-gold);
        background: var(--hacer-cream);
        box-shadow: 0 0 0 6px var(--hacer-gold-soft);
        animation: hacer-otp-badge-pulse 2.4s ease-in-out infinite;
    }

    .hacer-otp-badge svg {
        width: 1.4rem;
        height: 1.4rem;
    }

    .hacer-otp-field {
        width: 100%;
        text-align: center;
    }

    .hacer-otp-field .fi-fo-field-label,
    .hacer-otp-field .fi-fo-field-wrp-label,
    .hacer-otp-field label {
        justify-content: center;
        width: 100%;
    }

    .hacer-otp.fi-one-time-code-input-ctn,
    .hacer-otp {
        display: flex;
        justify-content: center;
        width: 100%;
        gap: 0.75rem;
    }

    .hacer-admin-auth .hacer-otp .fi-one-time-code-input-digit {
        width: 3.35rem;
        height: 3.85rem;
        border: 2px solid #d4cbbe !important;
        border-radius: 0.95rem;
        background: #f3ecdd !important;
        color: var(--hacer-ink);
        font-size: 1.45rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-align: center;
        box-shadow: inset 0 1px 0 rgb(255 255 255 / 0.65);
        transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, transform 0.18s ease;
        animation: hacer-otp-enter 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    .hacer-admin-auth .hacer-otp .fi-one-time-code-input-digit:nth-child(1) {
        animation-delay: 0.02s;
    }

    .hacer-admin-auth .hacer-otp .fi-one-time-code-input-digit:nth-child(2) {
        animation-delay: 0.1s;
    }

    .hacer-admin-auth .hacer-otp .fi-one-time-code-input-digit:nth-child(3) {
        animation-delay: 0.18s;
    }

    .hacer-admin-auth .hacer-otp .fi-one-time-code-input-digit:nth-child(4) {
        animation-delay: 0.26s;
    }

    .hacer-admin-auth .hacer-otp .fi-one-time-code-input-digit:hover {
        border-color: rgb(138 122 98 / 0.55);
    }

    .hacer-admin-auth .hacer-otp .fi-one-time-code-input-digit:focus {
        outline: none;
        border-color: var(--hacer-gold) !important;
        background: #fff !important;
        box-shadow: 0 0 0 4px var(--hacer-gold-soft);
        transform: translateY(-2px) scale(1.04);
    }

    .hacer-admin-auth .hacer-otp .fi-one-time-code-input-digit.is-filled {
        border-color: var(--hacer-ink) !important;
        background: var(--hacer-cream) !important;
    }

    .hacer-admin-auth .hacer-otp .fi-one-time-code-input-digit.is-pop {
        animation: hacer-otp-pop 0.34s cubic-bezier(0.22, 1, 0.36, 1);
    }

    .hacer-otp-field .fi-ac,
    .hacer-otp-field [class*='below'] {
        display: flex;
        justify-content: center;
    }

    @keyframes hacer-otp-enter {
        from {
            opacity: 0;
            transform: translateY(12px) scale(0.88);
        }

        to {
            opacity: 1;
            transform: none;
        }
    }

    @keyframes hacer-otp-pop {
        0% {
            transform: scale(0.82);
        }

        58% {
            transform: scale(1.12);
        }

        100% {
            transform: scale(1);
        }
    }

    @keyframes hacer-otp-badge-pulse {
        0%,
        100% {
            box-shadow: 0 0 0 6px var(--hacer-gold-soft);
            transform: scale(1);
        }

        50% {
            box-shadow: 0 0 0 10px rgb(138 122 98 / 0.1);
            transform: scale(1.04);
        }
    }
</style>

<script>
    (() => {
        if (window.__hacerOtpDigitsBound) {
            return;
        }

        window.__hacerOtpDigitsBound = true;

        const markDigit = (input) => {
            if (!(input instanceof HTMLInputElement) || !input.classList.contains('fi-one-time-code-input-digit')) {
                return;
            }

            const hasValue = input.value.trim() !== '';
            input.classList.toggle('is-filled', hasValue);

            if (!hasValue) {
                input.classList.remove('is-pop');
                return;
            }

            input.classList.remove('is-pop');
            void input.offsetWidth;
            input.classList.add('is-pop');
        };

        document.addEventListener('input', (event) => markDigit(event.target), true);
        document.addEventListener('keyup', (event) => markDigit(event.target), true);
    })();
</script>
