<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCpf implements ValidationRule
{
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail,
    ): void {
        $cpf = preg_replace('/\D/', '', (string) $value);

        if (strlen($cpf) !== 11) {
            return;
        }

        if (
            preg_match('/^(\d)\1{10}$/', $cpf)
            || ! $this->hasValidDigit($cpf, 9, 10)
            || ! $this->hasValidDigit($cpf, 10, 11)
        ) {
            $fail('Informe um CPF válido.');
        }
    }

    private function hasValidDigit(
        string $cpf,
        int $position,
        int $initialWeight,
    ): bool {
        $sum = 0;

        for ($index = 0; $index < $position; $index++) {
            $sum += (int) $cpf[$index] * ($initialWeight - $index);
        }

        $remainder = $sum % 11;
        $digit = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $cpf[$position] === $digit;
    }
}
