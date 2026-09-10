<nav class="hacer-login-links" aria-label="Giriş yardımcı bağlantıları">
    <a href="{{ filament()->getRequestPasswordResetUrl() }}" class="hacer-login-link">
        Şifremi unuttum
    </a>
    <a href="{{ route('home') }}" class="hacer-login-link hacer-login-link--muted">
        Ana sayfaya dön
    </a>
</nav>
