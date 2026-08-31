<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Ingredient;
use App\Models\Recipe;

class JsonToMySqlSeeder extends Seeder
{
    public function run(): void
    {
        $ingredientsPath = storage_path('app/private/data/ingredientes.json');
        $recipesPath = storage_path('app/private/data/recetas.json');

        if (!file_exists($ingredientsPath) || !file_exists($recipesPath)) {
            $this->command?->warn('Archivos JSON no encontrados en storage/app/private/data/. Seeder saltado.');
            return;
        }

        $this->seedIngredients($ingredientsPath);
        $this->seedRecipes($recipesPath);
    }

    private function seedIngredients(string $path): void
    {
        $ingredients = json_decode(file_get_contents($path), true);
        $inserted = 0;

        foreach ($ingredients as $i) {
            $exists = Ingredient::where('id', $i['id'])->exists();
            if (!$exists) {
                Ingredient::create([
                    'id' => $i['id'],
                    'name' => $i['name'],
                    'casa_id' => null,
                ]);
                $inserted++;
            }
        }

        $this->command?->info("Ingredientes: {$inserted} nuevos insertados, " . (count($ingredients) - $inserted) . " ya existían.");
    }

    private function seedRecipes(string $path): void
    {
        $recipes = json_decode(file_get_contents($path), true);
        $inserted = 0;

        foreach ($recipes as $r) {
            $exists = Recipe::where('id', $r['id'])->exists();
            if (!$exists) {
                $imagen = !empty($r['imagen']) ? $r['imagen'] : $this->generatePlaceholderImage($r['nombre']);

                Recipe::create([
                    'id' => $r['id'],
                    'nombre' => $r['nombre'],
                    'pasos' => $r['pasos'],
                    'imagen' => $imagen,
                    'casa_id' => null,
                ]);

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

                $inserted++;
            }
        }

        $this->command?->info("Recetas: {$inserted} nuevas insertadas, " . (count($recipes) - $inserted) . " ya existían.");
    }

    private function generatePlaceholderImage(string $nombre): string
    {
        $slug = $this->slugify($nombre);
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

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9àáâäçèéêëìíîïñòóôöùúûü]/u', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
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
