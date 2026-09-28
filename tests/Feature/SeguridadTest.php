<?php

namespace Tests\Feature;

use App\Enums\EstadoIntento;
use App\Enums\RolUsuario;
use App\Enums\TipoPregunta;
use App\Models\Examen;
use App\Models\Grupo;
use App\Models\IntentoExamen;
use App\Models\Pregunta;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(RolUsuario $rol, array $extra = []): User
    {
        return User::factory()->create(['role' => $rol, ...$extra]);
    }

    private function crearSesionEnBaseDeDatos(User $usuario): void
    {
        DB::table('sessions')->insert([
            'id' => 'sesion-'.$usuario->id,
            'user_id' => $usuario->id,
            'payload' => '',
            'last_activity' => time(),
        ]);
    }

    /**
     * @return array{0: User, 1: Examen, 2: Pregunta}
     */
    private function examenAbiertoParaAlumno(User $alumno, int $maxIntentos = 1): array
    {
        $admin = $this->usuario(RolUsuario::Administrador);
        $grupo = Grupo::create(['nombre' => 'Grupo A', 'ciclo' => '2026-1', 'administrado_por' => $admin->id]);
        $grupo->alumnos()->attach($alumno->id);

        $examen = Examen::create([
            'titulo' => 'Examen',
            'apertura' => now()->subDay(),
            'cierre' => now()->addDay(),
            'duracion_min' => 30,
            'max_intentos' => $maxIntentos,
            'creado_por' => $admin->id,
        ]);
        $examen->grupos()->attach($grupo->id);

        $pregunta = Pregunta::create([
            'enunciado' => 'Explica algo',
            'tipo' => TipoPregunta::Abierta,
            'puntaje' => 1,
            'creado_por' => $admin->id,
        ]);
        $examen->preguntas()->attach($pregunta->id);

        return [$admin, $examen, $pregunta];
    }

    public function test_el_login_se_bloquea_tras_cinco_intentos_fallidos(): void
    {
        $this->usuario(RolUsuario::Alumno, ['email' => 'ana@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.attempt'), ['email' => 'ana@example.com', 'password' => 'incorrecta'])
                ->assertSessionHasErrors('email');
        }

        $this->post(route('login.attempt'), ['email' => 'ana@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('email'));
    }

    public function test_un_login_correcto_sigue_funcionando_y_limpia_el_contador(): void
    {
        $this->usuario(RolUsuario::Alumno, ['email' => 'ana@example.com']);

        for ($i = 0; $i < 4; $i++) {
            $this->post(route('login.attempt'), ['email' => 'ana@example.com', 'password' => 'incorrecta']);
        }

        $this->post(route('login.attempt'), ['email' => 'ana@example.com', 'password' => 'password'])
            ->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }

    public function test_olvide_password_responde_igual_exista_o_no_el_correo(): void
    {
        Notification::fake();
        $usuario = $this->usuario(RolUsuario::Alumno, ['email' => 'ana@example.com']);

        $existente = $this->post(route('password.email'), ['email' => 'ana@example.com']);
        $inexistente = $this->post(route('password.email'), ['email' => 'nadie@example.com']);

        $existente->assertSessionHasNoErrors();
        $inexistente->assertSessionHasNoErrors();
        $inexistente->assertSessionHas('status', $existente->getSession()->get('status'));

        Notification::assertSentTo($usuario, ResetPassword::class);
        Notification::assertCount(1);
    }

    public function test_restablecer_password_cierra_sesiones_y_rota_el_remember_token(): void
    {
        $usuario = $this->usuario(RolUsuario::Alumno, ['email' => 'ana@example.com', 'remember_token' => 'viejo-token']);
        $this->crearSesionEnBaseDeDatos($usuario);
        $token = Password::createToken($usuario);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'ana@example.com',
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ])->assertRedirect(route('login'));

        $this->assertNotSame('viejo-token', $usuario->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['user_id' => $usuario->id]);
    }

    public function test_restablecer_con_token_invalido_da_un_mensaje_generico(): void
    {
        $this->usuario(RolUsuario::Alumno, ['email' => 'ana@example.com']);

        $mensaje = 'El enlace de restablecimiento no es válido o ya expiró.';

        $this->post(route('password.update'), [
            'token' => 'token-falso', 'email' => 'ana@example.com',
            'password' => 'NuevaClave123', 'password_confirmation' => 'NuevaClave123',
        ])->assertSessionHasErrors(['email' => $mensaje]);

        $this->post(route('password.update'), [
            'token' => 'token-falso', 'email' => 'nadie@example.com',
            'password' => 'NuevaClave123', 'password_confirmation' => 'NuevaClave123',
        ])->assertSessionHasErrors(['email' => $mensaje]);
    }

    public function test_al_cambiar_la_password_de_otro_usuario_se_cierran_sus_sesiones(): void
    {
        $admin = $this->usuario(RolUsuario::Administrador);
        $alumno = $this->usuario(RolUsuario::Alumno);
        $grupo = Grupo::create(['nombre' => 'G', 'ciclo' => '2026-1', 'administrado_por' => $admin->id]);
        $this->crearSesionEnBaseDeDatos($alumno);

        $this->actingAs($admin)->put(route('usuarios.update', $alumno), [
            'name' => $alumno->name,
            'email' => $alumno->email,
            'password' => 'OtraClave12345',
            'role' => RolUsuario::Alumno->value,
            'matricula' => 'A-1',
            'grupo_id' => $grupo->id,
        ])->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseMissing('sessions', ['user_id' => $alumno->id]);
    }

    public function test_no_se_puede_contestar_una_pregunta_que_todavia_no_se_mostro(): void
    {
        $alumno = $this->usuario(RolUsuario::Alumno);
        [, $examen, $pregunta] = $this->examenAbiertoParaAlumno($alumno);

        $this->actingAs($alumno)->post(route('intentos.iniciar', $examen));
        $intento = IntentoExamen::firstOrFail();
        $url = route('intentos.guardarRespuesta', [$intento, $pregunta]);

        $this->patchJson($url, ['texto' => 'respuesta anticipada'])
            ->assertStatus(409)
            ->assertJson(['guardado' => false]);

        $this->get(route('intentos.pregunta', [$intento, 1]))->assertOk();

        $this->patchJson($url, ['texto' => 'respuesta'])
            ->assertOk()
            ->assertJson(['guardado' => true]);
    }

    public function test_rechaza_respuestas_de_texto_demasiado_largas(): void
    {
        $alumno = $this->usuario(RolUsuario::Alumno);
        [, $examen, $pregunta] = $this->examenAbiertoParaAlumno($alumno);

        $this->actingAs($alumno)->post(route('intentos.iniciar', $examen));
        $intento = IntentoExamen::firstOrFail();
        $this->get(route('intentos.pregunta', [$intento, 1]))->assertOk();

        $this->patchJson(route('intentos.guardarRespuesta', [$intento, $pregunta]), ['texto' => str_repeat('a', 10001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('texto');
    }

    public function test_iniciar_reutiliza_el_intento_en_curso_y_respeta_max_intentos(): void
    {
        $alumno = $this->usuario(RolUsuario::Alumno);
        [, $examen] = $this->examenAbiertoParaAlumno($alumno, maxIntentos: 1);

        $this->actingAs($alumno)->post(route('intentos.iniciar', $examen));
        $this->post(route('intentos.iniciar', $examen));

        $this->assertSame(1, IntentoExamen::count());

        $intento = IntentoExamen::firstOrFail();
        $this->post(route('intentos.entregar', $intento));
        $this->assertNotSame(EstadoIntento::EnCurso, $intento->fresh()->estado);

        $this->post(route('intentos.iniciar', $examen))->assertSessionHas('error');
        $this->assertSame(1, IntentoExamen::count());
    }

    public function test_las_respuestas_incluyen_cabeceras_de_seguridad(): void
    {
        $this->get(route('login'))
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'same-origin');
    }

    public function test_la_csp_usa_un_nonce_que_coincide_con_el_del_html(): void
    {
        $respuesta = $this->get(route('login'))->assertOk();

        $csp = $respuesta->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);

        preg_match("/'nonce-([^']+)'/", $csp, $coincidencia);
        $this->assertNotEmpty($coincidencia[1] ?? null);
        $respuesta->assertSee('<script nonce="'.$coincidencia[1].'">', false);
    }

    public function test_cada_peticion_recibe_un_nonce_distinto(): void
    {
        $primero = $this->get(route('login'))->headers->get('Content-Security-Policy');
        $segundo = $this->get(route('login'))->headers->get('Content-Security-Policy');

        $this->assertNotSame($primero, $segundo);
    }

    public function test_ninguna_vista_tiene_scripts_estilos_o_manejadores_en_linea_sin_nonce(): void
    {
        $admin = $this->usuario(RolUsuario::Administrador);
        $alumno = $this->usuario(RolUsuario::Alumno);

        $paginas = [
            [null, route('welcome')],
            [null, route('login')],
            [null, route('password.request')],
            [$admin, route('dashboard')],
            [$admin, route('usuarios.index')],
            [$admin, route('usuarios.create')],
            [$admin, route('preguntas.index')],
            [$admin, route('preguntas.create')],
            [$admin, route('grupos.index')],
            [$admin, route('examenes.index')],
            [$admin, route('calificaciones.index')],
            [$alumno, route('intentos.index')],
            [null, url('/ruta-que-no-existe')],
        ];

        foreach ($paginas as [$usuario, $url]) {
            $usuario ? $this->actingAs($usuario) : auth()->logout();

            $html = $this->get($url)->getContent();

            $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*nonce=)/i', $html, "script sin nonce en {$url}");
            $this->assertDoesNotMatchRegularExpression('/<style(?![^>]*nonce=)/i', $html, "style sin nonce en {$url}");
            $this->assertDoesNotMatchRegularExpression('/\son(change|submit|click|load|error)\s*=/i', $html, "manejador en línea en {$url}");
        }
    }

    public function test_rechaza_contrasenas_debiles_al_crear_un_usuario(): void
    {
        $admin = $this->usuario(RolUsuario::Administrador);

        foreach (['corta1A', 'todoenminusculas1', 'SINNUMEROSAQUI', 'Abc12345'] as $debil) {
            $this->actingAs($admin)->post(route('usuarios.store'), [
                'name' => 'Nuevo Admin',
                'email' => 'nuevo@example.com',
                'password' => $debil,
                'role' => RolUsuario::Administrador->value,
            ])->assertSessionHasErrors('password');
        }

        $this->assertDatabaseMissing('users', ['email' => 'nuevo@example.com']);
    }

    public function test_acepta_una_contrasena_que_cumple_la_politica(): void
    {
        $admin = $this->usuario(RolUsuario::Administrador);

        $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Nuevo Admin',
            'email' => 'nuevo@example.com',
            'password' => 'ClaveSegura2026',
            'role' => RolUsuario::Administrador->value,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'nuevo@example.com']);
    }

    public function test_restablecer_rechaza_una_contrasena_debil(): void
    {
        $usuario = $this->usuario(RolUsuario::Alumno, ['email' => 'ana@example.com']);

        $this->post(route('password.update'), [
            'token' => Password::createToken($usuario),
            'email' => 'ana@example.com',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ])->assertSessionHasErrors('password');
    }
}
