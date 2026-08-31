<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\{User, House, Ingredient, Recipe, Planning, ShoppingItem};
use Illuminate\Support\Facades\{Hash, Mail, Storage, DB};
use App\Mail\InvitationMail;
use App\Models\ActivationToken;
use App\Traits\IsSuperAdmin;

class ApiController extends Controller
{
    use IsSuperAdmin;

    private function getCasaId(Request $request)
    {
        return $request->user()->casa_id;
    }

    private function assetUrl(string $path): string
    {
        return config('app.url') . $path;
    }

    private function resolveUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return $this->assetUrl($path);
    }

    public function getAll(Request $request)
    {
        $casaId = $this->getCasaId($request);
        $currentUser = $request->user();

        $currentUserData = [
            'username' => $currentUser->username,
            'nombre' => $currentUser->nombre,
            'foto' => $this->resolveUrl($currentUser->foto),
            'casa_id' => $currentUser->casa_id,
            'nombre_casa' => $currentUser->house->nombre_casa ?? 'Casa',
            'role' => $this->resolveRole($currentUser->username, $currentUser->role)
        ];

        $recipes = Recipe::with('ingredients')->where('casa_id', $casaId)->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'nombre' => $r->nombre,
                'pasos' => $r->pasos,
                'imagen' => $r->imagen ? $this->resolveUrl($r->imagen) : '',
                'ingredientes' => $r->ingredients->map(function ($i) {
                    return [
                        'ingredient_id' => $i->id,
                        'nombre' => $i->name,
                        'quantity' => $i->pivot->quantity
                    ];
                })->toArray()
            ];
        });

        $deletedPlannings = $this->cleanOldPlannings($casaId);

        $planningData = Planning::where('casa_id', $casaId)->get();
        $planning = new \stdClass();
        foreach ($planningData as $p) {
            if (!isset($planning->{$p->day})) {
                $planning->{$p->day} = new \stdClass();
            }
            $planning->{$p->day}->{$p->meal} = $p->recipe_id;
        }

        $globalIngredients = Ingredient::whereNull('casa_id')->get(['id', 'name'])->map(function ($i) {
            return ['id' => $i->id, 'name' => $i->name, 'isPredefined' => true];
        });
        $houseIngredients = Ingredient::where('casa_id', $casaId)->get(['id', 'name'])->map(function ($i) {
            return ['id' => $i->id, 'name' => $i->name, 'isPredefined' => false];
        });
        $allIngredients = $globalIngredients->merge($houseIngredients);

        $globalRecipes = Recipe::with('ingredients')->whereNull('casa_id')->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'nombre' => $r->nombre,
                'pasos' => $r->pasos,
                'imagen' => $r->imagen ? $this->resolveUrl($r->imagen) : '',
                'ingredientes' => $r->ingredients->map(function ($i) {
                    return [
                        'ingredient_id' => $i->id,
                        'nombre' => $i->name,
                        'quantity' => $i->pivot->quantity
                    ];
                })->toArray(),
                'isPredefined' => true
            ];
        });
        $houseRecipes = Recipe::with('ingredients')->where('casa_id', $casaId)->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'nombre' => $r->nombre,
                'pasos' => $r->pasos,
                'imagen' => $r->imagen ? $this->resolveUrl($r->imagen) : '',
                'ingredientes' => $r->ingredients->map(function ($i) {
                    return [
                        'ingredient_id' => $i->id,
                        'nombre' => $i->name,
                        'quantity' => $i->pivot->quantity
                    ];
                })->toArray(),
                'isPredefined' => false
            ];
        });
        $allRecipes = $globalRecipes->merge($houseRecipes);

        return response()->json([
            'ingredients' => $allIngredients,
            'recipes' => $allRecipes,
            'planning' => $planning,
            'shopping' => ShoppingItem::where('casa_id', $casaId)
                ->orderBy('checked')
                ->orderBy('id')
                ->get(['id', 'text', 'checked']),
            'user' => $currentUserData,
            'planning_cleanup' => $deletedPlannings > 0
                ? 'Se eliminaron plannings antiguos de más de 1 mes.'
                : null
        ]);
    }

    private function cleanOldPlannings(string $casaId): int
    {
        $cutoffDate = now()->subMonth()->startOfMonth()->format('Y-m-d');
        return Planning::where('casa_id', $casaId)
            ->where('day', '<', $cutoffDate)
            ->delete();
    }

    public function updateProfile(Request $request)
    {
        $currentUser = $request->user();
        $casaId = $this->getCasaId($request);
        $input = $request->all();

        if (!empty($input['nombre_casa'])) {
            House::where('id', $casaId)->update(['nombre_casa' => trim($input['nombre_casa'])]);
        }

        if (!empty($input['nombre']))
            $currentUser->nombre = $input['nombre'];
        if (!empty($input['foto']))
            $currentUser->foto = $input['foto'];

        if (!empty($input['new_password'])) {
            if (strlen($input['new_password']) < 6) {
                return response()->json(['error' => 'La nueva contraseña debe tener al menos 6 caracteres'], 400);
            }
            if (!Hash::check($input['current_password'] ?? '', $currentUser->password)) {
                return response()->json(['error' => 'La contraseña actual no es correcta'], 403);
            }
            $currentUser->password = Hash::make($input['new_password']);
        }
        $currentUser->save();

        return response()->json(['success' => true]);
    }

    public function inviteUser(Request $request)
    {
        $email = strtolower(trim($request->input('email')));
        $casaId = $this->getCasaId($request);
        $nombreCasa = $request->input('nombre_casa', 'Casa');

        $tempPassword = Str::random(10);
        $token = Str::random(40);
        $inviterName = $request->user()->nombre;

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))
            return response()->json(['error' => 'Email válido requerido'], 400);

        if (User::where('username', $email)->exists()) {
            return response()->json(['error' => 'Este usuario ya está registrado en la app.'], 400);
        }

        $houseUserLimit = config('recetapp.limits.users_per_house', 10);
        $houseUserCount = User::where('casa_id', $casaId)->count();
        if ($houseUserCount >= $houseUserLimit) {
            return response()->json(['error' => "Límite de {$houseUserLimit} usuarios por casa alcanzado."], 429);
        }

        $totalUserLimit = config('recetapp.limits.total_users', 100);
        $totalUserCount = User::count();
        if ($totalUserCount >= $totalUserLimit) {
            return response()->json(['error' => "Límite total de {$totalUserLimit} usuarios alcanzado. No se pueden crear más cuentas."], 429);
        }

        User::create([
            'username' => $email,
            'nombre' => 'Pendiente',
            'password' => Hash::make($tempPassword),
            'foto' => $this->generateInitialsAvatar('Pendiente'),
            'casa_id' => $casaId,
            'role' => 'user',
            'status' => 'pending',
        ]);

        ActivationToken::create([
            'email' => $email,
            'token' => $token,
        ]);

        try {
            Mail::to($email)->send(new InvitationMail($inviterName, $nombreCasa, $token, $tempPassword, $email));
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => true,
                'warning' => 'Usuario creado, pero no se pudo enviar el email automático.'
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function resendInvitation(Request $request)
    {
        $email = strtolower(trim($request->input('email')));
        $casaId = $this->getCasaId($request);
        $nombreCasa = $request->input('nombre_casa', 'Casa');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Email válido requerido'], 400);
        }

        $user = User::where('username', $email)
            ->where('casa_id', $casaId)
            ->where('status', 'pending')
            ->first();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado o ya activado'], 404);
        }

        $tempPassword = Str::random(10);
        $token = Str::random(40);
        $inviterName = $request->user()->nombre;

        ActivationToken::where('email', $email)->delete();

        $user->update(['password' => Hash::make($tempPassword)]);

        ActivationToken::create([
            'email' => $email,
            'token' => $token,
        ]);

        try {
            Mail::to($email)->send(new InvitationMail($inviterName, $nombreCasa, $token, $tempPassword, $email));
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => true,
                'warning' => 'Invitación regenerada, pero no se pudo enviar el email automático.'
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function deleteUser(Request $request)
    {
        $targetUsername = $request->input('target_username');
        $transferTo = $request->input('transfer_to');
        $casaId = $this->getCasaId($request);
        $sessionUser = $request->user()->username;

        if (!$targetUsername)
            return response()->json(['error' => 'Usuario objetivo requerido'], 400);

        $targetUser = User::where('username', $targetUsername)->where('casa_id', $casaId)->first();
        if (!$targetUser)
            return response()->json(['error' => 'Usuario no encontrado en tu casa'], 404);

        $currentUser = User::where('username', $sessionUser)->first();

        if ($targetUsername !== $sessionUser && $currentUser->role !== 'admin') {
            return response()->json(['error' => 'No tienes permisos para eliminar usuarios'], 403);
        }

        $houseUserCount = User::where('casa_id', $casaId)->count();

        if ($targetUsername === $sessionUser && $targetUser->role === 'admin' && $houseUserCount > 1) {
            if (!$transferTo)
                return response()->json(['error' => 'Debes transferir el rol de administrador.'], 400);

            $transferUser = User::where('username', $transferTo)->where('casa_id', $casaId)->first();
            if (!$transferUser)
                return response()->json(['error' => 'El usuario destino no pertenece a tu casa'], 400);

            $transferUser->update(['role' => 'admin']);
        }

        $targetUser->tokens()->delete();
        $targetUser->delete();

        if ($houseUserCount === 1) {
            House::where('id', $casaId)->delete();
        }

        if ($targetUsername === $sessionUser) {
            return response()->json(['success' => true, 'action' => 'logout']);
        }

        return response()->json(['success' => true]);
    }

    public function adminStats(Request $request)
    {
        $casaId = $this->getCasaId($request);
        $sessionUser = $request->user()->username;

        $houseUsers = User::where('casa_id', $casaId)->get(['username', 'nombre', 'role', 'status', 'foto', 'created_at']);

        $pendingEmails = $houseUsers->where('status', 'pending')->pluck('username');
        $tokens = $pendingEmails->isNotEmpty()
            ? ActivationToken::whereIn('email', $pendingEmails)->get(['email', 'created_at'])
            : collect();
        $tokenHours = (int) config('recetapp.invitation_token_hours', 72);

        $houseUsers = $houseUsers->map(function ($u) use ($tokens, $tokenHours) {
            $data = [
                'username' => $u->username,
                'nombre' => $u->nombre,
                'role' => $u->role,
                'status' => $u->status,
                'foto' => $u->foto ? $this->resolveUrl($u->foto) : null,
                'created_at' => $u->created_at ? $u->created_at->format('d/m/Y') : '-',
                'has_pending_token' => false,
                'invitation_expired' => false,
                'invitation_hours_remaining' => null,
            ];

            if ($u->status !== 'pending') {
                return $data;
            }

            $token = $tokens->firstWhere('email', $u->username);
            if (!$token) {
                return $data;
            }

            $createdAt = $token->created_at;
            $expired = !$createdAt || $createdAt->diffInHours(now()) >= $tokenHours;

            $data['has_pending_token'] = true;
            $data['invitation_expired'] = $expired;
            $data['invitation_hours_remaining'] = $expired || !$createdAt
                ? 0
                : max(0, $tokenHours - $createdAt->diffInHours(now()));

            return $data;
        });

        $stats = [
            'is_superadmin' => $this->isSuperAdmin($sessionUser),
            'house' => [
                'total_recipes' => Recipe::where('casa_id', $casaId)->count(),
                'total_ingredients' => Ingredient::where('casa_id', $casaId)->count(),
                'total_shopping' => ShoppingItem::where('casa_id', $casaId)->count(),
                'total_users' => $houseUsers->count(),
                'storage_mb' => $this->calculateStorage($casaId)
            ],
            'house_users' => $houseUsers->values()
        ];

        if ($this->isSuperAdmin($sessionUser)) {
            $stats['global'] = [
                'total_houses' => House::count(),
                'total_users' => User::count(),
                'storage_mb' => $this->calculateGlobalStorage()
            ];
        }

        return response()->json($stats);
    }

    private function calculateStorage(string $casaId): float
    {
        $profileSize = Storage::disk('public')->exists('profiles')
            ? collect(Storage::disk('public')->allFiles("profiles"))
                ->filter(fn($file) => true)
                ->sum(fn($file) => Storage::disk('public')->size($file))
            : 0;

        $recipeSize = Storage::disk('public')->exists('recipes')
            ? collect(Storage::disk('public')->allFiles("recipes"))
                ->sum(fn($file) => Storage::disk('public')->size($file))
            : 0;

        return round(($profileSize + $recipeSize) / (1024 * 1024), 2);
    }

    private function calculateGlobalStorage(): float
    {
        $files = Storage::disk('public')->allFiles();
        $totalSize = collect($files)->sum(fn($file) => Storage::disk('public')->size($file));
        return round($totalSize / (1024 * 1024), 2);
    }

    public function saveIngredient(Request $request)
    {
        $casaId = $this->getCasaId($request);
        $ingId = $request->input('id');
        $ingName = strtolower(trim($request->input('name', '')));

        if (!$ingName)
            return response()->json(['error' => 'Nombre requerido'], 400);

        if ($ingId) {
            $existing = Ingredient::where('id', $ingId)->first();
            if ($existing && is_null($existing->casa_id)) {
                return response()->json(['error' => 'No se puede modificar un ingrediente predefinido.'], 403);
            }
            Ingredient::where('id', $ingId)->where('casa_id', $casaId)->update(['name' => $ingName]);
        } else {
            $limit = config('recetapp.limits.ingredients', 750);
            $currentCount = Ingredient::where('casa_id', $casaId)->count();
            if ($currentCount >= $limit) {
                return response()->json(['error' => "Límite de {$limit} ingredientes alcanzado."], 429);
            }
            Ingredient::create([
                'id' => uniqid(),
                'name' => $ingName,
                'casa_id' => $casaId
            ]);
        }
        return response()->json(['success' => true]);
    }

    public function deleteIngredient(Request $request)
    {
        $casaId = $this->getCasaId($request);
        $ingId = $request->input('id');
        $existing = Ingredient::where('id', $ingId)->first();
        if ($existing && is_null($existing->casa_id)) {
            return response()->json(['error' => 'No se puede eliminar un ingrediente predefinido.'], 403);
        }

        $recipeCount = DB::table('recipe_ingredient')
            ->where('ingredient_id', $ingId)
            ->count();
        if ($recipeCount > 0) {
            return response()->json(['error' => "No se puede eliminar: está asignado a {$recipeCount} receta(s)."], 409);
        }

        $ingName = $existing->name ?? '';
        if ($ingName) {
            $shoppingItems = ShoppingItem::where('casa_id', $casaId)->pluck('text')->toArray();
            foreach ($shoppingItems as $text) {
                if (mb_stripos($text, $ingName) !== false) {
                    return response()->json(['error' => 'No se puede eliminar: está en la lista de la compra.'], 409);
                }
            }
        }

        Ingredient::where('id', $ingId)->where('casa_id', $casaId)->delete();
        return response()->json(['success' => true]);
    }

    public function saveRecipe(Request $request)
    {
        $casaId = $this->getCasaId($request);
        $recipeId = $request->id ?? uniqid();

        if ($request->id) {
            $existing = Recipe::where('id', $request->id)->first();
            if ($existing && is_null($existing->casa_id)) {
                return response()->json(['error' => 'No se puede modificar una receta predefinida.'], 403);
            }
        } else {
            $limit = config('recetapp.limits.recipes', 250);
            $currentCount = Recipe::where('casa_id', $casaId)->count();
            if ($currentCount >= $limit) {
                return response()->json(['error' => "Límite de {$limit} recetas alcanzado."], 429);
            }
        }

        $imagen = $request->imagen ?? '';
        if (empty($imagen)) {
            $imagen = $this->generatePlaceholderImage($request->nombre ?? 'Sin nombre');
        }

        $recipe = Recipe::updateOrCreate(
            ['id' => $recipeId, 'casa_id' => $casaId],
            ['nombre' => $request->nombre ?? '', 'pasos' => $request->pasos ?? '', 'imagen' => $imagen]
        );

        $syncData = [];
        if (is_array($request->ingredientes)) {
            foreach ($request->ingredientes as $ing) {
                $ingName = strtolower(trim($ing['nombre'] ?? ''));
                if (!$ingName)
                    continue;

                $ingredient = Ingredient::firstOrCreate(
                    ['name' => $ingName, 'casa_id' => $casaId],
                    ['id' => uniqid()]
                );
                $syncData[$ingredient->id] = ['quantity' => $ing['quantity'] ?? ''];
            }
        }
        $recipe->ingredients()->sync($syncData);

        return response()->json(['success' => true]);
    }

    public function deleteRecipe(Request $request)
    {
        $casaId = $this->getCasaId($request);
        $recipeId = $request->input('id');

        if (!$recipeId) {
            return response()->json(['error' => 'ID de receta requerido'], 400);
        }

        $recipe = Recipe::where('id', $recipeId)
            ->where(function ($q) use ($casaId) {
                $q->where('casa_id', $casaId)->orWhereNull('casa_id');
            })->first();

        if (!$recipe) {
            return response()->json(['error' => 'Receta no encontrada'], 404);
        }

        if (is_null($recipe->casa_id)) {
            return response()->json(['error' => 'No se puede eliminar una receta predefinida.'], 403);
        }

        $inPlanning = Planning::where('casa_id', $casaId)
            ->where('recipe_id', $recipeId)->count();
        if ($inPlanning > 0) {
            return response()->json(['error' => 'No se puede eliminar: está asignada en el planning semanal.'], 409);
        }

        $ingredientNames = $recipe->ingredients->pluck('name')->toArray();
        if (!empty($ingredientNames)) {
            $shoppingItems = ShoppingItem::where('casa_id', $casaId)->pluck('text')->toArray();
            foreach ($ingredientNames as $name) {
                foreach ($shoppingItems as $text) {
                    if (mb_stripos($text, $name) !== false) {
                        return response()->json(['error' => 'No se puede eliminar: tiene ingredientes en la lista de la compra.'], 409);
                    }
                }
            }
        }

        $recipe->ingredients()->detach();
        $recipe->delete();

        return response()->json(['success' => true]);
    }

    public function savePlanning(Request $request)
    {
        $casaId = $this->getCasaId($request);
        $data = $request->all();
        $maxDate = now()->addMonth()->format('Y-m-d');

        foreach ($data as $day => $meals) {
            if (!is_string($day) || $day > $maxDate) {
                return response()->json(['error' => 'No se puede planificar más de 1 mes adelante.'], 422);
            }
        }

        Planning::where('casa_id', $casaId)->delete();

        foreach ($data as $day => $meals) {
            foreach ($meals as $meal => $recipeId) {
                if ($recipeId) {
                    Planning::create([
                        'casa_id' => $casaId,
                        'day' => $day,
                        'meal' => $meal,
                        'recipe_id' => $recipeId
                    ]);
                }
            }
        }
        return response()->json(['success' => true]);
    }

    public function updateShopping(Request $request)
    {
        $casaId = $this->getCasaId($request);
        $items = $request->all();

        $limit = config('recetapp.limits.shopping', 250);
        if (count($items) > $limit) {
            return response()->json(['error' => "Límite de {$limit} items de compra alcanzado."], 429);
        }

        ShoppingItem::where('casa_id', $casaId)->delete();

        foreach ($items as $item) {
            ShoppingItem::create([
                'id' => $item['id'] ?? uniqid(),
                'casa_id' => $casaId,
                'text' => $item['text'],
                'checked' => $item['checked'] ?? false
            ]);
        }
        return response()->json(['success' => true]);
    }

    public function toggleShoppingItem(Request $request)
    {
        $casaId = $this->getCasaId($request);
        $id = $request->input('id');
        $checked = filter_var($request->input('checked'), FILTER_VALIDATE_BOOLEAN);

        if (!$id) {
            return response()->json(['error' => 'ID requerido'], 400);
        }

        $updated = ShoppingItem::where('id', $id)
            ->where('casa_id', $casaId)
            ->update(['checked' => $checked]);

        if (!$updated) {
            return response()->json(['error' => 'Item no encontrado'], 404);
        }

        return response()->json(['success' => true]);
    }

    public function deleteShoppingItem(Request $request)
    {
        $casaId = $this->getCasaId($request);
        $id = $request->input('id');

        if (!$id) {
            return response()->json(['error' => 'ID requerido'], 400);
        }

        $deleted = ShoppingItem::where('id', $id)
            ->where('casa_id', $casaId)
            ->delete();

        if (!$deleted) {
            return response()->json(['error' => 'Item no encontrado'], 404);
        }

        return response()->json(['success' => true]);
    }

    public function addShoppingItem(Request $request)
    {
        $casaId = $this->getCasaId($request);
        $text = trim($request->input('text', ''));

        if (!$text) {
            return response()->json(['error' => 'Descripción requerida'], 400);
        }

        $limit = config('recetapp.limits.shopping', 250);
        $currentCount = ShoppingItem::where('casa_id', $casaId)->count();
        if ($currentCount >= $limit) {
            return response()->json(['error' => "Límite de {$limit} items de compra alcanzado."], 429);
        }

        $item = ShoppingItem::create([
            'id' => uniqid(),
            'casa_id' => $casaId,
            'text' => $text,
            'checked' => false,
        ]);

        return response()->json([
            'success' => true,
            'item' => ['id' => $item->id, 'text' => $item->text, 'checked' => (bool) $item->checked]
        ]);
    }

    public function addRecipeToShopping(Request $request)
    {
        $casaId = $this->getCasaId($request);
        $recipe = Recipe::with('ingredients')
            ->where('id', $request->input('recipe_id'))
            ->where(function ($q) use ($casaId) {
                $q->where('casa_id', $casaId)->orWhereNull('casa_id');
            })->first();

        if ($recipe) {
            $limit = config('recetapp.limits.shopping', 250);
            $currentItems = ShoppingItem::where('casa_id', $casaId)->get();
            $uncheckedItems = $currentItems->where('checked', false);
            $checkedItems = $currentItems->where('checked', true);

            $newIngredients = $recipe->ingredients->map(function ($ing) {
                return $ing->name . ' (' . $ing->pivot->quantity . ')';
            })->toArray();

            $merged = $this->mergeShoppingItems($uncheckedItems->pluck('text')->toArray(), $newIngredients);

            $newCount = count($merged) + $checkedItems->count() - $currentItems->count();
            if ($currentItems->count() + $newCount > $limit) {
                return response()->json(['error' => "No se pueden añadir ingredientes. Límite de {$limit} items de compra alcanzado."], 429);
            }

            ShoppingItem::where('casa_id', $casaId)->delete();

            foreach ($merged as $text) {
                ShoppingItem::create([
                    'id' => uniqid(),
                    'casa_id' => $casaId,
                    'text' => $text,
                    'checked' => false
                ]);
            }

            foreach ($checkedItems as $item) {
                ShoppingItem::create([
                    'id' => $item->id,
                    'casa_id' => $casaId,
                    'text' => $item->text,
                    'checked' => true
                ]);
            }
        }
        return response()->json(['success' => true]);
    }

    private function mergeShoppingItems(array $existing, array $new): array
    {
        $items = [];

        foreach ($existing as $text) {
            $parsed = $this->parseShoppingText($text);
            $key = $this->normalizeIngredientName($parsed['name']);
            if (!isset($items[$key])) {
                $items[$key] = ['text' => $text, 'parsed' => $parsed];
            }
        }

        foreach ($new as $text) {
            $parsed = $this->parseShoppingText($text);
            $key = $this->normalizeIngredientName($parsed['name']);

            if (isset($items[$key])) {
                $existingParsed = $items[$key]['parsed'];
                $mergedText = $this->combineQuantities($existingParsed, $parsed);
                $items[$key]['text'] = $mergedText;
                $items[$key]['parsed'] = $this->parseShoppingText($mergedText);
            } else {
                $items[$key] = ['text' => $text, 'parsed' => $parsed];
            }
        }

        return array_values(array_map(fn($item) => $item['text'], $items));
    }

    private function parseShoppingText(string $text): array
    {
        $text = trim($text);
        $name = $text;
        $quantity = '';
        $unit = '';

        if (preg_match('/^(.+?)\s*\((.+)\)$/', $text, $m)) {
            $name = trim($m[1]);
            $quantity = trim($m[2]);
        } elseif (preg_match('/^(\d+(?:\.\d+)?)\s+(.+)$/', $text, $m)) {
            $quantity = $m[1];
            $name = trim($m[2]);
        } elseif (preg_match('/^(un|una|unas|uno)\s+(.+)$/i', $text, $m)) {
            $quantity = '1';
            $name = trim($m[2]);
        }

        $quantity = $this->normalizeQuantity($quantity);

        return ['name' => $name, 'quantity' => $quantity];
    }

    private function normalizeQuantity(string $qty): string
    {
        $qty = mb_strtolower(trim($qty));
        $map = ['un' => '1', 'una' => '1', 'unas' => '1', 'uno' => '1'];
        if (isset($map[$qty])) return $map[$qty];
        return $qty;
    }

    private function normalizeIngredientName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $name);
        $name = preg_replace('/s$/', '', $name);
        $name = preg_replace('/es$/', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return $name;
    }

    private function combineQuantities(array $existing, array $new): string
    {
        $name = $existing['name'];
        $qty1 = $existing['quantity'];
        $qty2 = $new['quantity'];

        if (is_numeric($qty1) && is_numeric($qty2)) {
            $sum = (float)$qty1 + (float)$qty2;
            $sumStr = $sum == (int)$sum ? (string)(int)$sum : (string)$sum;
            return $name . ' (' . $sumStr . ')';
        }

        if ($qty1 && $qty2) {
            return $name . ' (' . $qty1 . ' + ' . $qty2 . ')';
        }

        if ($qty2) {
            return $name . ' (' . $qty2 . ')';
        }

        return $name;
    }

    public function uploadProfilePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        $user = $request->user();

        if ($user->foto && !$this->isSvgUrl($user->foto)) {
            $oldPath = parse_url($user->foto, PHP_URL_PATH);
            if ($oldPath) {
                $oldRelative = str_replace('/storage/', '', $oldPath);
                if (Storage::disk('public')->exists($oldRelative)) {
                    Storage::disk('public')->delete($oldRelative);
                }
            }
        }

        $file = $request->file('photo');
        $filename = 'user_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();

        $directory = storage_path('app/public/profiles');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $file->storeAs('profiles', $filename, 'public');

        $url = $this->assetUrl('/storage/profiles/' . $filename);
        $user->update(['foto' => $url]);

        return response()->json(['success' => true, 'url' => $url]);
    }

    public function uploadRecipeImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        $file = $request->file('image');
        $filename = 'recipe_' . uniqid() . '.' . $file->getClientOriginalExtension();

        $directory = storage_path('app/public/recipes');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $file->storeAs('recipes', $filename, 'public');

        $url = $this->assetUrl('/storage/recipes/' . $filename);

        return response()->json(['success' => true, 'url' => $url]);
    }

    public function deleteProfilePhoto(Request $request)
    {
        $user = $request->user();

        if ($user->foto && !$this->isSvgUrl($user->foto)) {
            $oldPath = parse_url($user->foto, PHP_URL_PATH);
            if ($oldPath) {
                $oldRelative = str_replace('/storage/', '', $oldPath);
                if (Storage::disk('public')->exists($oldRelative)) {
                    Storage::disk('public')->delete($oldRelative);
                }
            }
        }

        $defaultFoto = $this->generateInitialsAvatar($user->nombre);
        $user->update(['foto' => $defaultFoto]);

        return response()->json(['success' => true, 'foto' => $this->resolveUrl($defaultFoto)]);
    }

    public function deleteRecipeImage(Request $request)
    {
        $casaId = $this->getCasaId($request);
        $recipeId = $request->input('recipe_id');

        if (!$recipeId) {
            return response()->json(['error' => 'ID de receta requerido'], 400);
        }

        $recipe = Recipe::where('id', $recipeId)->where('casa_id', $casaId)->first();
        if (!$recipe) {
            return response()->json(['error' => 'Receta no encontrada'], 404);
        }

        if ($recipe->imagen && !$this->isSvgUrl($recipe->imagen)) {
            $oldPath = parse_url($recipe->imagen, PHP_URL_PATH);
            if ($oldPath) {
                $oldRelative = str_replace('/storage/', '', $oldPath);
                if (Storage::disk('public')->exists($oldRelative)) {
                    Storage::disk('public')->delete($oldRelative);
                }
            }
        }

        $defaultImage = $this->generatePlaceholderImage($recipe->nombre);
        $recipe->update(['imagen' => $defaultImage]);

        return response()->json(['success' => true, 'imagen' => $this->resolveUrl($defaultImage)]);
    }

    private function isSvgUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);
        return $path && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'svg';
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

        return '/storage/recipes/' . $filename;
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

    private function generateInitialsAvatar(string $nombre): string
    {
        $iniciales = $this->getIniciales($nombre);
        $color = $this->getColorFromName($nombre);
        $slug = strtolower(trim($nombre));
        $slug = preg_replace('/[^a-z0-9àáâäçèéêëìíîïñòóôöùúûü]/u', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        $filename = "avatar_{$slug}.svg";
        $directory = storage_path('app/public/profiles');
        $filepath = "{$directory}/{$filename}";

        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        if (!file_exists($filepath)) {
            $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
  <circle cx="100" cy="100" r="100" fill="{$color}"/>
  <text x="100" y="115" text-anchor="middle" font-family="Arial, sans-serif" font-size="64" font-weight="bold" fill="white" opacity="0.9">{$iniciales}</text>
</svg>
SVG;

            file_put_contents($filepath, $svg);
        }

        return '/storage/profiles/' . $filename;
    }

    private function getIniciales(string $nombre): string
    {
        $words = explode(' ', trim($nombre));
        $iniciales = '';
        $count = 0;

        foreach ($words as $word) {
            if (strlen($word) > 2 && $count < 2) {
                $iniciales .= mb_strtoupper(mb_substr($word, 0, 1));
                $count++;
            }
        }

        return $iniciales ?: mb_strtoupper(mb_substr($nombre, 0, 2));
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
