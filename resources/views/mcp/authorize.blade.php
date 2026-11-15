<!DOCTYPE html>
<html lang="ar" dir="rtl" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- نفس منطق الوضع الليلي اللي في باقي النظام --}}
    <script>
        (function() {
            const appearance = '{{ $appearance ?? "system" }}';

            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>

    <style>
        html { background-color: oklch(1 0 0); }
        html.dark { background-color: oklch(0.145 0 0); }
    </style>

    <title>السماح بالوصول — {{ config('app.name', 'MCP Server') }}</title>

    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />

    @vite(['resources/css/app.css'])
</head>
<body class="bg-background font-sans text-foreground antialiased">
<div class="flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="rounded-xl border border-border bg-card text-card-foreground shadow-soft">
            <div class="flex flex-col space-y-1.5 p-6">
                <div class="mb-4 flex items-center justify-center">
                    @if ($client->logo_uri ?? null)
                        <img src="{{ $client->logo_uri }}" alt="{{ $client->name }}" class="size-12 rounded object-contain">
                    @else
                        <svg class="size-12 text-primary" stroke="currentColor" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    @endif
                </div>

                <h1 class="text-center text-2xl leading-none font-semibold tracking-tight">
                    تسمح لـ «{{ $client->name }}» بالوصول؟
                </h1>

                <p class="pt-2 text-center text-sm text-muted-foreground">
                    التطبيق ده هيقدر يقرا بيانات النظام من خلال أدوات الـ MCP:
                    المخزون، أرصدة التجار، المبيعات، والفواتير.
                    <span class="font-medium text-foreground">قراءة بس — مش هيقدر يعدّل أو يحذف أي حاجة.</span>
                </p>

                @php($redirectUri = request()->query('redirect_uri', $client->redirect_uris[0] ?? null))
                @if ($redirectUri)
                    <p class="pt-1 text-center text-xs break-all text-muted-foreground" dir="ltr">
                        {{ $redirectUri }}
                    </p>
                @endif

                @if ($client->client_uri ?? null)
                    <a href="{{ $client->client_uri }}" target="_blank" rel="noopener noreferrer" class="text-center text-sm text-primary underline" dir="ltr">
                        {{ $client->client_uri }}
                    </a>
                @endif
            </div>

            <div class="space-y-4 p-6 pt-0">
                <div class="rounded-lg border border-border bg-muted/50 p-4">
                    <p class="mb-2 text-sm text-muted-foreground">داخل باسم:</p>
                    <p class="font-medium" dir="ltr">{{ $user->email }}</p>
                </div>

                @if(count($scopes) > 0)
                    <div class="space-y-2">
                        <p class="text-sm font-medium">الصلاحيات المطلوبة:</p>

                        <ul class="space-y-2">
                            @foreach($scopes as $scope)
                                <li class="flex items-start gap-2">
                                    <div class="mt-0.5 rounded-full bg-primary/10 p-1">
                                        <div class="size-1.5 rounded-full bg-primary"></div>
                                    </div>
                                    <span class="text-sm text-muted-foreground">
                                        استخدام أدوات الـ MCP للقراءة
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-3 p-6 pt-0">
                <form method="POST" action="{{ route('passport.authorizations.deny') }}" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="state" value="">
                    <input type="hidden" name="client_id" value="{{ $client->id }}">
                    <input type="hidden" name="auth_token" value="{{ $authToken }}">
                    <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none">
                        <svg class="me-2 size-4" stroke="currentColor" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        رفض
                    </button>
                </form>

                <form method="POST" action="{{ route('passport.authorizations.approve') }}" class="flex-1" id="authorizeForm">
                    @csrf
                    <input type="hidden" name="state" value="">
                    <input type="hidden" name="client_id" value="{{ $client->id }}">
                    <input type="hidden" name="auth_token" value="{{ $authToken }}">
                    <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium whitespace-nowrap text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50" id="authorizeButton">
                        <svg id="loadingSpinner" class="me-3 hidden size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span id="authorizeText">اسمح بالوصول</span>
                    </button>
                </form>
            </div>
        </div>

        <p class="mt-4 text-center text-xs text-muted-foreground">
            {{ config('app.name') }}
        </p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('authorizeForm');
        const button = document.getElementById('authorizeButton');
        const authorizeText = document.getElementById('authorizeText');
        const loadingSpinner = document.getElementById('loadingSpinner');

        form.addEventListener('submit', function(e) {
            button.disabled = true;
            authorizeText.textContent = 'جاري السماح...';
            loadingSpinner.classList.remove('hidden');

            // بعد الإرسال بنستنى التحويل وبنقفل النافذة لو كانت منبثقة
            setTimeout(function() {
                const checkRedirect = setInterval(function() {
                    if (!window.location.href.includes('/oauth/authorize') ||
                        window.location.search.includes('code=') ||
                        window.location.search.includes('error=')) {
                        clearInterval(checkRedirect);
                        window.close();
                    }
                }, 100);

                setTimeout(function() {
                    clearInterval(checkRedirect);
                    window.close();
                }, 5000);
            }, 200);
        });

        const cancelForm = document.querySelector('form[method="POST"]:has(input[name="_method"][value="DELETE"])');
        if (cancelForm) {
            cancelForm.addEventListener('submit', function(e) {
                setTimeout(function() {
                    window.close();
                }, 200);
            });
        }
    });
</script>
</body>
</html>
