<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamenController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\IntentoExamenController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PreguntaController;
use App\Http\Controllers\RespuestaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    // Límite alto y por IP: el día del examen, medio salón puede compartir
    // la misma IP de escuela e intentar iniciar sesión en el mismo minuto.
    // El freno real contra fuerza bruta es el RateLimiter por correo+IP
    // dentro de AuthController::login().
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt')->middleware('throttle:100,1');
});

// Sin middleware "guest" a propósito: si alguien llega acá con una sesión
// vieja todavía activa, antes lo mandaba en silencio a su panel en vez de
// dejarlo restablecer la contraseña.
Route::get('/olvide-password', [PasswordResetController::class, 'solicitar'])->name('password.request');
Route::post('/olvide-password', [PasswordResetController::class, 'enviarEnlace'])->name('password.email')->middleware('throttle:5,1');
Route::get('/restablecer-password/{token}', [PasswordResetController::class, 'formulario'])->name('password.reset');
Route::post('/restablecer-password', [PasswordResetController::class, 'restablecer'])->name('password.update')->middleware('throttle:5,1');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');

    Route::get('/preguntas', [PreguntaController::class, 'index'])->name('preguntas.index');
    Route::get('/preguntas/crear', [PreguntaController::class, 'create'])->name('preguntas.create');
    Route::post('/preguntas', [PreguntaController::class, 'store'])->name('preguntas.store');
    Route::get('/preguntas/{pregunta}/editar', [PreguntaController::class, 'edit'])->name('preguntas.edit');
    Route::put('/preguntas/{pregunta}', [PreguntaController::class, 'update'])->name('preguntas.update');

    Route::get('/grupos', [GrupoController::class, 'index'])->name('grupos.index');
    Route::get('/grupos/crear', [GrupoController::class, 'create'])->name('grupos.create');
    Route::post('/grupos', [GrupoController::class, 'store'])->name('grupos.store');
    Route::get('/grupos/{grupo}/editar', [GrupoController::class, 'edit'])->name('grupos.edit');
    Route::put('/grupos/{grupo}', [GrupoController::class, 'update'])->name('grupos.update');
    Route::delete('/grupos/{grupo}', [GrupoController::class, 'destroy'])->name('grupos.destroy');
    Route::get('/grupos/{grupo}/examenes', [GrupoController::class, 'examenes'])->name('grupos.examenes');
    Route::get('/grupos/{grupo}/examenes/{examen}/calificaciones', [GrupoController::class, 'calificaciones'])->name('grupos.examenes.calificaciones');
    Route::get('/grupos/{grupo}/examenes/{examen}/alumnos/{alumno}', [GrupoController::class, 'detalleAlumno'])->name('grupos.examenes.alumnos.detalle');

    Route::get('/examenes', [ExamenController::class, 'index'])->name('examenes.index');
    Route::get('/examenes/crear', [ExamenController::class, 'create'])->name('examenes.create');
    Route::post('/examenes', [ExamenController::class, 'store'])->name('examenes.store');
    Route::get('/examenes/{examen}/editar', [ExamenController::class, 'edit'])->name('examenes.edit');
    Route::put('/examenes/{examen}', [ExamenController::class, 'update'])->name('examenes.update');
    Route::delete('/examenes/{examen}', [ExamenController::class, 'destroy'])->name('examenes.destroy');

    Route::get('/calificaciones', [RespuestaController::class, 'index'])->name('calificaciones.index');
    Route::put('/calificaciones/{respuesta}', [RespuestaController::class, 'update'])->name('calificaciones.update');
});

Route::middleware(['auth', 'alumno'])->group(function () {
    Route::get('/mis-examenes', [IntentoExamenController::class, 'index'])->name('intentos.index');
    Route::post('/examenes/{examen}/iniciar', [IntentoExamenController::class, 'iniciar'])->name('intentos.iniciar');
    Route::get('/intentos/{intento}/responder', [IntentoExamenController::class, 'responder'])->name('intentos.responder');
    Route::get('/intentos/{intento}/preguntas/{posicion}', [IntentoExamenController::class, 'mostrarPregunta'])
        ->whereNumber('posicion')
        ->name('intentos.pregunta');
    Route::patch('/intentos/{intento}/respuestas/{pregunta}', [IntentoExamenController::class, 'guardarRespuesta'])->name('intentos.guardarRespuesta');
    Route::post('/intentos/{intento}/salida-pestana', [IntentoExamenController::class, 'registrarSalidaPestana'])->name('intentos.salidaPestana');
    Route::post('/intentos/{intento}/entregar', [IntentoExamenController::class, 'entregar'])->name('intentos.entregar');
});
