<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * Identifier the Unity app checks on startup to confirm the configured URL
     * really points at a NavSpace panel.
     *
     * Without this, any server answering 200 with valid JSON passes as a working
     * API: the buildings endpoint just deserializes into an empty list and the
     * headset reports "connected" against, say, a random public API. Matching on
     * `app === "navspace"` cannot happen by accident.
     *
     * @return JsonResponse array{data: array{app: string, api_version: int, name: string}}
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'app' => 'navspace',
                'api_version' => 1,
                'name' => config('app.name'),
            ],
        ]);
    }
}
