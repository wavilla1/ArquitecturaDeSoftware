@extends('layouts.app')

@section('title', __('Panel de administración'))

@section('content')
    <section class="page-hero container">
        <span class="eyebrow">{{ __('Moderación') }}</span>
        <h1>{{ __('Panel de administración') }}</h1>
        <p>{{ __('Reportes del mercado, visibilidad de colecciones y estado de las cuentas. Cada acción de moderación queda registrada como un bloque en la cadena interna.') }}</p>
    </section>

    <section class="section container">
        <div class="section-heading"><div><span class="eyebrow">{{ __('Reportes') }}</span><h2>{{ __('Estado del mercado') }}</h2></div></div>
        <div class="metric-grid">
            <div class="metric-card"><span>{{ __('Usuarios') }}</span><strong>{{ $metrics['users'] }}</strong><small>{{ __(':count suspendido(s)', ['count' => $metrics['suspended']]) }}</small></div>
            <div class="metric-card"><span>{{ __('Colecciones') }}</span><strong>{{ $metrics['collections'] }}</strong><small>{{ __(':count oculta(s)', ['count' => $metrics['hidden_collections']]) }}</small></div>
            <div class="metric-card"><span>{{ __('NFTs acuñados') }}</span><strong>{{ $metrics['nfts'] }}</strong><small>{{ __(':count en venta', ['count' => $metrics['active_listings']]) }}</small></div>
            <div class="metric-card"><span>{{ __('Subastas abiertas') }}</span><strong>{{ $metrics['open_auctions'] }}</strong><small>{{ __('con pujas en curso') }}</small></div>
            <div class="metric-card"><span>{{ __('Ventas cerradas') }}</span><strong>{{ $metrics['sales'] }}</strong><small>{{ __('ticket medio :amount MONO', ['amount' => number_format($metrics['average_ticket'], 2)]) }}</small></div>
            <div class="metric-card"><span>{{ __('Volumen transado') }}</span><strong>{{ number_format($metrics['volume'], 2) }}</strong><small>MONO</small></div>
            <div class="metric-card"><span>{{ __('Bloques en cadena') }}</span><strong>{{ $metrics['blocks'] }}</strong><small>{{ __('eventos registrados') }}</small></div>
            <div class="metric-card"><span>{{ __('MONO en circulación') }}</span><strong>{{ number_format($balance['total'], 2) }}</strong><small>{{ __(':amount reservados en pujas', ['amount' => number_format($balance['escrowed'], 2)]) }}</small></div>
        </div>
    </section>

    <section class="section container">
        <div class="section-heading">
            <div><span class="eyebrow">{{ __('Ranking') }}</span><h2>{{ __('Colecciones con más volumen') }}</h2></div>
            <p>{{ __('Suma de las ventas registradas por cada colección.') }}</p>
        </div>
        <div class="block-list">
            @forelse ($topCollections as $index => $collection)
                <article class="block-row">
                    <span class="block-number">{{ $index + 1 }}</span>
                    <div>
                        <strong>{{ $collection->name }}</strong>
                        <span>{{ '@'.$collection->creator->handle }} · {{ __(':count pieza(s)', ['count' => $collection->nfts_count]) }}</span>
                    </div>
                    <strong class="movement-amount">{{ number_format((float) $collection->traded_volume, 2) }} MONO</strong>
                </article>
            @empty
                <div class="empty-state"><p>{{ __('Todavía no hay ventas registradas.') }}</p></div>
            @endforelse
        </div>
    </section>

    <section class="section container">
        <div class="section-heading">
            <div><span class="eyebrow">{{ __('Moderación') }}</span><h2>{{ __('Colecciones') }}</h2></div>
            <p>{{ __('Al ocultar una colección se retiran sus ventas directas y deja de admitir pujas nuevas.') }}</p>
        </div>
        <div class="admin-table">
            @foreach ($collections as $collection)
                <article class="admin-row">
                    <span class="admin-swatch" style="--art-start: {{ $collection->palette_from }}; --art-end: {{ $collection->palette_to }}"></span>
                    <div>
                        <strong>{{ $collection->name }}</strong>
                        <span>{{ '@'.$collection->creator->handle }} · {{ __(':minted/:supply acuñados · :listed publicado(s)', ['minted' => $collection->nfts_count, 'supply' => $collection->total_supply, 'listed' => $collection->listed_nfts_count]) }}</span>
                    </div>
                    <span class="{{ $collection->isHidden() ? 'pending-note' : 'pending-note status-pill-done' }}">
                        {{ $collection->isHidden() ? __('Oculta') : __('Visible') }}
                    </span>
                    <form action="{{ route('admin.collections.toggle', $collection) }}" method="POST">
                        @csrf
                        <button class="button button-small {{ $collection->isHidden() ? 'button-primary' : 'button-secondary' }}" type="submit">
                            {{ $collection->isHidden() ? __('Restaurar') : __('Ocultar') }}
                        </button>
                    </form>
                </article>
            @endforeach
        </div>
    </section>

    <section class="section container">
        <div class="section-heading">
            <div><span class="eyebrow">{{ __('Moderación') }}</span><h2>{{ __('Cuentas') }}</h2></div>
            <p>{{ __('Una cuenta suspendida conserva sus NFTs y su saldo, pero no puede acuñar, publicar, comprar ni pujar.') }}</p>
        </div>
        <div class="admin-table">
            @foreach ($moderatedUsers as $user)
                <article class="admin-row">
                    <span class="avatar" style="--avatar: {{ $user->accent }}">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <div>
                        <strong>{{ $user->name }} {{ $user->is_admin ? '· admin' : '' }}</strong>
                        <span>{{ '@'.$user->handle }} · {{ __(':nfts NFT(s) · :collections colección(es) · :listings publicación(es)', ['nfts' => $user->nfts_count, 'collections' => $user->collections_count, 'listings' => $user->active_listings_count]) }} · {{ number_format((float) $user->balance, 2) }} MONO</span>
                    </div>
                    <span class="{{ $user->isSuspended() ? 'pending-note' : 'pending-note status-pill-done' }}">
                        {{ $user->isSuspended() ? __('Suspendido') : __('Activo') }}
                    </span>
                    @if ($user->is_admin)
                        <span class="admin-locked">{{ __('Sin acciones') }}</span>
                    @else
                        <form action="{{ route('admin.users.toggle', $user) }}" method="POST">
                            @csrf
                            <button class="button button-small {{ $user->isSuspended() ? 'button-primary' : 'button-secondary' }}" type="submit">
                                {{ $user->isSuspended() ? __('Reactivar') : __('Suspender') }}
                            </button>
                        </form>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    <section class="section container chain-preview">
        <div class="section-heading">
            <div><span class="eyebrow">{{ __('Auditoría') }}</span><h2>{{ __('Últimos bloques') }}</h2></div>
            <p>{{ __('Las acciones de moderación aparecen aquí junto a acuñaciones, publicaciones, pujas y ventas.') }}</p>
        </div>
        <div class="block-list">
            @foreach ($recentBlocks as $block)
                <article class="block-row">
                    <span class="block-number">#{{ $block->position }}</span>
                    <div><strong>{{ $block->event }}</strong><span>{{ $block->occurred_at->diffForHumans() }}</span></div>
                    <code title="{{ $block->current_hash }}">{{ substr($block->current_hash, 0, 12) }}…</code>
                </article>
            @endforeach
        </div>
    </section>
@endsection
