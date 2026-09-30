<?php

namespace Tests\Feature;

use App\Enums\EstadoIntento;
use App\Enums\RolUsuario;
use App\Http\Controllers\IntentoExamenController;
use App\Models\Examen;
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

    public function test_el_comando_solo_lista_sin_aplicar(): void
    {
        $this->artisan('intentos:reabrir-por-salidas', ['examen' => $this->examen->id])
            ->assertSuccessful();

        $this->assertSame(EstadoIntento::Calificado, $this->intento->fresh()->estado);
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
