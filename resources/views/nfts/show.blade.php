@extends('layouts.app')
@section('title', $nft->collection->name.' #'.$nft->token_number)
@section('content')
    @php($collection = $nft->collection)
    @php($listing = $nft->activeListing)
    <section class="page-hero container compact-hero">
        <a href="{{ route('marketplace') }}#explorar">← {{ __('catalog.back') }}</a>
        <p class="eyebrow">{{ __('catalog.direct_sale') }}</p>
        <h1>{{ $collection->name }} <span>#{{ $nft->token_number }}</span></h1>
        <p>{{ $collection->description }}</p>
    </section>
    <section class="container create-layout">
        <article class="nft-card">
            <div class="nft-art" style="--art-start: {{ $collection->palette_from }}; --art-end: {{ $collection->palette_to }}">
                @if ($collection->has_image)
                    <img class="collection-image" src="{{ route('collections.image', $collection) }}" alt="{{ $collection->name }}">
                @endif
                <span class="nft-edition">{{ __('catalog.edition', ['number' => $nft->token_number, 'total' => $collection->total_supply]) }}</span>
                <span class="nft-monogram">{{ mb_strtoupper(mb_substr($collection->name, 0, 1)) }}</span>
                <div class="art-ring"></div>
            </div>
            <div class="nft-body chain-block">
                <h2>{{ __('catalog.hash') }}</h2>
                <code>{{ $nft->token_hash }}</code>
            </div>
        </article>
        <aside class="form-card">
            <p>{{ __('catalog.seller') }}: <strong>{{ '@'.$nft->owner->handle }}</strong></p>
            <div class="price-row"><div><span>{{ __('catalog.price') }}</span><strong>{{ $listing->price }} MONO</strong></div></div>
            <p class="chain-explanation">{{ __('catalog.virtual') }}</p>
            @if (!$activeUser)
                <a class="button button-primary button-wide" href="{{ route('login') }}">{{ __('catalog.login') }}</a>
            @elseif ($listing->seller_id === $activeUser->id)
                <button class="button button-disabled" disabled>{{ __('catalog.own') }}</button>
            @elseif ((float) $activeUser->balance < (float) $listing->price)
                <button class="button button-disabled" disabled>{{ __('catalog.insufficient') }}</button>
            @else
                <form action="{{ route('listings.buy', $listing) }}" method="POST" data-confirm="{{ __('catalog.confirm', ['name' => $collection->name.' #'.$nft->token_number, 'price' => $listing->price]) }}">
                    @csrf
                    <input type="hidden" name="return_to_market" value="1">
                    <button class="button button-primary button-wide" type="submit">{{ __('catalog.buy') }}</button>
                </form>
            @endif
        </aside>
    </section>
@endsection
