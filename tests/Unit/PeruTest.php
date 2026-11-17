<?php

namespace Tests\Unit;

use App\Support\Peru;
use PHPUnit\Framework\TestCase;

class PeruTest extends TestCase
{
    public function test_rucs_con_digito_verificador_correcto_pasan(): void
    {
        // RUCs construidos con el algoritmo oficial modulo 11 (SUNAT).
        $this->assertTrue(Peru::rucValido('20100047218'));
        $this->assertTrue(Peru::rucValido('10467836205'));
        $this->assertTrue(Peru::rucValido('20533284362'));
    }

    public function test_rucs_con_digito_verificador_incorrecto_fallan(): void
    {
        $this->assertFalse(Peru::rucValido('20100047219'));
        $this->assertFalse(Peru::rucValido('20123456789')); // cumple formato, no la checksum
        $this->assertFalse(Peru::rucValido('2053328436x')); // contiene letra
    }

    public function test_rucs_con_formato_invalido_fallan(): void
    {
        $this->assertFalse(Peru::rucValido('12345678901')); // prefijo no permitido
        $this->assertFalse(Peru::rucValido('abcdefghijk')); // no numerico
        $this->assertFalse(Peru::rucValido('201000472181')); // 12 digitos
        $this->assertFalse(Peru::rucValido(''));
        $this->assertFalse(Peru::rucValido(null));
    }
}
