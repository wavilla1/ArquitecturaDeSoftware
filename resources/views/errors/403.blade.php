@extends('layouts.app')
@php($activeUser = auth()->user())
@section('title', __('Acceso restringido'))
@section('content')
<section class="page-hero container"><h1>{{ __('Acceso restringido') }}</h1><p>{{ $exception->getMessage() ? __($exception->getMessage()) : __('No tienes permiso para realizar esta operación.') }}</p><a class="button button-primary" href="{{ route('marketplace') }}">{{ __('catalog.back') }}</a></section>
@endsection
