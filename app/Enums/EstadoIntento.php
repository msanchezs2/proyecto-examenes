<?php

namespace App\Enums;

enum EstadoIntento: string
{
    case EnCurso = 'en_curso';
    case Entregado = 'entregado';
    case CalificacionParcial = 'calificacion_parcial';
    case Calificado = 'calificado';
}
