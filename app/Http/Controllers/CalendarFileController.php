<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Descarga de invitaciones .ics generadas por el canal de calendario.
 * Solo se sirve con una URL firmada y temporal: el archivo no es público.
 */
class CalendarFileController extends Controller
{
    public function show(Request $request, string $archivo): BinaryFileResponse|Response
    {
        if (! preg_match('/^[0-9a-f-]{36}$/i', $archivo)) {
            abort(404);
        }

        $ruta = 'calendar/'.$archivo.'.ics';

        if (! Storage::disk('local')->exists($ruta)) {
            abort(404);
        }

        return response()->download(
            Storage::disk('local')->path($ruta),
            'invitacion-voluntmatch.ics',
            ['Content-Type' => 'text/calendar; charset=utf-8']
        );
    }
}
