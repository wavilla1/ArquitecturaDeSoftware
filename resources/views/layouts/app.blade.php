<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b0718">
    <title>@yield('title', 'Monoverse') · Marketplace NFT</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>

    <header class="site-header">
        <div class="container nav-wrap">
            <a class="brand" href="{{ route('marketplace') }}" aria-label="Inicio de Monoverse">
                <span class="brand-mark">M</span>
                <span>MONOVERSE</span>
            </a>

            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation">
                <span></span><span></span><span></span>
                <span class="sr-only">Abrir menú</span>
            </button>

            <div class="nav-panel" id="primary-navigation">
                <nav class="nav-links" aria-label="Navegación principal">
                    <a class="{{ request()->routeIs('marketplace') ? 'active' : '' }}" href="{{ route('marketplace') }}">Mercado</a>
                    <a class="{{ request()->routeIs('mint*') ? 'active' : '' }}" href="{{ route('mint') }}">Acuñar</a>
                    <a class="{{ request()->routeIs('profile') ? 'active' : '' }}" href="{{ route('profile') }}">Mi inventario</a>
                    <a class="{{ request()->routeIs('roadmap') ? 'active' : '' }}" href="{{ route('roadmap') }}">Pendientes</a>
                </nav>

                <form class="demo-user" action="{{ route('demo.user') }}" method="POST">
                    @csrf
                    <span class="avatar" style="--avatar: {{ $activeUser->accent }}">{{ mb_strtoupper(mb_substr($activeUser->name, 0, 1)) }}</span>
                    <label>
                        <span class="sr-only">Usuario de demostración</span>
                        <select name="user_id" onchange="this.form.submit()" aria-label="Cambiar usuario de demostración">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected($user->id === $activeUser->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <strong>{{ number_format((float) $activeUser->balance, 2) }} MONO</strong>
                </form>
            </div>
        </div>
    </header>

    @if (session('success'))
        <div class="container toast" role="status">
            <span>✓</span>{{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="container alert" role="alert">
            <strong>Revisa la información:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container footer-row">
            <div>
                <span class="brand footer-brand"><span class="brand-mark">M</span> MONOVERSE</span>
                <p>MVP académico · Arquitectura cliente-servidor con Laravel.</p>
            </div>
            <p>Hecho por José Luis, Juan José y William Alberto.</p>
        </div>
    </footer>
</body>
</html>
