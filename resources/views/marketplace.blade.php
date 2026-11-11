@extends('layouts.app')

@section('title', __('Mercado'))

@section('content')
    <section class="hero container">
        <div class="hero-copy">
            <span class="eyebrow"><i></i> {{ __('Mercado digital en movimiento') }}</span>
            <h1>{{ __('Arte único.') }}<br><span>{{ __('Valor que evoluciona.') }}</span></h1>
            <p>{{ __('Descubre, compra y revende piezas digitales con precios guiados por la oferta, la demanda y la escasez.') }}</p>
            <div class="hero-actions">
                <a class="button button-primary" href="#explorar">{{ __('Explorar NFTs') }} <span>↘</span></a>
                <a class="button button-secondary" href="{{ route('mint') }}">{{ __('Crear colección') }}</a>
            </div>
        </div>

        <div class="hero-showcase" aria-label="{{ __('Pieza destacada') }}">
            <div class="orbit orbit-one"></div>
            <div class="orbit orbit-two"></div>
            <div class="featured-art">
                <div class="art-sphere sphere-one"></div>
                <div class="art-sphere sphere-two"></div>
                <span class="featured-label">{{ __('EDICIÓN / 01') }}</span>
                <strong>NEBULA<br>ECHOES</strong>
            </div>
            <div class="floating-card">
                <span>{{ __('Precio actual') }}</span>
                <strong>3.20 MONO</strong>
                <small>↑ {{ __('Precio dinámico') }}</small>
            </div>
        </div>
    </section>

    <section class="stats-bar">
        <div class="container stats-grid">
            <div><strong>{{ $nftCount }}</strong><span>{{ __('NFTs acuñados') }}</span></div>
            <div><strong>{{ $collectionCount }}</strong><span>{{ __('Colecciones') }}</span></div>
            <div><strong>{{ number_format((float) $volume, 2) }}</strong><span>{{ __('Volumen MONO') }}</span></div>
            <div><strong>{{ $userCount }}</strong><span>{{ __('Coleccionistas') }}</span></div>
        </div>
    </section>

    @if ($cryptoQuotes !== [])
        <section class="container crypto-ticker" aria-label="Mercado cripto real">
            <div class="crypto-ticker-heading">
                <span class="eyebrow"><i></i> Mercado cripto real</span>
                <small>Precios en USD · fuente: <a href="https://www.coingecko.com" target="_blank" rel="noopener noreferrer">CoinGecko</a>. MONO es saldo virtual y no cotiza.</small>
            </div>
            <div class="crypto-ticker-grid">
                @foreach ($cryptoQuotes as $quote)
                    <div class="crypto-quote">
                        <span>{{ $quote['name'] }} · {{ $quote['symbol'] }}</span>
                        <strong>${{ number_format($quote['price_usd'], 2) }}</strong>
                        @if ($quote['change_24h'] !== null)
                            <small class="{{ $quote['change_24h'] >= 0 ? 'quote-up' : 'quote-down' }}">{{ $quote['change_24h'] >= 0 ? '↑' : '↓' }} {{ number_format(abs($quote['change_24h']), 2) }}% en 24 h</small>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="section container" id="explorar">
        <div class="section-heading">
            <div>
                <span class="eyebrow">{{ __('Mercado abierto') }}</span>
                <h2>{{ __('Piezas en venta') }}</h2>
            </div>
            <p>{{ $activeUser ? __('Colecciona con tu cuenta @:handle.', ['handle' => $activeUser->handle]) : __('Crea una cuenta para comprar, ofertar y guardar tus favoritos.') }} {{ __('Todas las operaciones usan saldo virtual.') }}</p>
        </div>

        <div class="nft-grid">
            @forelse ($listings as $listing)
                @php($collection = $listing->nft->collection)
                <article class="nft-card">
                    <div class="nft-art" style="--art-start: {{ $collection->palette_from }}; --art-end: {{ $collection->palette_to }}">
                        @if ($collection->image_data)<img class="collection-image" src="{{ route('collections.image', $collection) }}" alt="{{ $collection->name }}" loading="lazy">@endif
                        <span class="nft-edition">#{{ str_pad($listing->nft->token_number, 2, '0', STR_PAD_LEFT) }} / {{ $collection->total_supply }}</span>
                        <span class="nft-monogram">{{ mb_strtoupper(mb_substr($collection->name, 0, 1)) }}</span>
                        <div class="art-ring"></div>
                    </div>
                    <div class="nft-body">
                        <div class="nft-title-row">
                            <div>
                                <span>{{ $collection->name }}</span>
                                <h3>{{ $collection->name }} #{{ $listing->nft->token_number }}</h3>
                            </div>
                            @if ($listing->isAuction())
                                <span class="auction-badge" title="{{ __('Subasta: gana la mejor puja') }}">{{ __('Subasta') }}</span>
                            @else
                                <span class="verified" title="{{ __('Hash único registrado') }}">✓</span>
                            @endif
                        </div>
                        <div class="owner-row">
                            <span class="mini-avatar" style="--avatar: {{ $listing->seller->accent }}">{{ mb_strtoupper(mb_substr($listing->seller->name, 0, 1)) }}</span>
                            <span>{{ __('Vende') }} <strong>{{ '@'.$listing->seller->handle }}</strong></span>
                        </div>
                        @if ($activeUser)
                            <div class="favorite-actions">
                                <form action="{{ route('favorites.nft', $listing->nft) }}" method="POST">@csrf<input type="hidden" name="saved" value="{{ in_array($listing->nft_id, $favoriteNftIds) ? 0 : 1 }}"><button type="submit">{{ in_array($listing->nft_id, $favoriteNftIds) ? '♥ '.__('NFT guardado') : '♡ '.__('Guardar NFT') }}</button></form>
                                <form action="{{ route('favorites.collection', $collection) }}" method="POST">@csrf<input type="hidden" name="saved" value="{{ in_array($collection->id, $favoriteCollectionIds) ? 0 : 1 }}"><button type="submit">{{ in_array($collection->id, $favoriteCollectionIds) ? '♥ '.__('Colección') : '♡ '.__('Colección') }}</button></form>
                            </div>
                        @endif

                        @if ($listing->isAuction())
                            @php($minimumBid = $listing->minimumNextBid())
                            <div class="price-row">
                                <div><span>{{ $listing->leadingBid ? __('Mejor puja') : __('Puja inicial') }}</span><strong>{{ number_format($listing->leadingBid ? (float) $listing->leadingBid->amount : (float) $listing->price, 2) }} MONO</strong></div>
                                <small>{{ $listing->leadingBid ? __('de @:handle', ['handle' => $listing->leadingBid->bidder->handle]) : __('Sin pujas aún') }}</small>
                            </div>
                            <div class="auction-meta">
                                <span>{{ __('Cierra :time', ['time' => $listing->closes_at->diffForHumans()]) }}</span>
                            </div>
                            @if (!$activeUser)
                                <a class="button button-card" href="{{ route('login') }}">{{ __('Ingresar para ofertar') }}</a>
                            @elseif ($listing->seller_id === $activeUser->id)
                                <button class="button button-disabled" disabled>{{ __('Es tu subasta') }}</button>
                            @elseif ($listing->leadingBid && $listing->leadingBid->bidder_id === $activeUser->id)
                                <button class="button button-disabled" disabled>{{ __('Vas ganando') }}</button>
                            @elseif ((float) $activeUser->balance < $minimumBid)
                                <button class="button button-disabled" disabled>{{ __('Saldo insuficiente') }}</button>
                            @else
                                <form class="bid-form" action="{{ route('listings.bid', $listing) }}" method="POST">
                                    @csrf
                                    <input name="amount" type="number" aria-label="{{ __('Monto de tu puja') }}" min="{{ $minimumBid }}" step="0.01" value="{{ $minimumBid }}" required>
                                    <button class="button button-card" type="submit">{{ __('Ofertar') }} <span>→</span></button>
                                </form>
                            @endif
                        @else
                            <div class="price-row">
                                <div><span>{{ __('Precio') }}</span><strong>{{ number_format((float) $listing->price, 2) }} MONO</strong></div>
                                <small>{{ __('Sugerido :price', ['price' => number_format($collection->suggestedPrice(), 2)]) }}</small>
                            </div>
                            <p class="favorite-actions"><a href="{{ route('nfts.show', $listing->nft) }}">{{ __('catalog.view') }} →</a></p>
                            @if (!$activeUser)
                                <a class="button button-card" href="{{ route('login') }}">{{ __('catalog.login') }}</a>
                            @elseif ($listing->seller_id === $activeUser->id)
                                <button class="button button-disabled" disabled>{{ __('catalog.own') }}</button>
                            @elseif ((float) $activeUser->balance < (float) $listing->price)
                                <button class="button button-disabled" disabled>{{ __('Saldo insuficiente') }}</button>
                            @else
                                <form action="{{ route('listings.buy', $listing) }}" method="POST" data-confirm="{{ __('catalog.confirm', ['name' => $collection->name.' #'.$listing->nft->token_number, 'price' => $listing->price]) }}">
                                    @csrf
                                    <button class="button button-card" type="submit">{{ __('catalog.buy') }} <span>→</span></button>
                                </form>
                            @endif
                        @endif
                    </div>
                </article>
            @empty
                <div class="empty-state"><strong>{{ __('No hay NFTs publicados.') }}</strong><p>{{ __('Acuña una colección y publícala para abrir el mercado.') }}</p></div>
            @endforelse
        </div>
    </section>

    <section class="section container chain-preview">
        <div class="section-heading">
            <div><span class="eyebrow">{{ __('Registro encadenado') }}</span><h2>{{ __('Actividad reciente') }}</h2></div>
            <a class="button button-secondary" href="{{ route('chain') }}">{{ __('Verificar cadena') }} →</a>
        </div>
        <div class="block-list">
            @foreach ($recentBlocks as $block)
                <article class="block-row">
                    <span class="block-number">{{ str_pad($block->position, 2, '0', STR_PAD_LEFT) }}</span>
                    <div><strong>{{ $block->event }}</strong><span>{{ $block->occurred_at->diffForHumans() }}</span></div>
                    <code>{{ substr($block->current_hash, 0, 12) }}…</code>
                </article>
            @endforeach
        </div>
    </section>
@endsection
