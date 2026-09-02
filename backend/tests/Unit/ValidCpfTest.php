<?php

namespace Tests\Unit;

use App\Rules\ValidCpf;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidCpfTest extends TestCase
{
    public function test_aceita_cpf_valido_com_ou_sem_formatacao(): void
    {
        foreach (['52998224725', '529.982.247-25'] as $cpf) {
            $validator = Validator::make(
                ['document' => $cpf],
                ['document' => [new ValidCpf]],
            );

            $this->assertFalse($validator->fails());
        }
    }

    public function test_rejeita_digitos_verificadores_invalidos(): void
    {
        $validator = Validator::make(
            ['document' => '52998224724'],
            ['document' => [new ValidCpf]],
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Informe um CPF válido.',
            $validator->errors()->first('document'),
        );
    }

    public function test_rejeita_sequencia_repetida(): void
    {
        $validator = Validator::make(
            ['document' => '11111111111'],
            ['document' => [new ValidCpf]],
        );

        $this->assertTrue($validator->fails());
    }
}
