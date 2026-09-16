@extends('layouts.app')

@section('title', 'Acuñar')

@section('content')
    <section class="page-hero container compact-hero">
        <span class="eyebrow"><i></i> Taller de creación</span>
        <h1>Acuña tu primera <span>pieza digital.</span></h1>
        <p>Para este MVP, cada nueva colección crea su NFT #1 y registra la operación en la cadena interna.</p>
    </section>

    <section class="container create-layout">
        <form class="form-card" action="{{ route('mint.store') }}" method="POST">
            @csrf
            <div class="form-heading"><span>01</span><div><h2>Datos de la colección</h2><p>Creador: {{ '@'.$activeUser->handle }}</p></div></div>

            <label class="field">
                <span>Nombre</span>
                <input id="collection-name" name="name" value="{{ old('name') }}" maxlength="80" placeholder="Ej. Cosmic Signals" required>
            </label>
            <label class="field">
                <span>Descripción</span>
                <textarea name="description" rows="4" maxlength="500" placeholder="Cuenta la idea detrás de tu colección" required>{{ old('description') }}</textarea>
            </label>
            <div class="form-grid">
                <label class="field"><span>Suministro total</span><input name="total_supply" type="number" min="1" max="1000" value="{{ old('total_supply', 25) }}" required></label>
                <label class="field"><span>Precio base (MONO)</span><input name="base_price" type="number" min="0.10" max="999999" step="0.01" value="{{ old('base_price', '2.50') }}" required></label>
            </div>
            <div class="form-grid color-grid">
                <label class="field"><span>Color inicial</span><input id="palette-from" name="palette_from" type="color" value="{{ old('palette_from', '#7c3aed') }}"></label>
                <label class="field"><span>Color final</span><input id="palette-to" name="palette_to" type="color" value="{{ old('palette_to', '#06b6d4') }}"></label>
            </div>
            <label class="check-field">
                <input type="checkbox" name="list_now" value="1" @checked(old('list_now', true))>
                <span><strong>Publicar al acuñar</strong><small>El NFT #1 aparecerá de inmediato en el mercado al precio base.</small></span>
            </label>
            <button class="button button-primary button-wide" type="submit">Acuñar NFT <span>✦</span></button>
        </form>

        <aside class="preview-card">
            <span class="preview-label">Vista previa</span>
            <div id="mint-preview" class="nft-art preview-art" style="--art-start: {{ old('palette_from', '#7c3aed') }}; --art-end: {{ old('palette_to', '#06b6d4') }}">
                <span class="nft-edition">#01 / NUEVO</span>
                <span class="nft-monogram" id="preview-monogram">M</span>
                <div class="art-ring"></div>
            </div>
            <div class="preview-copy"><span>Nueva colección</span><h3 id="preview-name">Tu colección</h3><p>Hash y número de serie generados automáticamente.</p></div>
            <div class="preview-facts"><span>Red <strong>Monoverse</strong></span><span>Formato <strong>NFT</strong></span></div>
        </aside>
    </section>
@endsection
