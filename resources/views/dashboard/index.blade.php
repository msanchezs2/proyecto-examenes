@extends('layouts.simple')

@section('titulo', 'Panel de administración')

@section('content')
    <div class="cabecera">
        <div>
            <h1>Hola, {{ explode(' ', auth()->user()->name)[0] }}</h1>
            <p class="subtitle">Estado general del sistema de exámenes.</p>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-tile">
            <p class="stat-label">Grupos</p>
            <p class="stat-valor">{{ $totalGrupos }}</p>
        </div>
        <div class="stat-tile">
            <p class="stat-label">Alumnos</p>
            <p class="stat-valor">{{ $totalAlumnos }}</p>
        </div>
        <div class="stat-tile">
            <p class="stat-label">Preguntas en el banco</p>
            <p class="stat-valor">{{ $totalPreguntas }}</p>
        </div>
        <div class="stat-tile">
            <p class="stat-label">Exámenes activos</p>
            <p class="stat-valor">{{ $examenesActivos }} <span class="stat-sobre">/ {{ $totalExamenes }}</span></p>
        </div>
        <a href="{{ route('calificaciones.index') }}" class="stat-tile stat-tile-link {{ $pendientesCalificar > 0 ? 'alerta' : 'ok' }}">
            <p class="stat-label">Respuestas por calificar</p>
            <p class="stat-valor">{{ $pendientesCalificar }}</p>
        </a>
    </div>

    <h2 class="seccion-titulo">Accesos rápidos</h2>
    <div class="accesos-grid">
        <a href="{{ route('usuarios.create') }}" class="acceso-card">
            <span class="acceso-titulo">+ Nuevo usuario</span>
            <span class="acceso-desc">Alta de alumno o administrador.</span>
        </a>
        <a href="{{ route('preguntas.create') }}" class="acceso-card">
            <span class="acceso-titulo">+ Nueva pregunta</span>
            <span class="acceso-desc">Sumar al banco reutilizable.</span>
        </a>
        <a href="{{ route('grupos.create') }}" class="acceso-card">
            <span class="acceso-titulo">+ Nuevo grupo</span>
            <span class="acceso-desc">Armar un grupo de alumnos.</span>
        </a>
        <a href="{{ route('examenes.create') }}" class="acceso-card">
            <span class="acceso-titulo">+ Nuevo examen</span>
            <span class="acceso-desc">Armarlo con el banco de preguntas.</span>
        </a>
        <a href="{{ route('calificaciones.index') }}" class="acceso-card">
            <span class="acceso-titulo">Calificar pendientes</span>
            <span class="acceso-desc">Respuestas abiertas sin revisar.</span>
        </a>
    </div>
@endsection
