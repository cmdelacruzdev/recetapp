<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{User, House, ActivationToken};
use Illuminate\Support\Facades\{Hash, Mail, DB};
use Illuminate\Support\Str;
use App\Mail\WelcomeMail;
use App\Mail\InvitationMail;
use App\Mail\ResetPasswordMail;
use App\Traits\IsSuperAdmin;

class AuthController extends Controller
{
    use IsSuperAdmin;

    public function register(Request $request)
    {
        $data = $request->all();

        if (empty($data['usuario']) || empty($data['password']) || empty($data['nombre']) || empty($data['nombre_casa'])) {
            return response()->json(['error' => 'Faltan datos requeridos'], 400);
        }

        if (User::where('username', $data['usuario'])->exists()) {
            return response()->json(['error' => 'Este correo electrónico ya está registrado'], 400);
        }

        $totalUserLimit = config('recetapp.limits.total_users', 100);
        $totalUserCount = User::count();
        if ($totalUserCount >= $totalUserLimit) {
            return response()->json(['error' => "Límite total de {$totalUserLimit} usuarios alcanzado. No se pueden crear más cuentas."], 429);
        }

        $casaSlug = substr(preg_replace('/[^a-z0-9]/', '', strtolower($data['nombre_casa'])), 0, 15) ?: 'home';
        $newCasaId = 'casa_' . uniqid() . '_' . $casaSlug;

        House::create(['id' => $newCasaId, 'nombre_casa' => trim($data['nombre_casa'])]);

        $foto = $this->generateInitialsAvatar($data['nombre']);

        $user = User::create([
            'username' => $data['usuario'],
            'nombre' => $data['nombre'],
            'password' => Hash::make($data['password']),
            'foto' => $foto,
            'casa_id' => $newCasaId,
            'role' => 'admin',
            'status' => 'active'
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        Mail::to($data['usuario'])->send(new WelcomeMail($data['nombre'], $data['nombre_casa']));

        return response()->json([
            'token' => $token,
            'user' => [
                'username' => $user->username,
                'nombre' => $user->nombre,
                'foto' => $user->foto,
                'casa_id' => $user->casa_id,
                'nombre_casa' => $data['nombre_casa'],
                'role' => $this->resolveRole($data['usuario'], $user->role),
            ]
        ]);
    }

    public function login(Request $request)
    {
        $user = User::where('username', $request->input('username'))->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        if ($user->status !== 'active') {
            return response()->json(['error' => 'Cuenta pendiente de activación. Revisa tu email.'], 403);
        }

        // Multi-sesión: no se borran todos los tokens (tumbaría otros dispositivos).
        // Solo se limpian los más antiguos para no acumular sesiones.
        $tokens = $user->tokens()->orderByDesc('created_at')->pluck('id')->slice(29);
        if ($tokens->isNotEmpty()) {
            DB::table('personal_access_tokens')->whereIn('id', $tokens->all())->delete();
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'username' => $user->username,
                'nombre' => $user->nombre,
                'foto' => $user->foto,
                'casa_id' => $user->casa_id,
                'nombre_casa' => $user->house->nombre_casa ?? 'Casa',
                'role' => $this->resolveRole($user->username, $user->role),
            ]
        ]);
    }

