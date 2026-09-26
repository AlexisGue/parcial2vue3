<?php

namespace App\Http\Controllers\Parcial;

use App\Modules\Appointments\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class ReporteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/reportes/citas-por-estado",
     *     tags={"Reportes"},
     *     security={{"sanctum":{}}},
     *     summary="Citas agrupadas por estado",
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function citasPorEstado(): JsonResponse
    {
        $rows = Appointment::query()
            ->select('status as estado', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        return response()->json(['data' => $rows]);
    }

    /**
     * @OA\Get(
     *     path="/api/reportes/citas-por-doctor",
     *     tags={"Reportes"},
     *     security={{"sanctum":{}}},
     *     summary="Citas agrupadas por doctor",
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function citasPorDoctor(): JsonResponse
    {
        $rows = Appointment::query()
            ->join('doctors', 'appointments.doctor_id', '=', 'doctors.id')
            ->join('users', 'doctors.user_id', '=', 'users.id')
            ->select(
                'appointments.doctor_id',
                'users.name as doctor',
                DB::raw('COUNT(appointments.id) as total')
            )
            ->groupBy('appointments.doctor_id', 'users.name')
            ->orderByDesc('total')
            ->get();

        return response()->json(['data' => $rows]);
    }
}
