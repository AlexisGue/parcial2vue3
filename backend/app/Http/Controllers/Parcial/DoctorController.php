<?php

namespace App\Http\Controllers\Parcial;

use App\Models\User;
use App\Modules\Catalogs\Models\Specialty;
use App\Modules\Doctors\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class DoctorController extends Controller
{
    /**
     * @OA\Get(path="/api/doctores", tags={"Doctores"}, security={{"sanctum":{}}}, summary="Listar doctores",
     *     @OA\Response(response=200, description="OK"))
     */
    public function index(): JsonResponse
    {
        $items = Doctor::query()->with(['user', 'specialties'])->latest('id')->get()
            ->map(fn (Doctor $d) => $this->transform($d));

        return response()->json(['data' => $items]);
    }

    /**
     * @OA\Post(path="/api/doctores", tags={"Doctores"}, security={{"sanctum":{}}}, summary="Crear doctor",
     *     @OA\Response(response=201, description="Creado"))
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'especialidad' => ['nullable', 'string', 'max:120'],
            'cualificaciones' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $doctor = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['nombre'],
                'email' => $data['email'],
                'password' => $data['password'] ?? 'Doctor1234',
                'is_active' => true,
            ]);

            $doctor = Doctor::query()->create([
                'user_id' => $user->id,
                'license_number' => null,
                'bio' => $data['cualificaciones'] ?? null,
                'is_active' => true,
            ]);

            if (! empty($data['especialidad'])) {
                $specialty = Specialty::query()->firstOrCreate(
                    ['slug' => Str::slug($data['especialidad'])],
                    ['name' => $data['especialidad'], 'is_active' => true],
                );
                $doctor->specialties()->syncWithoutDetaching([$specialty->id]);
            }

            return $doctor->load(['user', 'specialties']);
        });

        return response()->json(['data' => $this->transform($doctor)], 201);
    }

    /**
     * @OA\Get(path="/api/doctores/{id}", tags={"Doctores"}, security={{"sanctum":{}}}, summary="Ver doctor",
     *     @OA\Response(response=200, description="OK"))
     */
    public function show(int $doctore): JsonResponse
    {
        // apiResource singulariza "doctores" -> "doctore"
        $doctor = Doctor::query()->with(['user', 'specialties'])->findOrFail($doctore);

        return response()->json(['data' => $this->transform($doctor)]);
    }

    /**
     * @OA\Put(path="/api/doctores/{id}", tags={"Doctores"}, security={{"sanctum":{}}}, summary="Actualizar doctor",
     *     @OA\Response(response=200, description="OK"))
     */
    public function update(Request $request, int $doctore): JsonResponse
    {
        $doctor = Doctor::query()->with(['user', 'specialties'])->findOrFail($doctore);

        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email', 'max:190', 'unique:users,email,'.$doctor->user_id],
            'telefono' => ['nullable', 'string', 'max:50'],
            'especialidad' => ['nullable', 'string', 'max:120'],
            'cualificaciones' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($doctor, $data) {
            if (isset($data['nombre']) || isset($data['email'])) {
                $doctor->user->update(array_filter([
                    'name' => $data['nombre'] ?? null,
                    'email' => $data['email'] ?? null,
                ], fn ($v) => $v !== null));
            }

            if (array_key_exists('cualificaciones', $data)) {
                $doctor->update(['bio' => $data['cualificaciones']]);
            }

            if (! empty($data['especialidad'])) {
                $specialty = Specialty::query()->firstOrCreate(
                    ['slug' => Str::slug($data['especialidad'])],
                    ['name' => $data['especialidad'], 'is_active' => true],
                );
                $doctor->specialties()->sync([$specialty->id]);
            }
        });

        return response()->json(['data' => $this->transform($doctor->fresh()->load(['user', 'specialties']))]);
    }

    /**
     * @OA\Delete(path="/api/doctores/{id}", tags={"Doctores"}, security={{"sanctum":{}}}, summary="Eliminar doctor",
     *     @OA\Response(response=200, description="OK"))
     */
    public function destroy(int $doctore): JsonResponse
    {
        $doctor = Doctor::query()->findOrFail($doctore);

        if ($doctor->appointments()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: el doctor tiene citas registradas.',
            ], 422);
        }

        $userId = $doctor->user_id;
        $doctor->delete();
        User::query()->whereKey($userId)->delete();

        return response()->json(['message' => 'Doctor eliminado']);
    }

    private function transform(Doctor $d): array
    {
        return [
            'id' => $d->id,
            'nombre' => $d->user?->name,
            'email' => $d->user?->email,
            'telefono' => null,
            'especialidad' => $d->specialties->pluck('name')->implode(', ') ?: null,
            'cualificaciones' => $d->bio,
        ];
    }
}
