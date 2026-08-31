<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use RuntimeException;

class JsonDatabase
{
    public static function readGlobal($file)
    {
        return self::readArray("data/{$file}.json");
    }

    public static function writeGlobal($file, $data)
    {
        self::atomicWrite("data/{$file}.json", self::encode($data));
    }

    public static function readHouse($casaId, $file)
    {
        return self::readArray("data/{$casaId}/{$file}.json");
    }

    public static function writeHouse($casaId, $file, $data)
    {
        self::atomicWrite("data/{$casaId}/{$file}.json", self::encode($data));
    }

    public static function getHouseDirectorySize($casaId)
    {
        return self::directorySize(Storage::path("data/{$casaId}"));
    }

    public static function getGlobalDirectorySize()
    {
        return self::directorySize(Storage::path('data'));
    }

    private static function directorySize(string $path): int
    {
        $size = 0;
        if (File::isDirectory($path)) {
            foreach (File::allFiles($path) as $file) {
                $size += $file->getSize();
            }
        }
        return $size;
    }

    public static function loadPredefinedToHouse($casaId, $file)
    {
        $globalPath = "data/{$file}.json";
        $housePath = "data/{$casaId}/{$file}.json";
        if (Storage::exists($globalPath)) {
            self::atomicWrite($housePath, Storage::get($globalPath));
        }
    }

    private static function encode($data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json === false)
            throw new RuntimeException('No se pudo codificar los datos a JSON');

        return $json;
    }

    private static function readArray(string $relativePath): array
    {
        if (!Storage::exists($relativePath))
            return [];

        $decoded = json_decode(Storage::get($relativePath), true);

        // Un fichero corrupto debe fallar de forma visible, nunca convertirse en []
        // y provocar un borrado silencioso de los datos en la siguiente escritura.
        if (!is_array($decoded)) {
            report(new RuntimeException("JSON inválido o corrupto en storage/app/{$relativePath}"));
            throw new RuntimeException("Datos corruptos en {$relativePath}");
        }

        return $decoded;
    }

    private static function atomicWrite(string $relativePath, string $contents): void
    {
        $targetPath = Storage::path($relativePath);
        $directory = dirname($targetPath);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory))
            throw new RuntimeException("No se pudo crear el directorio: {$directory}");

        // Escritura atómica: fichero temporal + rename para evitar ficheros parciales
        // y escrituras concurrentes que se pisen entre sí.
        $tempPath = $targetPath . '.tmp.' . uniqid();

        if (file_put_contents($tempPath, $contents, LOCK_EX) === false) {
            @unlink($tempPath);
            throw new RuntimeException("No se pudo escribir: {$tempPath}");
        }

        if (!rename($tempPath, $targetPath)) {
            @unlink($tempPath);
            throw new RuntimeException("No se pudo guardar: {$relativePath}");
        }
    }
}
