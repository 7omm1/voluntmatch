<?php

namespace App\Services\Integrations\Drivers;

use App\Services\Integrations\Contracts\IntegrationDriver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

/**
 * Calendario local: genera una invitación iCalendar (.ics) firmada.
 *
 * Funciona sin credenciales de Google/Microsoft y el archivo se descarga
 * con una URL temporal firmada, así que no queda público.
 */
class CalendarIcsDriver implements IntegrationDriver
{
    public function channel(): string
    {
        return 'calendar';
    }

    public function driver(): string
    {
        return 'ics';
    }

    public function configured(): bool
    {
        return true;
    }

    public function deliver(string $action, array $payload, array $context = []): array
    {
        $title = trim((string) ($payload['title'] ?? 'Evento VoluntMatch'));
        $start = $payload['start'] ?? null;

        if ($title === '' || ! $start) {
            return ['ok' => false, 'summary' => 'El evento necesita título y fecha de inicio.'];
        }

        $timezone = (string) config('integrations.calendar.timezone', 'America/Lima');
        $duration = max(5, min(480, (int) ($payload['duration_minutes'] ?? 60)));

        try {
            $inicio = Carbon::parse((string) $start, $timezone);
            $fin = $inicio->copy()->addMinutes($duration);
            $uid = (string) Str::uuid();
            $attendee = (string) ($payload['attendee'] ?? '');
            $organizer = (string) ($payload['organizer'] ?? config('integrations.mail.from_address'));
            $description = (string) ($payload['description'] ?? '');

            $lines = [
                'BEGIN:VCALENDAR',
                'VERSION:2.0',
                'PRODID:-//VoluntMatch//Asistente//ES',
                'CALSCALE:GREGORIAN',
                'METHOD:REQUEST',
                'BEGIN:VEVENT',
                'UID:'.$uid,
                'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
                'DTSTART:'.$inicio->copy()->utc()->format('Ymd\THis\Z'),
                'DTEND:'.$fin->copy()->utc()->format('Ymd\THis\Z'),
                'SUMMARY:'.$this->escape($title),
                'DESCRIPTION:'.$this->escape($description),
                'ORGANIZER;CN=VoluntMatch:mailto:'.$organizer,
            ];

            if (filter_var($attendee, FILTER_VALIDATE_EMAIL)) {
                $lines[] = 'ATTENDEE;CN='.$this->escape($attendee).';RSVP=TRUE:mailto:'.$attendee;
            }

            $lines = array_merge($lines, ['STATUS:CONFIRMED', 'END:VEVENT', 'END:VCALENDAR']);

            Storage::disk('local')->put(
                'calendar/'.$uid.'.ics',
                implode("\r\n", $lines)."\r\n"
            );

            $url = URL::temporarySignedRoute(
                'integraciones.calendario',
                now()->addDays(max(1, (int) config('integrations.calendar.link_days', 7))),
                ['archivo' => $uid]
            );

            return [
                'ok' => true,
                'summary' => sprintf(
                    'Evento «%s» agendado para el %s a las %s (%s).',
                    $title,
                    $inicio->translatedFormat('d \d\e F'),
                    $inicio->format('H:i'),
                    $timezone
                ),
                'data' => [
                    'uid' => $uid,
                    'start' => $inicio->toIso8601String(),
                    'end' => $fin->toIso8601String(),
                    'timezone' => $timezone,
                    'invitation_url' => $url,
                ],
                'target' => $attendee !== '' ? $attendee : null,
            ];
        } catch (Throwable $exception) {
            return ['ok' => false, 'summary' => 'No pude generar la invitación de calendario.'];
        }
    }

    private function escape(string $value): string
    {
        $value = str_replace(['\\', "\r\n", "\n"], ['\\\\', '\\n', '\\n'], $value);

        return str_replace([',', ';'], ['\\,', '\\;'], $value);
    }
}
