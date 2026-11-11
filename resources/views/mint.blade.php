@extends('layouts.app')

@section('title', __('Acuñar'))

@section('content')
    <section class="page-hero container compact-hero">
        <span class="eyebrow"><i></i> {{ __('Taller de creación') }}</span>
        <h1>{{ __('Acuña tu primera') }} <span>{{ __('pieza digital.') }}</span></h1>
        <p>{{ __('Crea una colección, elige cuántas piezas acuñar y registra cada una con su propio hash.') }}</p>
    </section>

    <section class="container create-layout">
        <form class="form-card" action="{{ route('mint.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-heading"><span>01</span><div><h2>{{ __('Datos de la colección') }}</h2><p>{{ __('Creador') }}: {{ '@'.$activeUser->handle }}</p></div></div>

            <label class="field">
                <span>{{ __('Nombre') }}</span>
                <input id="collection-name" name="name" value="{{ old('name') }}" maxlength="80" placeholder="{{ __('Ej. Cosmic Signals') }}" required>
            </label>
            <label class="field">
                <span>{{ __('Descripción') }}</span>
                <textarea name="description" rows="4" maxlength="500" placeholder="{{ __('Cuenta la idea detrás de tu colección') }}" required>{{ old('description') }}</textarea>
            </label>
            <div class="form-grid">
                <label class="field"><span>{{ __('Suministro total') }}</span><input name="total_supply" type="number" min="1" max="1000" value="{{ old('total_supply', 25) }}" required></label>
                <label class="field"><span>{{ __('Precio base (MONO)') }}</span><input name="base_price" type="number" min="0.10" max="999999" step="0.01" value="{{ old('base_price', '2.50') }}" required></label>
            </div>
            <div class="form-grid color-grid">
                <label class="field"><span>{{ __('Piezas a acuñar (máximo 25)') }}</span><input name="quantity" type="number" min="1" max="25" value="{{ old('quantity', 1) }}" required></label>
                <label class="field"><span>{{ __('Imagen opcional (JPG, PNG, WebP, hasta 1 MB)') }}</span><input name="image" type="file" accept="image/jpeg,image/png,image/webp"></label>
                <label class="field"><span>{{ __('Color inicial') }}</span><input id="palette-from" name="palette_from" type="color" value="{{ old('palette_from', '#7c3aed') }}"></label>
                <label class="field"><span>{{ __('Color final') }}</span><input id="palette-to" name="palette_to" type="color" value="{{ old('palette_to', '#06b6d4') }}"></label>
            </div>
            <label class="check-field">
                <input type="checkbox" name="list_now" value="1" @checked(old('list_now', true))>
                <span><strong>{{ __('Publicar al acuñar') }}</strong><small>{{ __('Las piezas acuñadas aparecerán en el mercado al precio base.') }}</small></span>
            </label>
            <button class="button button-primary button-wide" type="submit">{{ __('Acuñar NFT') }} <span>✦</span></button>
        </form>

        <aside class="preview-card">
            <span class="preview-label">{{ __('Vista previa') }}</span>
            <div id="mint-preview" class="nft-art preview-art" style="--art-start: {{ old('palette_from', '#7c3aed') }}; --art-end: {{ old('palette_to', '#06b6d4') }}">
                <span class="nft-edition">#01 / {{ __('NUEVO') }}</span>
                <span class="nft-monogram" id="preview-monogram">M</span>
                <div class="art-ring"></div>
            </div>
            <div class="preview-copy"><span>{{ __('Nueva colección') }}</span><h3 id="preview-name">{{ __('Tu colección') }}</h3><p>{{ __('Hash y número de serie generados automáticamente.') }}</p></div>
            <div class="preview-facts"><span>{{ __('Red') }} <strong>Monoverse</strong></span><span>{{ __('Formato') }} <strong>NFT</strong></span></div>
        </aside>
    </section>
@endsection
