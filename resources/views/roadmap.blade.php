@extends('layouts.app')

@section('title', 'Pendientes')

@section('content')
    <section class="page-hero container roadmap-hero">
        <span class="eyebrow"><i></i> Próximo incremento</span>
        <h1>Trabajo asignado para el <span>próximo miércoles.</span></h1>
        <p>Fecha objetivo: <strong>{{ \Carbon\Carbon::parse($dueDate)->locale('es')->translatedFormat('l d \d\e F \d\e Y') }}</strong>. Todas las funciones están fuera del alcance de este MVP y comienzan en estado pendiente.</p>
    </section>

    <section class="container roadmap-grid">
        @foreach ($tasks as $task)
            <article class="task-card">
                <div class="task-top"><span class="task-number">{{ $task['number'] }}</span><span class="status-pill">{{ $task['status'] }}</span></div>
                <h2>{{ $task['title'] }}</h2>
                <p>{{ $task['description'] }}</p>
                <div class="task-meta"><span>Responsable</span><strong>{{ $task['owner'] }}</strong></div>
                <div class="task-meta"><span>Entrega</span><strong>23 sep. 2026</strong></div>
            </article>
        @endforeach
    </section>

    <section class="container scope-note">
        <span>Alcance actual</span>
        <p>El MVP ya permite acuñar, comprar, revender y consultar inventario. La cadena se registra internamente, pero su verificación pública corresponde a la tarea 03.</p>
    </section>
@endsection
