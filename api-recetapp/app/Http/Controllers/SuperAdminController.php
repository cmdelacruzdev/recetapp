<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Ingredient, Recipe};
use Illuminate\Support\Facades\{DB, Artisan, Log};

class SuperAdminController extends Controller
{
    public function loadPredefined(Request $request)
    {
        try {
            $ingredientsPath = storage_path('app/private/data/ingredientes.json');
            $recipesPath = storage_path('app/private/data/recetas.json');

            if (!file_exists($ingredientsPath) || !file_exists($recipesPath)) {
                return response()->json(['error' => 'Archivos JSON de datos predefinidos no encontrados.'], 404);
            }

            DB::beginTransaction();

            $ingredients = json_decode(file_get_contents($ingredientsPath), true);
            if (!is_array($ingredients)) {
                DB::rollBack();
                return response()->json(['error' => 'Error al leer ingredientes.json'], 500);
            }

            $ingInserted = 0;
            foreach ($ingredients as $i) {
                $exists = Ingredient::where('id', $i['id'])->whereNull('casa_id')->exists();
                if (!$exists) {
                    Ingredient::create([
                        'id' => $i['id'],
                        'name' => $i['name'],
                        'casa_id' => null,
                    ]);
                    $ingInserted++;
                }
            }

            $recipes = json_decode(file_get_contents($recipesPath), true);
            if (!is_array($recipes)) {
                DB::rollBack();
                return response()->json(['error' => 'Error al leer recetas.json'], 500);
            }

            $recInserted = 0;
            foreach ($recipes as $r) {
                $exists = Recipe::where('id', $r['id'])->whereNull('casa_id')->exists();
                if (!$exists) {
                    $imagen = !empty($r['imagen']) ? $r['imagen'] : $this->generatePlaceholderImage($r['nombre']);

                    Recipe::create([
                        'id' => $r['id'],
                        'nombre' => $r['nombre'],
                        'pasos' => $r['pasos'],
                        'imagen' => $imagen,
                        'casa_id' => null,
                    ]);

                    if (!empty($r['ingredientes'])) {
                        foreach ($r['ingredientes'] as $ing) {
                            $ingredientExists = DB::table('recipe_ingredient')
                                ->where('recipe_id', $r['id'])
                                ->where('ingredient_id', $ing['ingredient_id'])
                                ->exists();

                            if (!$ingredientExists) {
                                DB::table('recipe_ingredient')->insert([
                                    'recipe_id' => $r['id'],
                                    'ingredient_id' => $ing['ingredient_id'],
                                    'quantity' => $ing['quantity'],
                                ]);
                            }
                        }
                    }

                    $recInserted++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$ingInserted} ingredientes y {$recInserted} recetas predefinidas cargadas.",
                'ingredients_inserted' => $ingInserted,
                'recipes_inserted' => $recInserted,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('loadPredefined falló: ' . $e::class . ': ' . $this->safeAscii($e->getMessage()), ['trace' => $this->safeAscii($e->getTraceAsString())]);
            return response()->json(['error' => 'Error al cargar datos predefinidos: ' . $this->safeAscii($e->getMessage())], 500);
        }
    }

    public function deletePredefined(Request $request)
    {
        try {
            DB::beginTransaction();

            $deletedRecipes = Recipe::whereNull('casa_id')->delete();
            $deletedIngredients = Ingredient::whereNull('casa_id')->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$deletedRecipes} recetas y {$deletedIngredients} ingredientes predefinidos eliminados.",
                'deleted_recipes' => $deletedRecipes,
                'deleted_ingredients' => $deletedIngredients,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('deletePredefined falló: ' . $e::class . ': ' . $this->safeAscii($e->getMessage()), ['trace' => $this->safeAscii($e->getTraceAsString())]);
            return response()->json(['error' => 'Error al eliminar datos predefinidos: ' . $this->safeAscii($e->getMessage())], 500);
        }
    }

    public function clearCache(Request $request)
    {
        try {
            Artisan::call('optimize:clear');
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('storage:link');

            return response()->json([
                'success' => true,
                'message' => 'Caché del servidor limpiada, link creado y migraciones ejecutadas correctamente.',
            ]);
        } catch (\Throwable $e) {
            Log::error('clearCache falló: ' . $e::class . ': ' . $this->safeAscii($e->getMessage()), ['trace' => $this->safeAscii($e->getTraceAsString())]);
            return response()->json(['error' => 'Error al ejecutar clearCache: ' . $this->safeAscii($e->getMessage())], 500);
        }
    }

    private function safeAscii(string $value): string
    {
        return preg_replace('/[^\x20-\x7E]/', '?', $value);
    }

    private function generatePlaceholderImage(string $nombre): string
    {
        $slug = strtolower(trim($nombre));
        $slug = preg_replace('/[^a-z0-9àáâäçèéêëìíîïñòóôöùúûü]/u', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        $filename = "{$slug}.svg";
        $directory = storage_path('app/public/recipes');
        $filepath = "{$directory}/{$filename}";

        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $color = $this->getColorFromName($nombre);
        $lines = $this->wrapRecipeName($nombre);
        $textElements = '';
        $startY = 160 - 15 * (count($lines) - 1);
        foreach ($lines as $index => $line) {
            $y = $startY + 30 * $index;
            $textElements .= "  <text x=\"200\" y=\"{$y}\" text-anchor=\"middle\" font-family=\"Arial, sans-serif\" font-size=\"24\" font-weight=\"bold\" fill=\"white\" opacity=\"0.9\">{$line}</text>\n";
        }

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">
  <rect width="400" height="300" fill="{$color}" rx="12"/>
{$textElements}</svg>
SVG;

        file_put_contents($filepath, $svg);

        return "/storage/recipes/{$filename}";
    }

    private function wrapRecipeName(string $nombre): array
    {
        $maxChars = 24;
        $words = preg_split('/\s+/', trim($nombre)) ?: [];

        $single = implode(' ', $words);
        if (strlen($single) <= $maxChars) {
            return [$single];
        }

        $best = null;
        $bestDiff = PHP_INT_MAX;
        for ($i = 1; $i < count($words); $i++) {
            $line1 = implode(' ', array_slice($words, 0, $i));
            $line2 = implode(' ', array_slice($words, $i));

            if (strlen($line1) > $maxChars || strlen($line2) > $maxChars) {
                continue;
            }

            $diff = abs(strlen($line1) - strlen($line2));
            if ($diff < $bestDiff) {
                $bestDiff = $diff;
                $best = [$line1, $line2];
            }
        }

        if ($best) {
            return $best;
        }

        return $this->splitUtf8($single, $maxChars);
    }

    private function splitUtf8(string $text, int $maxChars): array
    {
        $lines = [];
        $current = '';
        $count = 0;
        $length = strlen($text);

        for ($i = 0; $i < $length; $i++) {
            $byte = ord($text[$i]);

            if (($byte & 0xC0) === 0x80) {
                $current .= $text[$i];
                continue;
            }

            if ($count >= $maxChars) {
                $lines[] = $current;
                $current = '';
                $count = 0;
            }

            $current .= $text[$i];
            $count++;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function getColorFromName(string $name): string
    {
        $colors = [
            '#e74c3c', '#e67e22', '#f1c40f', '#2ecc71', '#1abc9c',
            '#3498db', '#9b59b6', '#34495e', '#e91e63', '#00bcd4',
            '#ff5722', '#795548', '#607d8b', '#4caf50', '#ff9800',
        ];

        $hash = crc32($name);
        return $colors[abs($hash) % count($colors)];
    }
}
