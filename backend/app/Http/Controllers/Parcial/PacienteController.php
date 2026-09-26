<?php

namespace App\Http\Controllers\Parcial;

use App\Modules\Patients\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenApi\Annotations as OA;

class PacienteController extends Controller
{
    /**
     * @OA\Get(path="/api/pacientes", tags={"Pacientes"}, security={{"sanctum":{}}}, summary="Listar pacientes",
     *     @OA\Response(response=200, description="OK"))
     */
    public function index(): JsonResponse
    {
        $items = Patient::query()->latest('id')->get()->map(fn (Patient $p) => $this->transform($p));

        return response()->json(['data' => $items]);
    }

    /**
     * @OA\Post(path="/api/pacientes", tags={"Pacientes"}, security={{"sanctum":{}}}, summary="Crear paciente",
     *     @OA\Response(response=201, description="Creado"))
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $patient = Patient::query()->create($data);

        return response()->json(['data' => $this->transform($patient)], 201);
    }

    /**
     * @OA\Get(path="/api/pacientes/{id}", tags={"Pacientes"}, security={{"sanctum":{}}}, summary="Ver paciente",
     *     @OA\Response(response=200, description="OK"))
     */
    public function show(int $paciente): JsonResponse
    {
        $patient = Patient::query()->findOrFail($paciente);

        return response()->json(['data' => $this->transform($patient)]);
    }

    /**
     * @OA\Put(path="/api/pacientes/{id}", tags={"Pacientes"}, security={{"sanctum":{}}}, summary="Actualizar paciente",
     *     @OA\Response(response=200, description="OK"))
     */
    public function update(Request $request, int $paciente): JsonResponse
    {
        $patient = Patient::query()->findOrFail($paciente);
        $patient->update($this->validated($request, $patient->id, $patient->document_number));

        return response()->json(['data' => $this->transform($patient->fresh())]);
    }

    /**
     * @OA\Delete(path="/api/pacientes/{id}", tags={"Pacientes"}, security={{"sanctum":{}}}, summary="Eliminar paciente",
     *     @OA\Response(response=200, description="OK"))
     */
    public function destroy(int $paciente): JsonResponse
    {
        $patient = Patient::query()->findOrFail($paciente);

        if ($patient->appointments()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: el paciente tiene citas registradas.',
            ], 422);
        }

        $patient->delete();

        return response()->json(['message' => 'Paciente eliminado']);
    }

    private function validated(Request $request, ?int $id = null, ?string $documentNumber = null): array
    {
        foreach (['nombre', 'email', 'telefono', 'phone', 'fecha_nacimiento', 'birth_date', 'historial_medico'] as $key) {
            if ($request->exists($key)) {
                $request->merge([$key => $this->blankToNull($request->input($key))]);
            }
        }

        $nombre = $request->input('nombre') ?? trim(($request->input('first_name').' '.$request->input('last_name')));

        $data = $request->validate([
            'nombre' => ['nullable', 'string', 'max:190'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:190'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'birth_date' => ['nullable', 'date'],
            'historial_medico' => ['nullable', 'string'],
            'document_number' => ['nullable', 'string', 'max:50'],
        ]);

        $full = $data['nombre'] ?? $nombre ?? 'Paciente';
        $parts = preg_split('/\s+/', trim($full), 2) ?: ['Paciente'];

        return [
            'document_type' => 'ID',
            'document_number' => $documentNumber ?? $data['document_number'] ?? ('P-'.($id ?? time()).'-'.random_int(100, 999)),
            'first_name' => $data['first_name'] ?? ($parts[0] ?: 'Paciente'),
            'last_name' => $data['last_name'] ?? ($parts[1] ?? ''),
            'email' => $data['email'] ?? null,
            'phone' => $data['telefono'] ?? $data['phone'] ?? null,
            'birth_date' => $data['fecha_nacimiento'] ?? $data['birth_date'] ?? null,
            'notes' => $data['historial_medico'] ?? null,
            'is_active' => true,
        ];
    }

    private function blankToNull(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function transform(Patient $p): array
    {
        return [
            'id' => $p->id,
            'nombre' => $p->full_name,
            'email' => $p->email,
            'telefono' => $p->phone,
            'fecha_nacimiento' => optional($p->birth_date)?->toDateString(),
            'historial_medico' => $p->notes,
        ];
    }
}
