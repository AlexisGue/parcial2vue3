<?php

namespace App\Http\Controllers\Parcial;

use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenApi\Annotations as OA;

class ContactoController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/contacto",
     *     tags={"Contacto"},
     *     security={{"sanctum":{}}},
     *     summary="Enviar mensaje de contacto",
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function enviar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'mensaje' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $message = ContactMessage::query()->create($data);

        return response()->json([
            'message' => 'Mensaje recibido correctamente.',
            'data' => [
                'id' => $message->id,
                'email' => $message->email,
                'mensaje' => $message->mensaje,
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/contacto",
     *     tags={"Contacto"},
     *     security={{"sanctum":{}}},
     *     summary="Listar mensajes de contacto",
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function index(): JsonResponse
    {
        $items = ContactMessage::query()
            ->latest('id')
            ->get()
            ->map(fn (ContactMessage $message) => [
                'id' => $message->id,
                'email' => $message->email,
                'mensaje' => $message->mensaje,
                'created_at' => $message->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $items]);
    }
}
