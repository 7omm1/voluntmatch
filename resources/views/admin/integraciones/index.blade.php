@extends('layouts.admin')

@section('title', 'Integraciones — VoluntMatch')
@section('panel-seccion', 'INTEGRACIONES')
@section('panel-titulo', 'El asistente conectado a tus sistemas.')

@section('admin-content')
    @if (session('api_token_nuevo'))
        <div class="dashboard-card admin-note-box">
            <b>Token nuevo (cópialo ahora, no se volverá a mostrar)</b>
            <p><code style="word-break:break-all">{{ session('api_token_nuevo') }}</code></p>
            <small>Úsalo como <code>Authorization: Bearer …</code> en /api/v1/assistant/messages, /api/v1/tools o /api/v1/mcp.</small>
        </div>
    @endif

    <div class="metric-grid metric-grid-four">
        <article>
            <span><x-icon name="check" /></span>
            <strong>{{ number_format($resumen['enviadas'], 0, ',', '.') }}</strong>
            <p>Entregas correctas</p>
            <small>Correo, calendario, CRM, soporte y avisos</small>
        </article>
        <article>
            <span><x-icon name="close" /></span>
            <strong>{{ number_format($resumen['fallidas'], 0, ',', '.') }}</strong>
            <p>Entregas fallidas</p>
            <small>Revisa la bitácora y la configuración</small>
        </article>
        <article>
            <span><x-icon name="users" /></span>
            <strong>{{ number_format($resumen['contactos'], 0, ',', '.') }}</strong>
            <p>Contactos en el CRM</p>
            <small>Registrados por el asistente o a mano</small>
        </article>
        <article>
            <span><x-icon name="spark" /></span>
            <strong>{{ number_format($resumen['tokens'], 0, ',', '.') }}</strong>
            <p>Tokens API vigentes</p>
            <small>Con rol, scopes y límites propios</small>
        </article>
    </div>

    <section class="dashboard-card">
        <div class="card-heading">
            <div>
                <p>CANALES</p>
                <h3>Estado de las integraciones</h3>
            </div>
            <span>Configuración por variables de entorno</span>
        </div>

        <div class="admin-org-card">
            @foreach ($estado as $canal)
                <div class="admin-row">
                    <span class="list-icon"><x-icon name="spark" /></span>
                    <span class="admin-row-main">
                        <b>{{ $canal['label'] }}</b>
                        <small>{{ $canal['detail'] }}</small>
                    </span>
                    <span class="status {{ $canal['configured'] ? 'activo' : 'cerrado' }}">
                        {{ $canal['configured'] ? 'Operativo' : 'Sin configurar' }}
                    </span>
                    <form method="POST" action="{{ route('admin.integraciones.test') }}">
                        @csrf
                        <input type="hidden" name="channel" value="{{ $canal['channel'] }}">
                        <button class="admin-btn tiny ghost" type="submit">Probar</button>
                    </form>
                </div>
            @endforeach
        </div>

        <p class="admin-empty">
            Los drivers HTTP se configuran en <code>.env</code>: <code>INTEGRATION_CALENDAR_URL</code>,
            <code>INTEGRATION_CRM_URL</code>, <code>INTEGRATION_HELPDESK_URL</code> y
            <code>INTEGRATION_NOTIFICATIONS_WEBHOOK_URL</code>. El correo usa las variables <code>MAIL_*</code>.
        </p>
    </section>

    <section class="dashboard-card">
        <div class="card-heading">
            <div>
                <p>API Y MCP</p>
                <h3>Clientes y tokens</h3>
            </div>
            <span>REST + JSON-RPC 2.0</span>
        </div>

        <details class="dashboard-card admin-create-card">
            <summary>+ Crear token para un sistema externo</summary>
            <form class="admin-form-grid" method="POST" action="{{ route('admin.integraciones.clientes.store') }}">
                @csrf
                <label>Nombre
                    <input type="text" name="name" required maxlength="120" placeholder="Ej. Mesa de ayuda Zendesk">
                </label>
                <label>Rol (audiencia)
                    <select name="role" required>
                        <option value="public">Público (solo datos públicos)</option>
                        <option value="voluntario">Voluntario</option>
                        <option value="organizacion">Organización</option>
                        <option value="admin">Administración</option>
                    </select>
                </label>
                <label>Cuenta asociada (obligatoria si el rol no es público)
                    <select name="user_id">
                        <option value="">Sin cuenta</option>
                        @foreach (\App\Models\User::orderBy('name')->get(['id', 'name', 'email', 'role']) as $cuenta)
                            <option value="{{ $cuenta->id }}">
                                {{ $cuenta->name }} · {{ $cuenta->email }} ({{ $cuenta->role }})
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>Caducidad en días (opcional)
                    <input type="number" name="expires_days" min="1" max="365" placeholder="90">
                </label>
                <fieldset class="span-two">
                    <legend>Scopes</legend>
                    @foreach (\App\Models\ApiClient::SCOPES as $scope)
                        <label class="admin-check">
                            <input type="checkbox" name="scopes[]" value="{{ $scope }}" checked>
                            {{ $scope }}
                        </label>
                    @endforeach
                </fieldset>
                <button class="admin-btn" type="submit">Generar token</button>
            </form>
        </details>

        @forelse ($clientes as $cliente)
            <article class="admin-org-card">
                <div class="admin-row">
                    <span class="list-icon"><x-icon name="users" /></span>
                    <span class="admin-row-main">
                        <b>{{ $cliente->name }}</b>
                        <small>
                            <code>{{ $cliente->key_prefix }}…</code> · rol {{ $cliente->audience() }}
                            @if ($cliente->user) · {{ $cliente->user->email }} @endif
                            · {{ $cliente->created_at?->format('d/m/Y') }}
                            @if ($cliente->last_used_at) · último uso {{ $cliente->last_used_at->diffForHumans() }} @endif
                        </small>
                    </span>
                    <span class="status {{ $cliente->estaVigente() ? 'activo' : 'cerrado' }}">
                        {{ $cliente->estaVigente() ? 'Vigente' : 'Revocado' }}
                    </span>
                    @if ($cliente->estaVigente())
                        <form method="POST" action="{{ route('admin.integraciones.clientes.revoke', $cliente) }}">
                            @csrf
                            @method('patch')
                            <button class="admin-btn tiny danger" type="submit">Revocar</button>
                        </form>
                    @endif
                </div>
                <p class="admin-org-mission">Scopes: {{ implode(', ', $cliente->scopes ?? []) }}</p>
            </article>
        @empty
            <p class="admin-empty">Todavía no hay tokens. Crea uno para conectar un sistema externo.</p>
        @endforelse
    </section>

    <section class="dashboard-card">
        <div class="card-heading">
            <div>
                <p>CRM</p>
                <h3>Últimos contactos</h3>
            </div>
            <span>{{ $resumen['contactos'] }} en total</span>
        </div>

        @forelse ($contactos as $contacto)
            <div class="admin-row">
                <span class="list-icon"><x-icon name="users" /></span>
                <span class="admin-row-main">
                    <b>{{ $contacto->nombre }}</b>
                    <small>{{ $contacto->email }} · {{ $contacto->etapaLegible() }} · {{ $contacto->created_at?->format('d/m/Y') }}</small>
                </span>
                <span class="status en_proceso">{{ $contacto->etapa }}</span>
            </div>
            <details class="admin-details">
                <summary>Actualizar etapa o notas</summary>
                <form class="admin-form-grid" method="POST" action="{{ route('admin.integraciones.contactos.update', $contacto) }}">
                    @csrf
                    @method('patch')
                    <label>Etapa
                        <select name="etapa" required>
                            @foreach (\App\Models\CrmContact::ETAPAS as $etapa)
                                <option value="{{ $etapa }}" @selected($contacto->etapa === $etapa)>{{ ucfirst($etapa) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="span-two">Notas
                        <textarea name="notas" rows="3" maxlength="1500">{{ $contacto->notas }}</textarea>
                    </label>
                    <button class="admin-btn tiny" type="submit">Guardar contacto</button>
                </form>
            </details>
        @empty
            <p class="admin-empty">Sin contactos todavía. Prueba el canal CRM o pide al asistente registrar uno.</p>
        @endforelse
    </section>

    <section class="dashboard-card">
        <div class="card-heading">
            <div>
                <p>BITÁCORA</p>
                <h3>Últimas entregas</h3>
            </div>
            <span>Sin cuerpos completos ni credenciales</span>
        </div>

        @forelse ($entregas as $entrega)
            <div class="admin-row">
                <span class="list-icon"><x-icon name="clock" /></span>
                <span class="admin-row-main">
                    <b>{{ ucfirst($entrega->channel) }} · {{ $entrega->action }}</b>
                    <small>
                        {{ $entrega->target ?? 'sin destino' }}
                        · driver {{ $entrega->driver }}
                        · {{ $entrega->user?->name ?? $entrega->apiClient?->name ?? 'sistema' }}
                        · {{ $entrega->created_at?->format('d/m/Y H:i') }}
                    </small>
                </span>
                <span class="status {{ $entrega->status === 'sent' ? 'activo' : 'cerrado' }}">
                    {{ $entrega->estadoLegible() }}
                </span>
            </div>
        @empty
            <p class="admin-empty">Aún no se registraron entregas.</p>
        @endforelse
    </section>
@endsection
