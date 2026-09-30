@extends('layouts.app')

@section('title', 'Estado del proyecto')

@section('content')
    <section class="page-hero container roadmap-hero">
        <span class="eyebrow"><i></i> Equipo Monoverse</span>
        <h1>Estado del <span>proyecto.</span></h1>
        <p>Fecha objetivo original: <strong>{{ \Carbon\Carbon::parse($dueDate)->locale('es')->translatedFormat('l d \d\e F \d\e Y') }}</strong>. Funciones integradas y verificadas al 30 de septiembre de 2026.</p>
    </section>

    <section class="container roadmap-grid">
        @foreach ($tasks as $task)
            <article class="task-card">
                <div class="task-top"><span class="task-number">{{ $task['number'] }}</span><span class="status-pill {{ $task['status'] === 'Completado' ? 'status-pill-done' : '' }}">{{ $task['status'] }}</span></div>
                <h2>{{ $task['title'] }}</h2>
                <p>{{ $task['description'] }}</p>
                <div class="task-meta"><span>Responsable</span><strong>{{ $task['owner'] }}</strong></div>
                <div class="task-meta"><span>Entrega</span><strong>23 sep. 2026</strong></div>
            </article>
        @endforeach
    </section>

    <section class="container scope-note">
        <span>Alcance actual</span>
        <p>El MVP permite registrarse, acuñar, comprar, revender, pujar, guardar favoritos y verificar la cadena. El panel de administración modera colecciones y usuarios.</p>
    </section>
@endsection
