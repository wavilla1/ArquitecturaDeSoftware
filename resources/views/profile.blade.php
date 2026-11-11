@extends('layouts.app')

@section('title', __('Mi inventario'))

@section('content')
    <section class="profile-hero container">
        <span class="profile-avatar" style="--avatar: {{ $activeUser->accent }}">{{ mb_strtoupper(mb_substr($activeUser->name, 0, 1)) }}</span>
        <div><span class="eyebrow">{{ __('Mi perfil') }}</span><h1>{{ $activeUser->name }}</h1><p>{{ '@'.$activeUser->handle }} · {{ $activeUser->email }}</p></div>
        <div class="balance-card"><span>{{ __('Saldo disponible') }}</span><strong>{{ number_format((float) $activeUser->balance, 2) }}</strong><small>MONO</small></div>
    </section>

    <section class="section container">
        <div class="section-heading"><div><span class="eyebrow">{{ __('Propiedad digital') }}</span><h2>{{ __('Mi inventario') }}</h2></div><p>{{ $inventory->count() === 1 ? __(':count pieza en esta cuenta.', ['count' => 1]) : __(':count piezas en esta cuenta.', ['count' => $inventory->count()]) }}</p></div>
        <div class="inventory-grid">
            @forelse ($inventory as $nft)
                <article class="inventory-card">
                    <div class="inventory-art nft-art" style="--art-start: {{ $nft->collection->palette_from }}; --art-end: {{ $nft->collection->palette_to }}">
                        @if ($nft->collection->image_data)<img class="collection-image" src="{{ route('collections.image', $nft->collection) }}" alt="{{ $nft->collection->name }}" loading="lazy">@endif
                        <span class="nft-edition">#{{ str_pad($nft->token_number, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="nft-monogram">{{ mb_strtoupper(mb_substr($nft->collection->name, 0, 1)) }}</span>
                        <div class="art-ring"></div>
                    </div>
                    <div class="inventory-copy">
                        <span>{{ $nft->collection->name }}</span><h3>{{ $nft->collection->name }} #{{ $nft->token_number }}</h3>
                        <code title="{{ $nft->token_hash }}">{{ substr($nft->token_hash, 0, 14) }}…</code>
                        @if ($nft->activeListing)
                            @if ($nft->activeListing->isAuction())
                                <div class="listed-badge listed-badge-auction">
                                    {{ __('Subasta') }} · {{ $nft->activeListing->leadingBid ? __(':amount MONO en juego', ['amount' => number_format((float) $nft->activeListing->leadingBid->amount, 2)]) : __('sin pujas aún') }}
                                    <small>{{ __('Cierra :time', ['time' => $nft->activeListing->closes_at->diffForHumans()]) }}</small>
                                </div>
                            @else
                                <div class="listed-badge">{{ __('En venta por :price MONO', ['price' => number_format((float) $nft->activeListing->price, 2)]) }}</div>
                            @endif
                        @else
                            <form class="sell-form" action="{{ route('nfts.sell', $nft) }}" method="POST">
                                @csrf
                                <label><span>{{ __('Precio / puja inicial') }}</span><input name="price" type="number" min="0.10" step="0.01" value="{{ number_format($nft->collection->suggestedPrice(), 2, '.', '') }}" required></label>
                                <label><span>{{ __('Modalidad') }}</span>
                                    <select name="type" class="sell-type" onchange="this.closest('form').querySelector('.duration-field').style.display = this.value === 'auction' ? '' : 'none'">
                                        <option value="fixed">{{ __('Directa') }}</option>
                                        <option value="auction">{{ __('Subasta') }}</option>
                                    </select>
                                </label>
                                <label class="duration-field" style="display:none"><span>{{ __('Duración') }}</span>
                                    <select name="duration_hours">
                                        <option value="1">{{ __('1 hora') }}</option>
                                        <option value="6">{{ __(':count horas', ['count' => 6]) }}</option>
                                        <option value="24" selected>{{ __(':count horas', ['count' => 24]) }}</option>
                                        <option value="72">{{ __(':count horas', ['count' => 72]) }}</option>
                                    </select>
                                </label>
                                <button class="button button-small" type="submit">{{ __('Publicar') }}</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="empty-state"><strong>{{ __('Tu inventario está vacío.') }}</strong><p>{{ __('Compra una pieza en el mercado o acuña una nueva colección.') }}</p></div>
            @endforelse
        </div>
    </section>

    <section class="section container">
        <div class="section-heading"><div><span class="eyebrow">{{ __('Historial') }}</span><h2>{{ __('Movimientos recientes') }}</h2></div></div>
        <div class="movement-list">
            @forelse ($movements as $movement)
                <article class="movement-row">
                    <span class="movement-icon">{{ $movement->type === 'sale' ? '↗' : ($movement->type === 'mint' ? '✦' : '◎') }}</span>
                    <div><strong>{{ $movement->type === 'sale' ? __('Venta') : ($movement->type === 'mint' ? __('Acuñación') : __('Publicación')) }} · {{ $movement->nft->collection->name }} #{{ $movement->nft->token_number }}</strong><span>{{ $movement->created_at->diffForHumans() }}</span></div>
                    <strong class="movement-amount">{{ $movement->amount > 0 ? number_format((float) $movement->amount, 2).' MONO' : __('Registrado') }}</strong>
                </article>
            @empty
                <div class="empty-state"><p>{{ __('Aún no hay movimientos para este usuario.') }}</p></div>
            @endforelse
        </div>
    </section>

    <section class="section container">
        <div class="section-heading"><div><span class="eyebrow">{{ __('Subastas') }}</span><h2>{{ __('Mis pujas') }}</h2></div></div>
        <div class="movement-list">
            @forelse ($bids as $bid)
                @php($bidNft = $bid->listing->nft)
                <article class="movement-row">
                    <span class="movement-icon">{{ $bid->status === 'won' ? '🏆' : ($bid->status === 'outbid' ? '↧' : '◔') }}</span>
                    <div>
                        <strong>{{ $bidNft->collection->name }} #{{ $bidNft->token_number }} · {{ __('vende @:handle', ['handle' => $bid->listing->seller->handle]) }}</strong>
                        <span>
                            @if ($bid->status === 'active')
                                {{ __('Vas ganando · cierra :time', ['time' => $bid->listing->closes_at->diffForHumans()]) }}
                            @elseif ($bid->status === 'won')
                                {{ __('Ganaste esta subasta') }}
                            @else
                                {{ __('Superada por otra puja · saldo liberado') }}
                            @endif
                        </span>
                    </div>
                    <strong class="movement-amount">{{ number_format((float) $bid->amount, 2) }} MONO</strong>
                </article>
            @empty
                <div class="empty-state"><p>{{ __('Aún no has ofertado en ninguna subasta.') }}</p></div>
            @endforelse
        </div>
    </section>
@endsection
