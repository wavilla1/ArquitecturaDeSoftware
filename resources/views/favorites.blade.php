@extends('layouts.app')
@section('title', __('Mis favoritos'))
@section('content')
<section class="page-hero container"><span class="eyebrow">{{ __('Tu selección') }}</span><h1>{{ __('Mis') }} <span>{{ __('favoritos.') }}</span></h1><p>{{ __('Guarda piezas y colecciones que quieras volver a encontrar.') }}</p></section>
<section class="container">
    <h2 class="favorite-heading">NFTs</h2>
    <div class="roadmap-grid">
        @forelse ($nfts as $nft)
            <article class="task-card"><h3>{{ $nft->collection->name }} #{{ $nft->token_number }}</h3><p>{{ $nft->collection->description }}</p>
                <form method="POST" action="{{ route('favorites.nft', $nft) }}">@csrf<input type="hidden" name="saved" value="0"><button class="button button-secondary">{{ __('Quitar NFT de favoritos') }}</button></form>
            </article>
        @empty <p>{{ __('No has guardado NFTs todavía.') }}</p> @endforelse
    </div>
    <h2 class="favorite-heading">{{ __('Colecciones') }}</h2>
    <div class="roadmap-grid">
        @forelse ($collections as $collection)
            <article class="task-card"><h3>{{ $collection->name }}</h3><p>{{ $collection->description }}</p>
                <form method="POST" action="{{ route('favorites.collection', $collection) }}">@csrf<input type="hidden" name="saved" value="0"><button class="button button-secondary">{{ __('Quitar colección de favoritos') }}</button></form>
            </article>
        @empty <p>{{ __('No has guardado colecciones todavía.') }}</p> @endforelse
    </div>
    <p class="pagination-wrap"><a class="button button-primary" href="{{ route('marketplace') }}#explorar">{{ __('Explorar mercado') }}</a></p>
</section>
@endsection
