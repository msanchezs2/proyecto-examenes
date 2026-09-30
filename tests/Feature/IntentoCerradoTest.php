<?php

namespace Tests\Feature;

use App\Enums\EstadoIntento;
use App\Enums\RolUsuario;
use App\Http\Controllers\IntentoExamenController;
use App\Models\Examen;
use App\Models\Grupo;
use App\Models\IntentoExamen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntentoCerradoTest extends TestCase
{
    use RefreshDatabase;

    private User $alumno;

    private Examen $examen;

    private IntentoExamen $intento;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => RolUsuario::Administrador]);
        $this->alumno = User::factory()->create(['role' => RolUsuario::Alumno]);

        $this->examen = Examen::create([
            'titulo' => 'Examen',
            'apertura' => now()->subDay(),
            'cierre' => now()->addDay(),
            'duracion_min' => 30,
            'max_intentos' => 1,
            'creado_por' => $admin->id,
        ]);

        $this->intento = IntentoExamen::create([
            'alumno_id' => $this->alumno->id,
            'examen_id' => $this->examen->id,
            'inicio' => now()->subMinutes(5),
            'fin' => now()->subMinute(),
            'estado' => EstadoIntento::Calificado,
            'salidas_pestana' => IntentoExamenController::MAX_SALIDAS_PESTANA,
            'orden_preguntas' => [],
            'orden_opciones' => [],
        ]);
    }

    public function test_pedir_una_pregunta_de_un_intento_cerrado_redirige_en_vez_de_404(): void
    {
        $this->actingAs($this->alumno)
            ->get(route('intentos.pregunta', [$this->intento, 2]))
            ->assertRedirect(route('intentos.index'))
            ->assertSessionHas('status');
    }

    public function test_responder_a_un_intento_cerrado_redirige_en_vez_de_404(): void
    {
        $this->actingAs($this->alumno)
            ->get(route('intentos.responder', $this->intento))
            ->assertRedirect(route('intentos.index'));
    }

    public function test_entregar_dos_veces_no_da_404(): void
    {
        $this->actingAs($this->alumno)
            ->post(route('intentos.entregar', $this->intento))
            ->assertRedirect(route('intentos.index'));
    }

    public function test_intento_ajeno_sigue_dando_403(): void
    {
        $otro = User::factory()->create(['role' => RolUsuario::Alumno]);

        $this->actingAs($otro)
            ->get(route('intentos.pregunta', [$this->intento, 1]))
            ->assertForbidden();
    }

    public function test_un_examen_cerrado_sigue_listado_si_hay_intento_en_curso(): void
    {
        $this->asignarExamenCerradoAlAlumno();
        $this->intento->update(['estado' => EstadoIntento::EnCurso, 'fin' => null]);

        $this->actingAs($this->alumno)
            ->get(route('intentos.index'))
            ->assertOk()
            ->assertSee('Continuar intento en curso');
    }

    public function test_un_examen_cerrado_sin_intento_en_curso_no_se_lista(): void
    {
        $this->asignarExamenCerradoAlAlumno();

        $this->actingAs($this->alumno)
            ->get(route('intentos.index'))
            ->assertOk()
            ->assertSee('No tenés exámenes disponibles');
    }

    private function asignarExamenCerradoAlAlumno(): void
    {
        $grupo = Grupo::create(['nombre' => 'Grupo A', 'ciclo' => '2026-1', 'administrado_por' => $this->examen->creado_por]);
        $grupo->alumnos()->attach($this->alumno->id);
        $this->examen->grupos()->attach($grupo->id);
        $this->examen->update(['cierre' => now()->subHour()]);
    }

    public function test_el_comando_solo_lista_sin_aplicar(): void
    {
        $this->artisan('intentos:reabrir-por-salidas', ['examen' => $this->examen->id])
            ->assertSuccessful();

        $this->assertSame(EstadoIntento::Calificado, $this->intento->fresh()->estado);
    }

    public function test_el_comando_reabre_por_id_un_intento_ya_vencido_con_tiempo_nuevo(): void
    {
        $this->intento->update([
            'inicio' => now()->subMinutes(20),
            'fin' => now()->subMinutes(2),
            'salidas_pestana' => 0,
        ]);

        $this->artisan('intentos:reabrir-por-salidas', [
            'examen' => $this->examen->id,
            '--intentos' => (string) $this->intento->id,
            '--minutos' => 10,
            '--aplicar' => true,
        ])->assertSuccessful();

        $intento = $this->intento->fresh();

        $this->assertSame(EstadoIntento::EnCurso, $intento->estado);
        $this->assertEqualsWithDelta(
            now()->subMinutes(20)->getTimestamp(),
            $intento->inicio->getTimestamp(),
            5
        );
    }

    public function test_el_comando_reinicia_el_reloj_de_un_intento_en_curso_ya_vencido(): void
    {
        $this->intento->update([
            'inicio' => now()->subMinutes(60),
            'fin' => null,
            'estado' => EstadoIntento::EnCurso,
            'salidas_pestana' => 0,
        ]);

        $this->artisan('intentos:reabrir-por-salidas', [
            'examen' => $this->examen->id,
            '--intentos' => (string) $this->intento->id,
            '--minutos' => 30,
            '--aplicar' => true,
        ])->assertSuccessful();

        $intento = $this->intento->fresh();

        $this->assertSame(EstadoIntento::EnCurso, $intento->estado);
        $this->assertEqualsWithDelta(now()->getTimestamp(), $intento->inicio->getTimestamp(), 5);
    }

    public function test_el_comando_reabre_el_intento_conservando_el_tiempo_restante(): void
    {
        $this->artisan('intentos:reabrir-por-salidas', ['examen' => $this->examen->id, '--aplicar' => true])
            ->assertSuccessful();

        $intento = $this->intento->fresh();

        $this->assertSame(EstadoIntento::EnCurso, $intento->estado);
        $this->assertSame(0, $intento->salidas_pestana);
        $this->assertNull($intento->fin);
        $this->assertEqualsWithDelta(
            now()->subMinutes(4)->getTimestamp(),
            $intento->inicio->getTimestamp(),
            5
        );
    }
}
