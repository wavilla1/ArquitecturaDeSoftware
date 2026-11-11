@extends('layouts.app')
@section('title', $register ? __('Crear cuenta') : __('Ingresar'))
@section('content')
<section class="container auth-shell">
    <form class="form-card" method="POST" action="{{ route($register ? 'register.store' : 'login.store') }}">
        @csrf
        <h1>{{ $register ? __('Crea tu cuenta') : __('Vuelve a Monoverse') }}</h1>
        <p>{{ $register ? __('Empieza con 25 MONO virtuales para coleccionar y crear.') : __('Ingresa con tu correo y contraseña.') }}</p>
        @if ($register)
            <label class="field"><span>{{ __('Nombre') }}</span><input name="name" value="{{ old('name') }}" required maxlength="80" autocomplete="name"></label>
            <label class="field"><span>{{ __('Alias (3–24 letras minúsculas, números o _)') }}</span><input name="handle" value="{{ old('handle') }}" required pattern="[a-z0-9_]{3,24}" autocomplete="username"></label>
        @endif
        <label class="field"><span>{{ __('Correo electrónico') }}</span><input name="email" type="email" value="{{ old('email') }}" required autocomplete="email"></label>
        <label class="field"><span>{{ $register ? __('Contraseña (mínimo 10 caracteres)') : __('Contraseña') }}</span><input name="password" type="password" required autocomplete="{{ $register ? 'new-password' : 'current-password' }}"></label>
        @if ($register)
            <label class="field"><span>{{ __('Confirmar contraseña') }}</span><input name="password_confirmation" type="password" required autocomplete="new-password"></label>
        @endif
        <button class="button button-primary button-wide">{{ $register ? __('Crear cuenta') : __('Ingresar') }}</button>
        <p><a href="{{ route($register ? 'login' : 'register') }}">{{ $register ? __('Ya tengo cuenta') : __('Crear una cuenta nueva') }} →</a></p>
    </form>
</section>
@endsection
