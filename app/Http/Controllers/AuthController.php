<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\User;
use App\Support\ChatDemo;
use App\Support\Peru;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /** Opciones que los formularios de registro usan en sus selects. */
    private function camposComunes(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|regex:/^9\d{8}$/',
            'region' => ['nullable', Rule::in(Peru::REGIONES)],
            'terms' => 'accepted',
        ];
    }

    public function index()
    {
        return view('modules/auth/login');
    }

    public function registroVoluntario()
    {
        return view('modules/auth/registro-voluntario', [
            'regiones' => Peru::REGIONES,
            'ciclos' => Peru::CICLOS,
            'disponibilidades' => Peru::DISPONIBILIDADES,
            'intereses' => Peru::INTERESES,
            'universidades' => Peru::UNIVERSIDADES,
        ]);
    }

    public function registroOrganizacion()
    {
        return view('modules/auth/registro-organizacion', [
            'regiones' => Peru::REGIONES,
            'tipos' => Peru::TIPOS_ORGANIZACION,
            'sectores' => Peru::SECTORES,
        ]);
    }

    public function registrarVoluntario(Request $request)
    {
        return $this->crearCuenta($request, 'voluntario');
    }

    public function registrarOrganizacion(Request $request)
    {
        return $this->crearCuenta($request, 'organizacion');
    }

    /**
     * Crea la cuenta con el rol que fija el controlador segun la ruta usada.
     * Nunca se lee un campo "role" del formulario.
     */
    private function crearCuenta(Request $request, string $role)
    {
        $rules = $role === 'organizacion'
            ? $this->reglasOrganizacion()
            : $this->reglasVoluntario();

        // El registro de ONG usa su propia bolsa de errores para no mezclar
        // sus mensajes con el formulario de estudiantes cuando la ventana
        // de registro convive con la página de registro de voluntarios.
        $datos = $role === 'organizacion'
            ? $request->validateWithBag('ong', $rules + $this->camposComunes(), $this->mensajes())
            : $request->validate($rules + $this->camposComunes(), $this->mensajes());

        $user = new User;
        $user->fill($datos);
        $user->role = $role;
        $user->city = $role === 'organizacion' ? ($datos['city'] ?? null) : null;
        $user->save();

        // La cuenta de ONG nace con su ficha de organizacion pendiente de
        // verificacion: el panel de administracion la revisa y aprueba.
        if ($role === 'organizacion') {
            $user->organizacion()->create([
                'nombre' => $user->name,
                'ruc' => $user->ruc,
                'sector' => $user->sector,
                'mision' => $user->mission,
                'verificada' => false,
            ]);
        }

        return redirect()->route('login')->with(
            'success',
            $role === 'organizacion'
                ? '¡Organización registrada! Tu cuenta está lista, ya puedes iniciar sesión.'
                : '¡Cuenta creada con éxito! Ya puedes iniciar sesión.'
        );
    }

    private function reglasVoluntario(): array
    {
        return [
            'dni' => ['nullable', 'digits:8', Rule::unique('users', 'dni')],
            'university' => 'nullable|string|max:150',
            'career' => 'nullable|string|max:150',
            'cycle' => ['nullable', Rule::in(Peru::CICLOS)],
            'availability' => ['nullable', Rule::in(array_keys(Peru::DISPONIBILIDADES))],
            'city' => 'nullable|string|max:100',
            'interests' => 'nullable|array|max:6',
            'interests.*' => ['string', Rule::in(Peru::INTERESES)],
        ];
    }

    private function reglasOrganizacion(): array
    {
        return [
            'phone' => 'required|string|regex:/^9\d{8}$/',
            'region' => ['required', Rule::in(Peru::REGIONES)],
            'ruc' => [
                'required',
                'digits:11',
                'regex:/^(10|15|17|20)\d{9}$/',
                Rule::unique('users', 'ruc'),
                function (string $attribute, mixed $value, Closure $fail) {
                    // Verificacion del digito verificador (modulo 11) para
                    // rechazar RUCs inventados que cumplen el formato.
                    if (is_string($value) && $value !== '' && ! Peru::rucValido($value)) {
                        $fail('El RUC ingresado no pasa la verificación del dígito verificador. Revísalo con tu ficha RUC.');
                    }
                },
            ],
            'org_type' => ['nullable', Rule::in(Peru::TIPOS_ORGANIZACION)],
            'legal_rep' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'sector' => ['required', Rule::in(Peru::SECTORES)],
            'mission' => 'nullable|string|min:30|max:1000',
            'website' => 'nullable|string|max:255|url',
        ];
    }

    private function mensajes(): array
    {
        return [
            'name.required' => 'Ingresa tu nombre completo o el de la organización.',
            'email.required' => 'Ingresa un correo electrónico.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ya existe una cuenta registrada con ese correo.',
            'password.required' => 'Ingresa una contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'phone.required' => 'Ingresa un número de celular de contacto.',
            'phone.regex' => 'El celular debe tener 9 dígitos y empezar con 9 (ej. 987654321).',
            'dni.required' => 'Ingresa tu DNI.',
            'dni.digits' => 'El DNI debe tener exactamente 8 dígitos.',
            'dni.unique' => 'Ya existe una cuenta registrada con ese DNI.',
            'ruc.required' => 'Ingresa el RUC de la organización.',
            'ruc.digits' => 'El RUC debe tener exactamente 11 dígitos.',
            'ruc.regex' => 'El RUC debe empezar con 10, 15, 17 o 20.',
            'ruc.unique' => 'Ya existe una organización registrada con ese RUC.',
            'university.required' => 'Indica tu universidad o instituto.',
            'career.required' => 'Indica la carrera que estudias.',
            'cycle.required' => 'Indica el ciclo en el que te encuentras.',
            'availability.required' => 'Indica cuántas horas por semana puedes aportar.',
            'region.required' => 'Selecciona tu región.',
            'region.in' => 'Selecciona una región válida del Perú.',
            'city.required' => 'Indica la ciudad o distrito.',
            'legal_rep.required' => 'Indica el nombre del representante legal.',
            'address.required' => 'Indica la dirección de la organización.',
            'sector.required' => 'Selecciona la causa o sector principal.',
            'mission.required' => 'Cuéntanos brevemente la misión de la organización.',
            'mission.min' => 'La misión debe tener al menos 30 caracteres.',
            'org_type.required' => 'Selecciona el tipo de organización.',
            'website.url' => 'El sitio web debe ser una dirección válida (ej. https://mi-ong.org).',
            'terms.accepted' => 'Debes aceptar los términos y la política de privacidad.',
        ];
    }

    public function logear(Request $request)
    {
        $credenciales = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Una cuenta suspendida no puede volver a entrar aunque acierte
        // la contrasenia: el bloqueo lo decide la administracion.
        $cuenta = User::where('email', $credenciales['email'])->first();

        if ($cuenta && $cuenta->estaBloqueado()) {
            return back()->with('error', 'Tu cuenta está suspendida. Escríbenos desde la página de contacto para revisar tu caso.');
        }

        if (Auth::attempt($credenciales)) {
            $request->session()->regenerate();
            $request->user()->forceFill(['last_login_at' => now()])->save();

            return redirect()->route('home');
        }

        return back()->with('error', 'El correo electrónico o la contraseña son incorrectos.');
    }

    public function logout(Request $request)
    {
        Session::flush();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('index');
    }

    public function home()
    {
        return match (auth()->user()->role) {
            'admin' => redirect()->route('dashboard-admin'),
            'organizacion' => redirect()->route('dashboard-organizacion'),
            default => view('dashboard/voluntario', [
                'oportunidades' => OportunidadController::demo(),
                'conversaciones' => ChatDemo::conversaciones(),
                'noLeidos' => ChatDemo::noLeidos(),
                'anuncios' => Anuncio::activos()
                    ->whereIn('audiencia', ['todos', 'voluntario'])
                    ->latest()
                    ->limit(3)
                    ->get(),
            ]),
        };
    }
}
