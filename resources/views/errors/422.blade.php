@extends('layouts.app')
@php($activeUser = auth()->user())
@section('title', 'Revisa la operación')
@section('content')
<section class="page-hero container"><h1>Revisa la operación</h1><p>{{ $exception->getMessage() ?: 'No se pudo completar esta operación.' }}</p><a class="button button-primary" href="{{ route('marketplace') }}">Volver al mercado</a></section>
@endsection
