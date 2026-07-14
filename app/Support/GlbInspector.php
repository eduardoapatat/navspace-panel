<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Reads metadata from a stored .glb file to auto-configure how Unity should load
 * it. Currently detects the exporter (glTF `asset.generator`) to correct the
 * well-known 180° yaw flip that Unity's glTF importers apply to Blender exports.
 */
class GlbInspector
{
    /**
     * Yaw correction (degrees) Unity must apply to a model exported by the given
     * tool. Blender-exported glb import 180° rotated in Unity's glTF importers
     * (glTFast), so they need a 180° correction; other exporters need none.
     *
     * @param  string  $relativePath  Path on the public disk (e.g. "models/x.glb").
     */
    public static function detectModelYaw(string $relativePath): int
    {
        $generator = self::readGenerator($relativePath);

        if ($generator !== null && str_contains(strtolower($generator), 'blender')) {
            return 180;
        }

        return 0;
    }

    /**
     * Reads the glTF `asset.generator` string from a binary .glb, or null when it
     * can't be read (missing file, not a GLB, malformed header).
     */
    public static function readGenerator(string $relativePath): ?string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($relativePath)) {
            return null;
        }

        $bytes = $disk->get($relativePath);

        // GLB header: magic "glTF" (0x46546C67), version, total length (12 bytes).
        if (strlen($bytes) < 20 || substr($bytes, 0, 4) !== 'glTF') {
            return null;
        }

        // First chunk: length (4), type (4, JSON = 0x4E4F534A), then the JSON body.
        $header = unpack('V', substr($bytes, 12, 4));

        if ($header === false) {
            return null;
        }

        $chunkLength = $header[1];
        $chunkType = substr($bytes, 16, 4);

        if ($chunkType !== 'JSON' || strlen($bytes) < 20 + $chunkLength) {
            return null;
        }

        $json = json_decode(substr($bytes, 20, $chunkLength), true);

        return $json['asset']['generator'] ?? null;
    }
}
