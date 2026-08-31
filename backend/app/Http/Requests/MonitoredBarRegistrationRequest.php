<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MonitoredBarRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $lawyerName =
            $this->input(
                'lawyer_name'
            );

        $barNumber =
            $this->input(
                'bar_number'
            );

        $state =
            $this->input(
                'state'
            );

        $this->merge([
            'lawyer_name' => is_string($lawyerName)
                    ? preg_replace(
                        '/\s+/u',
                        ' ',
                        trim($lawyerName),
                    )
                    : $lawyerName,

            'bar_number' => is_string($barNumber)
                    ? preg_replace(
                        '/[^0-9A-Z]/',
                        '',
                        strtoupper($barNumber),
                    )
                    : $barNumber,

            'state' => is_string($state)
                    ? strtoupper(
                        trim($state)
                    )
                    : $state,
        ]);
    }

    public function rules(): array
    {
        $organizationId =
            app(
                CurrentOrganization::class
            )->id();

        $registrationId =
            $this->route(
                'monitoredBarRegistration'
            )?->getKey();

        return [
            'lawyer_name' => [
                'required',
                'string',
                'max:180',
            ],

            'bar_number' => [
                'required',
                'string',
                'max:20',
                'regex:/^[0-9A-Z]+$/',

                Rule::unique(
                    'monitored_bar_registrations',
                    'bar_number',
                )
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'organization_id',
                                $organizationId,
                            )
                            ->where(
                                'state',
                                $this->input('state'),
                            )
                    )
                    ->ignore(
                        $registrationId
                    ),
            ],

            'state' => [
                'required',
                'string',

                Rule::in([
                    'AC',
                    'AL',
                    'AP',
                    'AM',
                    'BA',
                    'CE',
                    'DF',
                    'ES',
                    'GO',
                    'MA',
                    'MT',
                    'MS',
                    'MG',
                    'PA',
                    'PB',
                    'PR',
                    'PE',
                    'PI',
                    'RJ',
                    'RN',
                    'RS',
                    'RO',
                    'RR',
                    'SC',
                    'SP',
                    'SE',
                    'TO',
                ]),
            ],

            'active' => [
                'sometimes',
                'boolean',
            ],

            'monitoring_started_on' => [
                'nullable',
                'date_format:Y-m-d',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'lawyer_name.required' => 'Informe o nome do advogado.',

            'lawyer_name.max' => 'O nome do advogado não pode ultrapassar 180 caracteres.',

            'bar_number.required' => 'Informe o número da OAB.',

            'bar_number.regex' => 'O número da OAB é inválido.',

            'bar_number.unique' => 'Esta inscrição OAB já está sendo monitorada.',

            'state.required' => 'Informe a UF da inscrição.',

            'state.in' => 'A UF da inscrição é inválida.',

            'active.boolean' => 'A situação do monitoramento é inválida.',

            'monitoring_started_on.date_format' => 'A data inicial deve estar no formato AAAA-MM-DD.',
        ];
    }
}
