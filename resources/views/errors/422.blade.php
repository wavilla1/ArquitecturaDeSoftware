@extends('layouts.app')
@php($activeUser = auth()->user())
@section('title', __('Revisa la operación'))
@section('content')
<section class="page-hero container"><h1>{{ __('Revisa la operación') }}</h1><p>{{ $exception->getMessage() ? __($exception->getMessage()) : __('No se pudo completar esta operación.') }}</p><a class="button button-primary" href="{{ route('marketplace') }}">{{ __('catalog.back') }}</a></section>
@endsection
