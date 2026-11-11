@extends('layouts.app')
@section('title', __('Verificación de la cadena'))
@section('content')
<section class="page-hero container">
    <span class="eyebrow">{{ __('Registro público') }}</span><h1>{{ __('Verificación de') }} <span>{{ __('la cadena.') }}</span></h1>
    <p>{{ __('Se recalculan los hashes SHA-256 y se comprueban los enlaces y la secuencia de todos los bloques.') }}</p>
</section>
<section class="container">
    <div class="{{ $verification['valid'] ? 'toast' : 'alert' }}" role="status" data-persistent>
        {{ $verification['valid'] ? __('Cadena íntegra') : __('Alteración detectada') }} · {{ __(':count bloques revisados.', ['count' => $verification['count']]) }}
        @if (!$verification['valid']) {{ __('Bloques afectados: :blocks.', ['blocks' => implode(', ', $verification['errors'])]) }} @endif
    </div>
    <p class="chain-explanation">{{ __('Esta verificación detecta cambios inconsistentes en el registro interno. No es una blockchain descentralizada: no permite detectar una reescritura completa ni la eliminación del último tramo sin una copia externa de referencia.') }}</p>
    <div class="block-list">
        @foreach ($blocks as $block)
            <article class="chain-block">
                <h2>{{ __('Bloque') }} #{{ $block->position }} · {{ $block->occurred_at->format('Y-m-d H:i:s') }} UTC</h2>
                <p>{{ $block->event }}</p>
                <details><summary>{{ __('Ver hashes') }}</summary><p>{{ __('Anterior') }}</p><code>{{ $block->previous_hash }}</code><p>{{ __('Actual') }}</p><code>{{ $block->current_hash }}</code></details>
            </article>
        @endforeach
    </div>
    <div class="pagination-wrap">{{ $blocks->links() }}</div>
</section>
@endsection
