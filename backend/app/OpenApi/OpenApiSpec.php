<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Parcial2Vue3 — API Gestión Médica',
    description: 'API RESTful Laravel + Sanctum (tokens) para el Parcial II'
)]
#[OA\Server(url: '/api', description: 'API base')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token'
)]
class OpenApiSpec
{
}
