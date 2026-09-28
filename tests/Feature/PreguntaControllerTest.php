<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Enums\TipoPregunta;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreguntaControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => RolUsuario::Administrador]);
    }

    /**
     * Reproduce exactamente lo que mandaba el formulario antes del arreglo:
     * el tipo de pregunta es "abierta", pero los campos de opciones seguían
     * viajando en el POST (ocultos con CSS, no deshabilitados), vacíos.
     */
    public function test_crear_pregunta_abierta_no_falla_por_los_campos_de_opciones_ocultos(): void
    {
        $this->actingAs($this->admin())->post(route('preguntas.store'), [
            'tema' => 'General',
            'tipo' => TipoPregunta::Abierta->value,
            'enunciado' => '¿Qué es la fotosíntesis?',
            'puntaje' => 10,
            'opciones' => [
                ['id' => '', 'texto' => ''],
                ['id' => '', 'texto' => ''],
            ],
            'correcta' => '0',
        ])->assertSessionHasNoErrors()->assertRedirect(route('preguntas.index'));

        $pregunta = Pregunta::sole();
        $this->assertSame(TipoPregunta::Abierta, $pregunta->tipo);
        $this->assertSame(0, $pregunta->opciones()->count());
    }

    public function test_crear_pregunta_abierta_sin_enviar_el_campo_opciones(): void
    {
        $this->actingAs($this->admin())->post(route('preguntas.store'), [
            'tema' => 'General',
            'tipo' => TipoPregunta::Abierta->value,
            'enunciado' => '¿Qué es la fotosíntesis?',
            'puntaje' => 10,
        ])->assertSessionHasNoErrors()->assertRedirect(route('preguntas.index'));

        $this->assertSame(TipoPregunta::Abierta, Pregunta::sole()->tipo);
    }

    public function test_crear_pregunta_opcion_multiple_exitosa(): void
    {
        $this->actingAs($this->admin())->post(route('preguntas.store'), [
            'tema' => 'General',
            'tipo' => TipoPregunta::OpcionMultiple->value,
            'enunciado' => 'Capital de Francia',
            'puntaje' => 10,
            'opciones' => [
                ['id' => '', 'texto' => 'París'],
                ['id' => '', 'texto' => 'Roma'],
            ],
            'correcta' => '0',
        ])->assertSessionHasNoErrors()->assertRedirect(route('preguntas.index'));

        $pregunta = Pregunta::sole();
        $this->assertSame(2, $pregunta->opciones()->count());
        $this->assertTrue($pregunta->opciones()->where('texto', 'París')->sole()->es_correcta);
    }

    public function test_crear_pregunta_opcion_multiple_sigue_exigiendo_texto_en_las_opciones(): void
    {
        $this->actingAs($this->admin())->post(route('preguntas.store'), [
            'tema' => 'General',
            'tipo' => TipoPregunta::OpcionMultiple->value,
            'enunciado' => 'Capital de Francia',
            'puntaje' => 10,
            'opciones' => [
                ['id' => '', 'texto' => ''],
                ['id' => '', 'texto' => ''],
            ],
            'correcta' => '0',
        ])->assertSessionHasErrors(['opciones.0.texto', 'opciones.1.texto']);

        $this->assertSame(0, Pregunta::count());
    }

    public function test_editar_pregunta_de_opcion_multiple_a_abierta_borra_sus_opciones(): void
    {
        $admin = $this->admin();
        $pregunta = Pregunta::create([
            'tema' => 'General', 'tipo' => TipoPregunta::OpcionMultiple, 'puntaje' => 10,
            'enunciado' => 'Capital de Francia', 'creado_por' => $admin->id,
        ]);
        Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'París', 'es_correcta' => true]);
        Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => 'Roma', 'es_correcta' => false]);

        $this->actingAs($admin)->put(route('preguntas.update', $pregunta), [
            'tema' => 'General',
            'tipo' => TipoPregunta::Abierta->value,
            'enunciado' => 'Explica la fotosíntesis',
            'puntaje' => 10,
            'opciones' => [
                ['id' => (string) $pregunta->opciones()->first()->id, 'texto' => 'París'],
                ['id' => '', 'texto' => ''],
            ],
            'correcta' => '0',
        ])->assertSessionHasNoErrors()->assertRedirect(route('preguntas.index'));

        $this->assertSame(0, $pregunta->opciones()->count());
    }
}
