<?php

namespace Tests\Feature;

use App\Enums\EstadoIntento;
use App\Enums\EstadoRespuesta;
use App\Enums\RolUsuario;
use App\Enums\TipoPregunta;
use App\Models\Examen;
use App\Models\Grupo;
use App\Models\IntentoExamen;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Respuesta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetalleAlumnoCalificacionesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $alumno;

    private Grupo $grupo;

    private Examen $examen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RolUsuario::Administrador]);
        $this->alumno = User::factory()->create(['role' => RolUsuario::Alumno, 'name' => 'Ana Alumna']);

        $this->grupo = Grupo::create(['nombre' => 'Grupo A', 'ciclo' => '2026-1', 'administrado_por' => $this->admin->id]);
        $this->grupo->alumnos()->attach($this->alumno->id);

        $this->examen = Examen::create([
            'titulo' => 'Examen parcial',
            'apertura' => now()->subDay(),
            'cierre' => now()->addDay(),
            'duracion_min' => 30,
            'max_intentos' => 2,
            'creado_por' => $this->admin->id,
        ]);
        $this->examen->grupos()->attach($this->grupo->id);
    }

    private function pregunta(TipoPregunta $tipo, string $enunciado, float $puntaje = 2): Pregunta
    {
        $pregunta = Pregunta::create([
            'enunciado' => $enunciado,
            'tipo' => $tipo,
            'puntaje' => $puntaje,
            'creado_por' => $this->admin->id,
        ]);
        $this->examen->preguntas()->attach($pregunta->id);

        return $pregunta;
    }

    private function intento(EstadoIntento $estado, array $extra = []): IntentoExamen
    {
        return IntentoExamen::create([
            'alumno_id' => $this->alumno->id,
            'examen_id' => $this->examen->id,
            'inicio' => now()->subHour(),
            'fin' => now(),
            'estado' => $estado,
            ...$extra,
        ]);
    }

    private function urlDetalle(?Grupo $grupo = null, ?User $alumno = null): string
    {
        return route('grupos.examenes.alumnos.detalle', [
            $grupo ?? $this->grupo,
            $this->examen,
            $alumno ?? $this->alumno,
        ]);
    }

    public function test_la_tabla_muestra_boton_de_detalle_solo_para_alumnos_con_intentos(): void
    {
        $sinIntentos = User::factory()->create(['role' => RolUsuario::Alumno, 'name' => 'Sin Intentos']);
        $this->grupo->alumnos()->attach($sinIntentos->id);
        $this->intento(EstadoIntento::Calificado, ['calificacion_final' => 2]);

        $response = $this->actingAs($this->admin)
            ->get(route('grupos.examenes.calificaciones', [$this->grupo, $this->examen]));

        $response->assertOk();
        $response->assertSee($this->urlDetalle(), false);
        $response->assertDontSee($this->urlDetalle(alumno: $sinIntentos), false);
    }

    public function test_muestra_respuestas_de_opcion_multiple_y_abiertas_con_su_puntaje(): void
    {
        $multiple = $this->pregunta(TipoPregunta::OpcionMultiple, 'Capital de Francia');
        $correcta = Opcion::create(['pregunta_id' => $multiple->id, 'texto' => 'París', 'es_correcta' => true]);
        Opcion::create(['pregunta_id' => $multiple->id, 'texto' => 'Roma', 'es_correcta' => false]);
        $abierta = $this->pregunta(TipoPregunta::Abierta, 'Explica la fotosíntesis');

        $intento = $this->intento(EstadoIntento::CalificacionParcial, [
            'calificacion_parcial' => 2,
            'orden_preguntas' => [$abierta->id, $multiple->id],
        ]);
        Respuesta::create([
            'intento_examen_id' => $intento->id,
            'pregunta_id' => $multiple->id,
            'opcion_id' => $correcta->id,
            'es_correcta' => true,
            'puntaje' => 2,
            'estado' => EstadoRespuesta::Calificada,
        ]);
        Respuesta::create([
            'intento_examen_id' => $intento->id,
            'pregunta_id' => $abierta->id,
            'texto' => 'Las plantas convierten luz en energía',
            'estado' => EstadoRespuesta::Pendiente,
        ]);

        $this->actingAs($this->admin)
            ->get($this->urlDetalle())
            ->assertOk()
            ->assertSeeInOrder(['Explica la fotosíntesis', 'Capital de Francia'])
            ->assertSee('Ana Alumna')
            ->assertSee('Las plantas convierten luz en energía')
            ->assertSee('Pendiente de calificar')
            ->assertSee('París')
            ->assertSee('respuesta del alumno')
            ->assertSee('Roma')
            ->assertSee('2 / 2 pts');
    }

    public function test_muestra_todos_los_intentos_del_alumno(): void
    {
        $this->intento(EstadoIntento::Calificado, ['calificacion_final' => 1]);
        $this->intento(EstadoIntento::EnCurso, ['fin' => null]);

        $this->actingAs($this->admin)
            ->get($this->urlDetalle())
            ->assertOk()
            ->assertSee('Intento 1')
            ->assertSee('Intento 2')
            ->assertSee('En curso');
    }

    public function test_devuelve_404_si_el_alumno_no_pertenece_al_grupo(): void
    {
        $ajeno = User::factory()->create(['role' => RolUsuario::Alumno]);

        $this->actingAs($this->admin)->get($this->urlDetalle(alumno: $ajeno))->assertNotFound();
    }

    public function test_devuelve_404_si_el_examen_no_se_aplica_al_grupo(): void
    {
        $otroGrupo = Grupo::create(['nombre' => 'Grupo B', 'ciclo' => '2026-1', 'administrado_por' => $this->admin->id]);
        $otroGrupo->alumnos()->attach($this->alumno->id);

        $this->actingAs($this->admin)->get($this->urlDetalle(grupo: $otroGrupo))->assertNotFound();
    }

    public function test_un_alumno_no_puede_ver_el_detalle(): void
    {
        $this->actingAs($this->alumno)->get($this->urlDetalle())->assertForbidden();
    }

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $this->get($this->urlDetalle())->assertRedirect(route('login'));
    }
}
