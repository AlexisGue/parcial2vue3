<?php

namespace App\Http\Controllers\Parcial;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Doctors\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

class CitaController extends Controller
{
    /**
     * @OA\Get(path="/api/citas", tags={"Citas"}, security={{"sanctum":{}}}, summary="Listar citas",
     *     @OA\Response(response=200, description="OK"))
     */
    public function index(): JsonResponse
    {
        $items = Appointment::query()
            ->with(['patient', 'doctor.user'])
            ->latest('starts_at')
            ->get()
            ->map(fn (Appointment $c) => $this->transform($c));

        return response()->json(['data' => $items]);
    }

    /**
     * @OA\Post(path="/api/citas", tags={"Citas"}, security={{"sanctum":{}}}, summary="Crear cita",
     *     @OA\Response(response=201, description="Creado"))
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'paciente_id' => ['required', 'integer', 'exists:patients,id'],
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'fecha_cita' => ['required', 'date', 'after:now'],
            'estado' => ['nullable', Rule::in([
                Appointment::STATUS_SCHEDULED,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_CANCELLED,
                Appointment::STATUS_COMPLETED,
            ])],
            'notas' => ['nullable', 'string'],
        ]);

        $starts = Carbon::parse($data['fecha_cita']);
        $ends = (clone $starts)->addMinutes(30);

        $overlap = Appointment::query()
            ->where('doctor_id', $data['doctor_id'])
            ->where('status', '!=', Appointment::STATUS_CANCELLED)
            ->where('starts_at', '<', $ends)
            ->where('ends_at', '>', $starts)
            ->exists();

        if ($overlap) {
            return response()->json([
                'message' => 'El doctor ya tiene una cita en ese horario.',
                'code' => 'APPOINTMENT_OVERLAP',
            ], 422);
        }

        $doctor = Doctor::query()->with('specialties')->findOrFail($data['doctor_id']);

        $cita = Appointment::query()->create([
            'folio' => 'C-'.now()->format('ymd').'-'.random_int(1000, 9999),
            'patient_id' => $data['paciente_id'],
            'doctor_id' => $data['doctor_id'],
            'specialty_id' => $doctor->specialties->first()?->id,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'status' => $data['estado'] ?? Appointment::STATUS_SCHEDULED,
            'notes' => $data['notas'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return response()->json(['data' => $this->transform($cita->load(['patient', 'doctor.user']))], 201);
    }

    /**
     * @OA\Get(path="/api/citas/{id}", tags={"Citas"}, security={{"sanctum":{}}}, summary="Ver cita",
     *     @OA\Response(response=200, description="OK"))
     */
    public function show(int $cita): JsonResponse
    {
        $model = Appointment::query()->with(['patient', 'doctor.user'])->findOrFail($cita);

        return response()->json(['data' => $this->transform($model)]);
    }

    /**
     * @OA\Put(path="/api/citas/{id}", tags={"Citas"}, security={{"sanctum":{}}}, summary="Actualizar cita",
     *     @OA\Response(response=200, description="OK"))
     */
    public function update(Request $request, int $cita): JsonResponse
    {
        $model = Appointment::query()->findOrFail($cita);

        $data = $request->validate([
            'paciente_id' => ['sometimes', 'integer', 'exists:patients,id'],
            'doctor_id' => ['sometimes', 'integer', 'exists:doctors,id'],
            'fecha_cita' => ['sometimes', 'date'],
            'estado' => ['sometimes', Rule::in([
                Appointment::STATUS_SCHEDULED,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_CANCELLED,
                Appointment::STATUS_COMPLETED,
                Appointment::STATUS_IN_PROGRESS,
                Appointment::STATUS_NO_SHOW,
            ])],
            'notas' => ['nullable', 'string'],
        ]);

        $payload = [];
        if (isset($data['paciente_id'])) {
            $payload['patient_id'] = $data['paciente_id'];
        }
        if (isset($data['doctor_id'])) {
            $payload['doctor_id'] = $data['doctor_id'];
        }
        if (isset($data['fecha_cita'])) {
            $starts = Carbon::parse($data['fecha_cita']);
            $payload['starts_at'] = $starts;
            $payload['ends_at'] = (clone $starts)->addMinutes(30);
        }
        if (isset($data['estado'])) {
            $payload['status'] = $data['estado'];
        }
        if (array_key_exists('notas', $data)) {
            $payload['notes'] = $data['notas'];
        }

        $model->update($payload);

        return response()->json(['data' => $this->transform($model->fresh()->load(['patient', 'doctor.user']))]);
    }

    /**
     * @OA\Delete(path="/api/citas/{id}", tags={"Citas"}, security={{"sanctum":{}}}, summary="Eliminar cita",
     *     @OA\Response(response=200, description="OK"))
     */
    public function destroy(int $cita): JsonResponse
    {
        Appointment::query()->findOrFail($cita)->delete();

        return response()->json(['message' => 'Cita eliminada']);
    }

    private function transform(Appointment $c): array
    {
        return [
            'id' => $c->id,
            'paciente_id' => $c->patient_id,
            'doctor_id' => $c->doctor_id,
            'fecha_cita' => optional($c->starts_at)?->toIso8601String(),
            'estado' => $c->status,
            'notas' => $c->notes,
            'paciente' => $c->patient?->full_name,
            'doctor' => $c->doctor?->user?->name,
        ];
    }
}
