@extends('layouts.app')
@php($activeUser = auth()->user())
@section('title', 'La publicación cambió')
@section('content')
<section class="page-hero container"><h1>La publicación cambió</h1><p>{{ $exception->getMessage() ?: 'Actualiza el mercado antes de intentar de nuevo.' }}</p><a class="button button-primary" href="{{ route('marketplace') }}">Volver al mercado</a></section>
@endsection
