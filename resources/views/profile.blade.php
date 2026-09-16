@extends('layouts.app')

@section('title', 'Mi inventario')

@section('content')
    <section class="profile-hero container">
        <span class="profile-avatar" style="--avatar: {{ $activeUser->accent }}">{{ mb_strtoupper(mb_substr($activeUser->name, 0, 1)) }}</span>
        <div><span class="eyebrow">Perfil de demostración</span><h1>{{ $activeUser->name }}</h1><p>{{ '@'.$activeUser->handle }} · {{ $activeUser->email }}</p></div>
        <div class="balance-card"><span>Saldo disponible</span><strong>{{ number_format((float) $activeUser->balance, 2) }}</strong><small>MONO</small></div>
    </section>

    <section class="section container">
        <div class="section-heading"><div><span class="eyebrow">Propiedad digital</span><h2>Mi inventario</h2></div><p>{{ $inventory->count() }} {{ $inventory->count() === 1 ? 'pieza' : 'piezas' }} en esta cuenta.</p></div>
        <div class="inventory-grid">
            @forelse ($inventory as $nft)
                <article class="inventory-card">
                    <div class="inventory-art nft-art" style="--art-start: {{ $nft->collection->palette_from }}; --art-end: {{ $nft->collection->palette_to }}">
                        <span class="nft-edition">#{{ str_pad($nft->token_number, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="nft-monogram">{{ mb_strtoupper(mb_substr($nft->collection->name, 0, 1)) }}</span>
                        <div class="art-ring"></div>
                    </div>
                    <div class="inventory-copy">
                        <span>{{ $nft->collection->name }}</span><h3>{{ $nft->collection->name }} #{{ $nft->token_number }}</h3>
                        <code title="{{ $nft->token_hash }}">{{ substr($nft->token_hash, 0, 14) }}…</code>
                        @if ($nft->activeListing)
                            <div class="listed-badge">En venta por {{ number_format((float) $nft->activeListing->price, 2) }} MONO</div>
                        @else
                            <form class="sell-form" action="{{ route('nfts.sell', $nft) }}" method="POST">
                                @csrf
                                <label><span>Precio</span><input name="price" type="number" min="0.10" step="0.01" value="{{ number_format($nft->collection->suggestedPrice(), 2, '.', '') }}" required></label>
                                <button class="button button-small" type="submit">Publicar</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="empty-state"><strong>Tu inventario está vacío.</strong><p>Compra una pieza en el mercado o acuña una nueva colección.</p></div>
            @endforelse
        </div>
    </section>

    <section class="section container">
        <div class="section-heading"><div><span class="eyebrow">Historial</span><h2>Movimientos recientes</h2></div></div>
        <div class="movement-list">
            @forelse ($movements as $movement)
                <article class="movement-row">
                    <span class="movement-icon">{{ $movement->type === 'sale' ? '↗' : ($movement->type === 'mint' ? '✦' : '◎') }}</span>
                    <div><strong>{{ ucfirst($movement->type === 'sale' ? 'venta' : ($movement->type === 'mint' ? 'acuñación' : 'publicación')) }} · {{ $movement->nft->collection->name }} #{{ $movement->nft->token_number }}</strong><span>{{ $movement->created_at->diffForHumans() }}</span></div>
                    <strong class="movement-amount">{{ $movement->amount > 0 ? number_format((float) $movement->amount, 2).' MONO' : 'Registrado' }}</strong>
                </article>
            @empty
                <div class="empty-state"><p>Aún no hay movimientos para este usuario.</p></div>
            @endforelse
        </div>
    </section>
@endsection
