<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->role === 'organizacion', 403);

        return view('dashboard/organizacion', [
            'oportunidades' => OportunidadController::demo(),
        ]);
    }

    public function admin()
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        return view('dashboard/admin', [
            'oportunidades' => OportunidadController::demo(),
        ]);
    }
}
