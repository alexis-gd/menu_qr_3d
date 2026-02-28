<?php
// Router principal de la API. Todas las rutas se determinan por $_GET['route'].
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Respuesta por defecto en JSON
header('Content-Type: application/json; charset=utf-8');

$route = $_GET['route'] ?? '';

switch ($route) {
    case 'menu':
        // Endpoint público que devuelve el menú.
        // En fase 1 devolvemos datos dummy.
        $dummy = [
            'restaurante' => 'Demo Restaurant',
            'slug' => 'demo',
            'categorias' => [
                [
                    'id' => 1,
                    'nombre' => 'Entradas',
                    'orden' => 0,
                    'productos' => [
                        [
                            'id' => 1,
                            'nombre' => 'Tacos al pastor',
                            'descripcion' => 'Tacos tradicionales con piña y cebolla.',
                            'precio' => 99.90,
                            'foto_principal' => BASE_URL . '/imgs/taco.jpg',
                            'tiene_ar' => 0,
                        ],
                    ],
                ],
            ],
        ];
        json_response($dummy);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Ruta no encontrada']);
        break;
}
