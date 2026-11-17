<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Moderacion de cuentas: busqueda, suspension, reactivacion, cambio de
 * rol, notas internas y exportacion del registro de usuarios.
 */
class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $usuarios = $this->consulta($request)->paginate(12)->withQueryString();

        return view('admin.usuarios.index', [
            'usuarios' => $usuarios,
            'q' => (string) $request->query('q'),
            'rol' => (string) $request->query('rol'),
            'estado' => (string) $request->query('estado'),
            'totales' => [
                'todos' => User::count(),
                'voluntarios' => User::where('role', 'voluntario')->count(),
                'organizaciones' => User::where('role', 'organizacion')->count(),
                'bloqueados' => User::whereNotNull('blocked_at')->count(),
            ],
        ]);
    }

    public function show(User $user)
    {
        $user->load([
            'organizacion',
            'postulaciones.oportunidad',
        ]);

        return view('admin.usuarios.show', [
            'usuario' => $user,
            'reportes' => Reporte::with('reportadoPor:id,name')
                ->where('user_id', $user->id)
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    public function bloquear(Request $request, User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'No puedes suspender tu propia cuenta.');
        }

        if ($user->role === 'admin') {
            return back()->with('error', 'Por seguridad no se puede suspender a otro administrador.');
        }

        $datos = $request->validate([
            'motivo' => 'nullable|string|max:255',
        ]);

        $user->forceFill([
            'blocked_at' => now(),
            'blocked_reason' => $datos['motivo'] ?? null,
        ])->save();

        AuditLog::registrar('usuario.bloqueado', $user, $datos['motivo'] ?? null);

        return back()->with('status', "La cuenta de {$user->name} quedó suspendida.");
    }

    public function reactivar(User $user)
    {
        $user->forceFill([
            'blocked_at' => null,
            'blocked_reason' => null,
        ])->save();

        AuditLog::registrar('usuario.reactivado', $user);

        return back()->with('status', "La cuenta de {$user->name} volvió a estar activa.");
    }

    public function cambiarRol(Request $request, User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'No puedes cambiar tu propio rol.');
        }

        $datos = $request->validate([
            'role' => ['required', Rule::in(['voluntario', 'organizacion', 'admin'])],
        ]);

        if ($user->role === 'admin'
            && $datos['role'] !== 'admin'
            && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Debe existir al menos una cuenta administradora.');
        }

        $anterior = $user->role;
        $user->forceFill(['role' => $datos['role']])->save();

        // Si la cuenta pasa a ONG y todavia no tiene ficha, se crea en
        // estado pendiente para que aparezca en la cola de verificacion.
        if ($datos['role'] === 'organizacion' && ! $user->organizacion()->exists()) {
            $user->organizacion()->create([
                'nombre' => $user->name,
                'ruc' => $user->ruc,
                'sector' => $user->sector,
                'mision' => $user->mission,
                'verificada' => false,
            ]);
        }

        AuditLog::registrar('usuario.rol', $user, "{$anterior} → {$datos['role']}");

        return back()->with('status', "Rol actualizado a {$user->rolLegible()}.");
    }

    public function notas(Request $request, User $user)
    {
        $datos = $request->validate([
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $user->forceFill(['admin_notes' => $datos['admin_notes'] ?? null])->save();

        AuditLog::registrar('usuario.notas', $user);

        return back()->with('status', 'Notas internas guardadas.');
    }

    public function exportar(Request $request): StreamedResponse
    {
        $usuarios = $this->consulta($request)->get();

        return response()->streamDownload(function () use ($usuarios) {
            $salida = fopen('php://output', 'wb');
            fwrite($salida, "\xEF\xBB\xBF");

            fputcsv($salida, [
                'ID', 'Nombre', 'Correo', 'Rol', 'Estado', 'Región', 'Ciudad',
                'DNI', 'RUC', 'Universidad', 'Carrera', 'Registro',
            ]);

            foreach ($usuarios as $usuario) {
                fputcsv($salida, [
                    $usuario->id,
                    $usuario->name,
                    $usuario->email,
                    $usuario->rolLegible(),
                    $usuario->estaBloqueado() ? 'Suspendido' : 'Activo',
                    $usuario->region,
                    $usuario->city,
                    $usuario->dni,
                    $usuario->ruc,
                    $usuario->university,
                    $usuario->career,
                    $usuario->created_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($salida);
        }, 'voluntmatch-usuarios-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return Builder<User>
     */
    private function consulta(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $rol = (string) $request->query('rol');
        $estado = (string) $request->query('estado');

        return User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('dni', 'like', "%{$q}%")
                        ->orWhere('ruc', 'like', "%{$q}%")
                        ->orWhere('university', 'like', "%{$q}%");
                });
            })
            ->when(in_array($rol, ['voluntario', 'organizacion', 'admin'], true), fn ($query) => $query->where('role', $rol))
            ->when($estado === 'activos', fn ($query) => $query->whereNull('blocked_at'))
            ->when($estado === 'bloqueados', fn ($query) => $query->whereNotNull('blocked_at'))
            ->latest();
    }
}
