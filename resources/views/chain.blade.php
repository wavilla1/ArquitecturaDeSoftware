@extends('layouts.app')
@section('title', 'Verificación de la cadena')
@section('content')
<section class="page-hero container">
    <span class="eyebrow">Registro público</span><h1>Verificación de <span>la cadena.</span></h1>
    <p>Se recalculan los hashes SHA-256 y se comprueban los enlaces y la secuencia de todos los bloques.</p>
</section>
<section class="container">
    <div class="{{ $verification['valid'] ? 'toast' : 'alert' }}" role="status" data-persistent>
        {{ $verification['valid'] ? 'Cadena íntegra' : 'Alteración detectada' }} · {{ $verification['count'] }} bloques revisados.
        @if (!$verification['valid']) Bloques afectados: {{ implode(', ', $verification['errors']) }}. @endif
    </div>
    <p class="chain-explanation">Esta verificación detecta cambios inconsistentes en el registro interno. No es una blockchain descentralizada: no permite detectar una reescritura completa ni la eliminación del último tramo sin una copia externa de referencia.</p>
    <div class="block-list">
        @foreach ($blocks as $block)
            <article class="chain-block">
                <h2>Bloque #{{ $block->position }} · {{ $block->occurred_at->format('Y-m-d H:i:s') }} UTC</h2>
                <p>{{ $block->event }}</p>
                <details><summary>Ver hashes</summary><p>Anterior</p><code>{{ $block->previous_hash }}</code><p>Actual</p><code>{{ $block->current_hash }}</code></details>
            </article>
        @endforeach
    </div>
    <div class="pagination-wrap">{{ $blocks->links() }}</div>
</section>
@endsection
