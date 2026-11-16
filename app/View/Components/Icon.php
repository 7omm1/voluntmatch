<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Icon extends Component
{
    private const PATHS = [
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6" />',
        'check' => '<path d="m5 12 4 4L19 6" />',
        'chevron' => '<path d="m8 10 4 4 4-4" />',
        'close' => '<path d="m6 6 12 12M18 6 6 18" />',
        'heart' => '<path d="M20.8 4.6a5.4 5.4 0 0 0-7.6 0L12 5.8l-1.2-1.2a5.4 5.4 0 0 0-7.6 7.6l1.2 1.2L12 21l7.6-7.6 1.2-1.2a5.4 5.4 0 0 0 0-7.6Z" />',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16" />',
        'moon' => '<path d="M20.5 15.5A9 9 0 0 1 8.5 3.5a9 9 0 1 0 12 12Z" />',
        'pause' => '<path d="M9 6v12M15 6v12" />',
        'play' => '<path d="m8 5 11 7-11 7Z" />',
        'spark' => '<path d="m12 3 1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3ZM19 16l.7 2.3L22 19l-2.3.7L19 22l-.7-2.3L16 19l2.3-.7L19 16Z" />',
        'sun' => '<circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />',
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
