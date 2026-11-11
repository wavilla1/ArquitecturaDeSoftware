@extends('layouts.app')

@section('title', __('Estado del proyecto'))

@section('content')
    <section class="page-hero container roadmap-hero">
        <span class="eyebrow"><i></i> {{ __('Equipo Monoverse') }}</span>
        <h1>{{ __('Estado del') }} <span>{{ __('proyecto.') }}</span></h1>
        <p>{{ __('Fecha objetivo original') }}: <strong>{{ \Carbon\Carbon::parse($dueDate)->locale(app()->getLocale())->isoFormat('LL') }}</strong>. {{ __('Funciones integradas y verificadas al 30 de septiembre de 2026.') }}</p>
    </section>

    <section class="container roadmap-grid">
        @foreach ($tasks as $task)
            <article class="task-card">
                <div class="task-top"><span class="task-number">{{ $task['number'] }}</span><span class="status-pill {{ $task['status'] === 'Completado' ? 'status-pill-done' : '' }}">{{ __($task['status']) }}</span></div>
                <h2>{{ __($task['title']) }}</h2>
                <p>{{ __($task['description']) }}</p>
                <div class="task-meta"><span>{{ __('Responsable') }}</span><strong>{{ $task['owner'] }}</strong></div>
                <div class="task-meta"><span>{{ __('Entrega') }}</span><strong>{{ \Carbon\Carbon::parse($dueDate)->locale(app()->getLocale())->isoFormat('ll') }}</strong></div>
            </article>
        @endforeach
    </section>

    <section class="container scope-note">
        <span>{{ __('Alcance actual') }}</span>
        <p>{{ __('El MVP permite registrarse, acuñar, comprar, revender, pujar, guardar favoritos y verificar la cadena. El panel de administración modera colecciones y usuarios.') }}</p>
    </section>
@endsection
