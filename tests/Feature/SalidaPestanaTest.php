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

class SalidaPestanaTest extends TestCase
{
    use RefreshDatabase;

    private User $alumno;

    private IntentoExamen $intento;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => RolUsuario::Administrador]);
        $this->alumno = User::factory()->create(['role' => RolUsuario::Alumno]);

        $examen = Examen::create([
            'titulo' => 'Examen',
            'apertura' => now()->subDay(),
            'cierre' => now()->addDay(),
            'duracion_min' => 30,
            'max_intentos' => 1,
            'creado_por' => $admin->id,
        ]);

        $this->intento = IntentoExamen::create([
            'alumno_id' => $this->alumno->id,
            'examen_id' => $examen->id,
            'inicio' => now(),
            'estado' => EstadoIntento::EnCurso,
        ]);
    }

    public function test_registra_la_salida_de_pestana(): void
    {
        $this->actingAs($this->alumno)
            ->postJson(route('intentos.salidaPestana', $this->intento))
            ->assertOk()
            ->assertJson(['salidas' => 1, 'entregado' => false]);

        $this->assertSame(1, $this->intento->fresh()->salidas_pestana);
    }

    public function test_entrega_el_examen_al_llegar_al_maximo_de_salidas(): void
    {
        $this->intento->update(['salidas_pestana' => IntentoExamenController::MAX_SALIDAS_PESTANA - 1]);

        $this->actingAs($this->alumno)
            ->postJson(route('intentos.salidaPestana', $this->intento))
            ->assertOk()
            ->assertJson(['entregado' => true]);

        $this->assertNotSame(EstadoIntento::EnCurso, $this->intento->fresh()->estado);
    }

    public function test_no_cuenta_salidas_de_un_intento_ya_entregado(): void
    {
        $this->intento->update(['estado' => EstadoIntento::Entregado]);

        $this->actingAs($this->alumno)
            ->postJson(route('intentos.salidaPestana', $this->intento))
            ->assertStatus(409);

        $this->assertSame(0, $this->intento->fresh()->salidas_pestana);
    }

    public function test_otro_alumno_no_puede_registrar_salidas_ajenas(): void
    {
        $otro = User::factory()->create(['role' => RolUsuario::Alumno]);

        $this->actingAs($otro)
            ->postJson(route('intentos.salidaPestana', $this->intento))
            ->assertForbidden();
    }
}
