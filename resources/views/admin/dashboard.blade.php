@extends('layouts.app')

@section('title', 'Panel de administración')

@section('content')
    <section class="page-hero container">
        <span class="eyebrow">Moderación</span>
        <h1>Panel de administración</h1>
        <p>Reportes del mercado, visibilidad de colecciones y estado de las cuentas. Cada acción de moderación queda registrada como un bloque en la cadena interna.</p>
    </section>

    <section class="section container">
        <div class="section-heading"><div><span class="eyebrow">Reportes</span><h2>Estado del mercado</h2></div></div>
        <div class="metric-grid">
            <div class="metric-card"><span>Usuarios</span><strong>{{ $metrics['users'] }}</strong><small>{{ $metrics['suspended'] }} suspendido(s)</small></div>
            <div class="metric-card"><span>Colecciones</span><strong>{{ $metrics['collections'] }}</strong><small>{{ $metrics['hidden_collections'] }} oculta(s)</small></div>
            <div class="metric-card"><span>NFTs acuñados</span><strong>{{ $metrics['nfts'] }}</strong><small>{{ $metrics['active_listings'] }} en venta</small></div>
            <div class="metric-card"><span>Subastas abiertas</span><strong>{{ $metrics['open_auctions'] }}</strong><small>con pujas en curso</small></div>
            <div class="metric-card"><span>Ventas cerradas</span><strong>{{ $metrics['sales'] }}</strong><small>ticket medio {{ number_format($metrics['average_ticket'], 2) }} MONO</small></div>
            <div class="metric-card"><span>Volumen transado</span><strong>{{ number_format($metrics['volume'], 2) }}</strong><small>MONO</small></div>
            <div class="metric-card"><span>Bloques en cadena</span><strong>{{ $metrics['blocks'] }}</strong><small>eventos registrados</small></div>
            <div class="metric-card"><span>MONO en circulación</span><strong>{{ number_format($balance['total'], 2) }}</strong><small>{{ number_format($balance['escrowed'], 2) }} reservados en pujas</small></div>
        </div>
    </section>

    <section class="section container">
        <div class="section-heading">
            <div><span class="eyebrow">Ranking</span><h2>Colecciones con más volumen</h2></div>
            <p>Suma de las ventas registradas por cada colección.</p>
        </div>
        <div class="block-list">
            @forelse ($topCollections as $index => $collection)
                <article class="block-row">
                    <span class="block-number">{{ $index + 1 }}</span>
                    <div>
                        <strong>{{ $collection->name }}</strong>
                        <span>{{ '@'.$collection->creator->handle }} · {{ $collection->nfts_count }} pieza(s)</span>
                    </div>
                    <strong class="movement-amount">{{ number_format((float) $collection->traded_volume, 2) }} MONO</strong>
                </article>
            @empty
                <div class="empty-state"><p>Todavía no hay ventas registradas.</p></div>
            @endforelse
        </div>
    </section>

    <section class="section container">
        <div class="section-heading">
            <div><span class="eyebrow">Moderación</span><h2>Colecciones</h2></div>
            <p>Al ocultar una colección se retiran sus ventas directas y deja de admitir pujas nuevas.</p>
        </div>
        <div class="admin-table">
            @foreach ($collections as $collection)
                <article class="admin-row">
                    <span class="admin-swatch" style="--art-start: {{ $collection->palette_from }}; --art-end: {{ $collection->palette_to }}"></span>
                    <div>
                        <strong>{{ $collection->name }}</strong>
                        <span>{{ '@'.$collection->creator->handle }} · {{ $collection->nfts_count }}/{{ $collection->total_supply }} acuñados · {{ $collection->listed_nfts_count }} publicado(s)</span>
                    </div>
                    <span class="{{ $collection->isHidden() ? 'pending-note' : 'pending-note status-pill-done' }}">
                        {{ $collection->isHidden() ? 'Oculta' : 'Visible' }}
                    </span>
                    <form action="{{ route('admin.collections.toggle', $collection) }}" method="POST">
                        @csrf
                        <button class="button button-small {{ $collection->isHidden() ? 'button-primary' : 'button-secondary' }}" type="submit">
                            {{ $collection->isHidden() ? 'Restaurar' : 'Ocultar' }}
                        </button>
                    </form>
                </article>
            @endforeach
        </div>
    </section>

    <section class="section container">
        <div class="section-heading">
            <div><span class="eyebrow">Moderación</span><h2>Cuentas</h2></div>
            <p>Una cuenta suspendida conserva sus NFTs y su saldo, pero no puede acuñar, publicar, comprar ni pujar.</p>
        </div>
        <div class="admin-table">
            @foreach ($moderatedUsers as $user)
                <article class="admin-row">
                    <span class="avatar" style="--avatar: {{ $user->accent }}">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <div>
                        <strong>{{ $user->name }} {{ $user->is_admin ? '· admin' : '' }}</strong>
                        <span>{{ '@'.$user->handle }} · {{ $user->nfts_count }} NFT(s) · {{ $user->collections_count }} colección(es) · {{ $user->active_listings_count }} publicación(es) · {{ number_format((float) $user->balance, 2) }} MONO</span>
                    </div>
                    <span class="{{ $user->isSuspended() ? 'pending-note' : 'pending-note status-pill-done' }}">
                        {{ $user->isSuspended() ? 'Suspendido' : 'Activo' }}
                    </span>
                    @if ($user->is_admin)
                        <span class="admin-locked">Sin acciones</span>
                    @else
                        <form action="{{ route('admin.users.toggle', $user) }}" method="POST">
                            @csrf
                            <button class="button button-small {{ $user->isSuspended() ? 'button-primary' : 'button-secondary' }}" type="submit">
                                {{ $user->isSuspended() ? 'Reactivar' : 'Suspender' }}
                            </button>
                        </form>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    <section class="section container chain-preview">
        <div class="section-heading">
            <div><span class="eyebrow">Auditoría</span><h2>Últimos bloques</h2></div>
            <p>Las acciones de moderación aparecen aquí junto a acuñaciones, publicaciones, pujas y ventas.</p>
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
