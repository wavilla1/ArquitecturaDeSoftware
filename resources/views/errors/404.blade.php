@extends('layouts.app')
@php($activeUser = auth()->user())
@section('title', __('catalog.not_found_title'))
@section('content')
<section class="page-hero container">
    <h1>{{ __('catalog.not_found_title') }}</h1>
    <p>{{ __('catalog.not_found') }}</p>
    <a class="button button-primary" href="{{ route('marketplace') }}">{{ __('catalog.back') }}</a>
</section>
@endsection
