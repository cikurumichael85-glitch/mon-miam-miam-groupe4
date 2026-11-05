<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ListRestaurantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La consultation des établissements publiés est publique, sans connexion.
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        // Borner les demandes avant la requête SQL pour éviter des pages excessives.
        return [
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        // Un corps GET ne doit pas remplacer les paramètres de pagination de l'URL.
        return $this->query();
    }

    protected function failedValidation(Validator $validator): void
    {
        // Fournir une erreur JSON stable que l'interface pourra présenter à l'utilisateur.
        throw new HttpResponseException(response()->json([
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Les paramètres de pagination sont invalides.',
                'fields' => $validator->errors()->toArray(),
            ],
        ], 422));
    }
}
