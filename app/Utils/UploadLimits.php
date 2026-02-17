<?php

namespace App\Utils;

class UploadLimits
{
    /**
     * Configuración de archivos por tipo.
     */
    protected const CONFIG = [
        'image' => [
            'max_kb' => 10240, // 10 MB
            'mime' => ['image/jpeg', 'image/png', 'image/webp'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        ],
        'pdf' => [
            'max_kb' => 20480, // 20 MB
            'mime' => ['application/pdf'],
            'extensions' => ['pdf'],
        ],
        'zip' => [
            'max_kb' => 819200, // 800 MB
            'mime' => ['application/zip', 'application/x-zip-compressed'],
            'extensions' => ['zip'],
        ],
        'video' => [
            'max_kb' => 1024000, // 1000 MB
            'mime' => ['video/mp4', 'video/mpeg'],
            'extensions' => ['mp4', 'mpeg'],
        ],
        'generic' => [
            'max_kb' => 51200, // 50 MB
            'mime' => ['application/octet-stream'],
            'extensions' => [],
        ],
    ];

    /**
     * Obtener el tamaño máximo en KB para un tipo.
     */
    public static function maxSize(string $type): int
    {
        return self::CONFIG[$type]['max_kb'] ?? self::CONFIG['generic']['max_kb'];
    }

    /**
     * Obtener los MIME types aceptados.
     */
    public static function mimeTypes(string $type): array
    {
        return self::CONFIG[$type]['mime'] ?? self::CONFIG['generic']['mime'];
    }

    /**
     * Obtener extensiones válidas (opcional).
     */
    public static function extensions(string $type): array
    {
        return self::CONFIG[$type]['extensions'] ?? [];
    }
}
