@extends('layouts.app')
@section('title', $register ? 'Crear cuenta' : 'Ingresar')
@section('content')
<section class="container auth-shell">
    <form class="form-card" method="POST" action="{{ route($register ? 'register.store' : 'login.store') }}">
        @csrf
        <h1>{{ $register ? 'Crea tu cuenta' : 'Vuelve a Monoverse' }}</h1>
        <p>{{ $register ? 'Empieza con 25 MONO virtuales para coleccionar y crear.' : 'Ingresa con tu correo y contraseña.' }}</p>
        @if ($register)
            <label class="field"><span>Nombre</span><input name="name" value="{{ old('name') }}" required maxlength="80" autocomplete="name"></label>
            <label class="field"><span>Alias (3–24 letras minúsculas, números o _)</span><input name="handle" value="{{ old('handle') }}" required pattern="[a-z0-9_]{3,24}" autocomplete="username"></label>
        @endif
        <label class="field"><span>Correo electrónico</span><input name="email" type="email" value="{{ old('email') }}" required autocomplete="email"></label>
        <label class="field"><span>Contraseña{{ $register ? ' (mínimo 10 caracteres)' : '' }}</span><input name="password" type="password" required autocomplete="{{ $register ? 'new-password' : 'current-password' }}"></label>
        @if ($register)
            <label class="field"><span>Confirmar contraseña</span><input name="password_confirmation" type="password" required autocomplete="new-password"></label>
        @endif
        <button class="button button-primary button-wide">{{ $register ? 'Crear cuenta' : 'Ingresar' }}</button>
        <p><a href="{{ route($register ? 'login' : 'register') }}">{{ $register ? 'Ya tengo cuenta' : 'Crear una cuenta nueva' }} →</a></p>
    </form>
</section>
@endsection
