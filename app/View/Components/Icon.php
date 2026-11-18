<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Icon extends Component
{
    private const PATHS = [
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6" />',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2" /><path d="M8 3v4M16 3v4M3 11h18" />',
        'video' => '<path d="m22 8-6 4 6 4V8Z" /><rect x="2" y="6" width="14" height="12" rx="2" />',
        'check' => '<path d="m5 12 4 4L19 6" />',
        'chevron' => '<path d="m8 10 4 4 4-4" />',
        'close' => '<path d="m6 6 12 12M18 6 6 18" />',
        'download' => '<path d="M12 4v11M7 11l5 5 5-5M5 20h14" />',
        'heart' => '<path d="M20.8 4.6a5.4 5.4 0 0 0-7.6 0L12 5.8l-1.2-1.2a5.4 5.4 0 0 0-7.6 7.6l1.2 1.2L12 21l7.6-7.6 1.2-1.2a5.4 5.4 0 0 0 0-7.6Z" />',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16" />',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" />',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.6a2 2 0 0 1-.5 2.1L8 9.7a16 16 0 0 0 6.3 6.3l1.3-1.3a2 2 0 0 1 2.1-.5c.8.3 1.7.6 2.6.7a2 2 0 0 1 1.7 2Z" />',
        'pin' => '<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z" /><circle cx="12" cy="10" r="2.5" />',
        'search' => '<circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />',
        'globe' => '<circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18Z" />',
        'moon' => '<path d="M20.5 15.5A9 9 0 0 1 8.5 3.5a9 9 0 1 0 12 12Z" />',
        'pause' => '<path d="M9 6v12M15 6v12" />',
        'play' => '<path d="m8 5 11 7-11 7Z" />',
        'spark' => '<path d="m12 3 1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3ZM19 16l.7 2.3L22 19l-2.3.7L19 22l-.7-2.3L16 19l2.3-.7L19 16Z" />',
        'star' => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z" />',
        'clock' => '<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />',
        'sun' => '<circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />',
        'chat' => '<path d="M21 11.5a8.4 8.4 0 0 1-8.5 8.5H7l-4 2.5V11.5a8.5 8.5 0 0 1 8.5-8.5h1A8.4 8.4 0 0 1 21 11.5Z" /><path d="M8 10h8M8 14h5" />',
        'send' => '<path d="m22 2-7 20-4-9-9-4Z" /><path d="M22 2 11 13" />',
    ];

    public string $name;

    public int $size;

    public function __construct(string $name, int $size = 20)
    {
        $this->name = $name;
        $this->size = $size;
    }

    public function path(): string
    {
        return self::PATHS[$this->name] ?? '';
    }

    public function render()
    {
        return view('components.icon');
    }
}
