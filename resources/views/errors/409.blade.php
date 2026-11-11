@extends('layouts.app')
@php($activeUser = auth()->user())
@section('title', __('La publicación cambió'))
@section('content')
<section class="page-hero container"><h1>{{ __('La publicación cambió') }}</h1><p>{{ $exception->getMessage() ? __($exception->getMessage()) : __('Actualiza el mercado antes de intentar de nuevo.') }}</p><a class="button button-primary" href="{{ route('marketplace') }}">{{ __('catalog.back') }}</a></section>
@endsection
