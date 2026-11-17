<?php

namespace App\Support;

/**
 * Catálogos de opciones usadas en los formularios de registro
 * con datos propios del Perú (regiones, DNI, RUC, ciclos, etc.).
 */
class Peru
{
    public const REGIONES = [
        'Amazonas', 'Áncash', 'Apurímac', 'Arequipa', 'Ayacucho', 'Cajamarca',
        'Callao', 'Cusco', 'Huancavelica', 'Huánuco', 'Ica', 'Junín',
        'La Libertad', 'Lambayeque', 'Lima', 'Loreto', 'Madre de Dios',
        'Moquegua', 'Pasco', 'Piura', 'Puno', 'San Martín', 'Tacna',
        'Tumbes', 'Ucayali',
    ];

    public const CICLOS = [
        '1', '2', '3', '4', '5', '6', '7', '8', '9', '10',
        'Egresado', 'Bachiller', 'Titulado',
    ];

    public const DISPONIBILIDADES = [
        '1-5' => '1 a 5 horas por semana',
        '6-10' => '6 a 10 horas por semana',
        '11-15' => '11 a 15 horas por semana',
        '16-20' => '16 a 20 horas por semana',
        '20+' => 'Más de 20 horas por semana',
    ];

    public const INTERESES = [
        'Educación', 'Salud', 'Medio ambiente', 'Animales', 'Niñez y adolescencia',
        'Adultos mayores', 'Inclusión y discapacidad', 'Cultura y arte',
        'Deporte y recreación', 'Tecnología', 'Emprendimiento', 'Derechos humanos',
    ];

    public const TIPOS_ORGANIZACION = [
        'ONG', 'Fundación', 'Asociación civil', 'Comité vecinal',
        'Institución educativa', 'Cooperativa', 'Organización comunal', 'Otra',
    ];

    public const SECTORES = [
        'Educación', 'Salud', 'Medio ambiente', 'Desarrollo social',
        'Niñez y adolescencia', 'Adultos mayores', 'Inclusión y discapacidad',
        'Cultura y arte', 'Animales', 'Derechos humanos', 'Emprendimiento',
        'Deporte y recreación', 'Otro',
    ];

    public const UNIVERSIDADES = [        'Universidad Nacional Mayor de San Marcos',
        'Universidad Nacional Agraria La Molina',
        'Universidad Nacional de Ingeniería',
        'Universidad Nacional Federico Villarreal',
        'Universidad Nacional de San Antonio Abad del Cusco',
        'Universidad Nacional de Trujillo',
        'Universidad Nacional de Arequipa (UNSA)',
        'Universidad Nacional del Altiplano (Puno)',
        'Universidad Nacional de San Cristóbal de Huamanga (Ayacucho)',
        'Universidad Nacional Hermilio Valdizán (Huánuco)',
        'Universidad Nacional de Piura',
        'Universidad Nacional Jorge Basadre Grohmann (Tacna)',
        'Universidad Nacional José Faustino Sánchez Carrión (Huacho)',
        'Universidad Nacional del Santa (Chimbote)',
        'Universidad Nacional de Ucayali',
        'PUCP',
        'Universidad de Lima',
        'Universidad del Pacífico',
        'Universidad de Piura',
        'Universidad San Ignacio de Loyola',
        'Universidad Científica del Sur',
        'Universidad Peruana de Ciencias Aplicadas (UPC)',
        'Universidad César Vallejo',
        'Universidad Tecnológica del Perú (UTP)',
        'Universidad Andina del Cusco',
        'Universidad Católica San Pablo (Arequipa)',
        'Universidad Católica de Santa María (Arequipa)',
        'Universidad Privada del Norte (Trujillo)',
        'Universidad Señor de Sipán (Chiclayo)',
        'Instituto / Escuela superior',
    ];

    /**
     * Verifica el digito verificador de un RUC peruano de 11 digitos
     * (algoritmo modulo 11 con los factores oficiales de SUNAT sobre
     * los primeros 10 digitos). Rechaza RUCs inventados que cumplen
     * el formato pero no la checksum.
     */
    public static function rucValido(?string $ruc): bool
    {
        if ($ruc === null || ! preg_match('/^(10|15|17|20)\d{9}$/', $ruc)) {
            return false;
        }

        $factores = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        for ($i = 0; $i < 10; $i++) {
            $suma += (int) $ruc[$i] * $factores[$i];
        }

        // resto 10 -> digito 0, resto 11 -> digito 1
        $digitoVerificador = (11 - ($suma % 11)) % 10;

        return (int) $ruc[10] === $digitoVerificador;
    }
}