    public function logout(Request $request)
    {
        // Solo se invalida la sesión actual; el resto de dispositivos sigue conectado.
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['success' => true]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'username' => $user->username,
            'nombre' => $user->nombre,
            'foto' => $user->foto,
            'casa_id' => $user->casa_id,
            'nombre_casa' => $user->house->nombre_casa ?? 'Casa',
            'role' => $this->resolveRole($user->username, $user->role),
        ]);
    }

    public function activateAccount($token)
    {
        $activationToken = ActivationToken::where('token', $token)->first();
        $frontendUrl = config('recetapp.frontend_url', 'http://localhost:4200');

        if (!$activationToken) {
            return redirect($frontendUrl . '/login?error=invalid_token');
        }

        if ($this->isTokenExpired($activationToken)) {
            $activationToken->delete();
            return redirect($frontendUrl . '/login?error=expired_token');
        }

        $email = urlencode($activationToken->email);
        return redirect($frontendUrl . "/activate?token={$token}&email={$email}");
    }

    public function activateAccountForm(Request $request)
    {
        $token = $request->input('token');
        $email = strtolower(trim($request->input('email', '')));
        $nombre = trim($request->input('nombre', ''));
        $tempPassword = $request->input('temp_password');
        $newPassword = $request->input('new_password');

        if (!$token || !$email || !$nombre || !$tempPassword || !$newPassword) {
            return response()->json(['error' => 'Todos los campos son requeridos'], 400);
        }

        $activationToken = ActivationToken::where('token', $token)
            ->where('email', $email)
            ->first();

        if (!$activationToken) {
            return response()->json(['error' => 'Token inválido o usuario no encontrado'], 400);
        }

        if ($this->isTokenExpired($activationToken)) {
            $activationToken->delete();
            return response()->json(['error' => 'El enlace de activación ha expirado. Solicita una nueva invitación.'], 400);
        }

        $user = User::where('username', $email)->where('status', 'pending')->first();
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado o ya activado'], 400);
        }

        if (!Hash::check($tempPassword, $user->password)) {
            return response()->json(['error' => 'La contraseña temporal no es correcta'], 400);
        }

        if (strlen($newPassword) < 6) {
            return response()->json(['error' => 'La contraseña debe tener al menos 6 caracteres'], 400);
        }

        $avatar = $this->generateInitialsAvatar($nombre);

        $user->update([
            'nombre' => $nombre,
            'foto' => $avatar,
            'password' => Hash::make($newPassword),
            'status' => 'active',
        ]);

        $activationToken->delete();

        $user->tokens()->delete();
        $authToken = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $authToken,
            'user' => [
                'username' => $user->username,
                'nombre' => $user->nombre,
                'foto' => $this->resolveFoto($user->foto),
                'casa_id' => $user->casa_id,
                'nombre_casa' => $user->house->nombre_casa ?? 'Casa',
                'role' => $this->resolveRole($user->username, $user->role),
            ]
        ]);
    }

    private function isTokenExpired(ActivationToken $activationToken): bool
    {
        $hours = (int) config('recetapp.invitation_token_hours', 72);
        $createdAt = $activationToken->created_at;

        if (!$createdAt) {
            return true;
        }

        return $createdAt->diffInHours(now()) >= $hours;
    }

    private function resolveFoto(?string $foto): ?string
    {
        if (!$foto) {
            return null;
        }
        if (str_starts_with($foto, 'http://') || str_starts_with($foto, 'https://')) {
            return $foto;
        }
        return config('app.url') . $foto;
    }

    public function forgotPassword(Request $request)
    {
        $email = strtolower(trim($request->input('email', '')));

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Email válido requerido'], 400);
        }

        $user = User::where('username', $email)->first();

        if ($user && $user->status === 'active') {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            $token = Str::random(60);

            DB::table('password_reset_tokens')->insert([
                'email' => $email,
                'token' => $token,
                'created_at' => now(),
            ]);

            try {
                Mail::to($email)->send(new ResetPasswordMail($token));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json(['success' => true, 'message' => 'Si el email existe, recibirás un enlace para restablecer tu contraseña.']);
    }

    public function resetPassword(Request $request)
    {
        $token = $request->input('token');
        $password = $request->input('password');

        if (!$token || !$password) {
            return response()->json(['error' => 'Token y contraseña requeridos'], 400);
        }

        if (strlen($password) < 6) {
            return response()->json(['error' => 'La contraseña debe tener al menos 6 caracteres'], 400);
        }

        $resetToken = DB::table('password_reset_tokens')
            ->where('token', $token)
            ->where('created_at', '>=', now()->subMinutes(60))
            ->first();

        if (!$resetToken) {
            return response()->json(['error' => 'Token inválido o expirado'], 400);
        }

        $user = User::where('username', $resetToken->email)->first();

        if ($user) {
            $user->update(['password' => Hash::make($password)]);
        }

        DB::table('password_reset_tokens')->where('email', $resetToken->email)->delete();

        return response()->json(['success' => true]);
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

        return config('app.url') . "/storage/profiles/{$filename}";
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
