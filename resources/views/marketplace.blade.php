@extends('layouts.app')

@section('title', 'Mercado')

@section('content')
    <section class="hero container">
        <div class="hero-copy">
            <span class="eyebrow"><i></i> Mercado digital en movimiento</span>
            <h1>Arte único.<br><span>Valor que evoluciona.</span></h1>
            <p>Descubre, compra y revende piezas digitales con precios guiados por la oferta, la demanda y la escasez.</p>
            <div class="hero-actions">
                <a class="button button-primary" href="#explorar">Explorar NFTs <span>↘</span></a>
                <a class="button button-secondary" href="{{ route('mint') }}">Crear colección</a>
            </div>
        </div>

        <div class="hero-showcase" aria-label="Pieza destacada">
            <div class="orbit orbit-one"></div>
            <div class="orbit orbit-two"></div>
            <div class="featured-art">
                <div class="art-sphere sphere-one"></div>
                <div class="art-sphere sphere-two"></div>
                <span class="featured-label">EDICIÓN / 01</span>
                <strong>NEBULA<br>ECHOES</strong>
            </div>
            <div class="floating-card">
                <span>Precio actual</span>
                <strong>3.20 MONO</strong>
                <small>↑ Precio dinámico</small>
            </div>
        </div>
    </section>

    <section class="stats-bar">
        <div class="container stats-grid">
            <div><strong>{{ $nftCount }}</strong><span>NFTs acuñados</span></div>
            <div><strong>{{ $collectionCount }}</strong><span>Colecciones</span></div>
            <div><strong>{{ number_format((float) $volume, 2) }}</strong><span>Volumen MONO</span></div>
            <div><strong>3</strong><span>Coleccionistas</span></div>
        </div>
    </section>

    <section class="section container" id="explorar">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Mercado abierto</span>
                <h2>Piezas en venta</h2>
            </div>
            <p>Actúas como <strong>{{ '@'.$activeUser->handle }}</strong>. Cambia el usuario desde la barra superior para probar compras entre perfiles.</p>
        </div>

        <div class="nft-grid">
            @forelse ($listings as $listing)
                @php($collection = $listing->nft->collection)
                <article class="nft-card">
                    <div class="nft-art" style="--art-start: {{ $collection->palette_from }}; --art-end: {{ $collection->palette_to }}">
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
                                <span class="auction-badge" title="Subasta: gana la mejor puja">Subasta</span>
                            @else
                                <span class="verified" title="Hash único registrado">✓</span>
                            @endif
                        </div>
                        <div class="owner-row">
                            <span class="mini-avatar" style="--avatar: {{ $listing->seller->accent }}">{{ mb_strtoupper(mb_substr($listing->seller->name, 0, 1)) }}</span>
                            <span>Vende <strong>{{ '@'.$listing->seller->handle }}</strong></span>
                        </div>

                        @if ($listing->isAuction())
                            @php($minimumBid = $listing->minimumNextBid())
                            <div class="price-row">
                                <div><span>{{ $listing->leadingBid ? 'Mejor puja' : 'Puja inicial' }}</span><strong>{{ number_format($listing->leadingBid ? (float) $listing->leadingBid->amount : (float) $listing->price, 2) }} MONO</strong></div>
                                <small>{{ $listing->leadingBid ? 'de @'.$listing->leadingBid->bidder->handle : 'Sin pujas aún' }}</small>
                            </div>
                            <div class="auction-meta">
                                <span>Cierra {{ $listing->closes_at->diffForHumans() }}</span>
                            </div>
                            @if ($listing->seller_id === $activeUser->id)
                                <button class="button button-disabled" disabled>Es tu subasta</button>
                            @elseif ($listing->leadingBid && $listing->leadingBid->bidder_id === $activeUser->id)
                                <button class="button button-disabled" disabled>Vas ganando</button>
                            @elseif ((float) $activeUser->balance < $minimumBid)
                                <button class="button button-disabled" disabled>Saldo insuficiente</button>
                            @else
                                <form class="bid-form" action="{{ route('listings.bid', $listing) }}" method="POST">
                                    @csrf
                                    <input name="amount" type="number" aria-label="Monto de tu puja" min="{{ $minimumBid }}" step="0.01" value="{{ $minimumBid }}" required>
                                    <button class="button button-card" type="submit">Ofertar <span>→</span></button>
                                </form>
                            @endif
                        @else
                            <div class="price-row">
                                <div><span>Precio</span><strong>{{ number_format((float) $listing->price, 2) }} MONO</strong></div>
                                <small>Sugerido {{ number_format($collection->suggestedPrice(), 2) }}</small>
                            </div>
                            @if ($listing->seller_id === $activeUser->id)
                                <button class="button button-disabled" disabled>Es tu publicación</button>
                            @elseif ((float) $activeUser->balance < (float) $listing->price)
                                <button class="button button-disabled" disabled>Saldo insuficiente</button>
                            @else
                                <form action="{{ route('listings.buy', $listing) }}" method="POST" data-confirm="¿Comprar {{ $collection->name }} #{{ $listing->nft->token_number }} por {{ $listing->price }} MONO?">
                                    @csrf
                                    <button class="button button-card" type="submit">Comprar ahora <span>→</span></button>
                                </form>
                            @endif
                        @endif
                    </div>
                </article>
            @empty
                <div class="empty-state"><strong>No hay NFTs publicados.</strong><p>Acuña una colección y publícala para abrir el mercado.</p></div>
            @endforelse
        </div>
    </section>

    <section class="section container chain-preview">
        <div class="section-heading">
            <div><span class="eyebrow">Registro encadenado</span><h2>Actividad reciente</h2></div>
            <span class="pending-note">Verificación pública · pendiente</span>
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
