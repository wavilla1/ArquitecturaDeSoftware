@extends('layouts.app')
@php($activeUser = auth()->user())
@section('title', 'Acceso restringido')
@section('content')
<section class="page-hero container"><h1>Acceso restringido</h1><p>{{ $exception->getMessage() ?: 'No tienes permiso para realizar esta operación.' }}</p><a class="button button-primary" href="{{ route('marketplace') }}">Volver al mercado</a></section>
@endsection
