<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DestinationResource;
use App\Models\Destination;

class DestinationController extends Controller
{
    /**
     * Return the destination detail the Unity app shows on arrival.
     */
    public function show(Destination $destination): DestinationResource
    {
        $destination->load(['spaceType', 'node']);

        return new DestinationResource($destination);
    }
}
