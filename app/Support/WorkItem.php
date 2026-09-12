<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Program;
use Carbon\CarbonInterface;

class WorkItem
{
    public function __construct(
        public string $kind,
        public string $title,
        public ?CarbonInterface $startsAt,
        public ?string $location,
        public string $url,
        public string $badge,
        public bool $registrationOpen = false,
    ) {}

    public static function fromProgram(Program $program): self
    {
        return new self(
            kind: 'program',
            title: $program->title,
            startsAt: $program->starts_at,
            location: $program->location,
            url: route('programs.show', $program),
            badge: $program->type?->label() ?: 'Program',
        );
    }

    public static function fromEvent(Event $event): self
    {
        return new self(
            kind: 'event',
            title: $event->title,
            startsAt: $event->starts_at,
            location: $event->location,
            url: route('events.show', $event),
            badge: $event->registration_open ? 'Kayıt açık' : 'Program',
            registrationOpen: $event->registration_open,
        );
    }

    public function day(): string
    {
        return $this->startsAt?->format('d') ?? '—';
    }

    public function monthShort(): string
    {
        if (! $this->startsAt) {
            return '';
        }

        return mb_strtoupper(mb_substr($this->startsAt->translatedFormat('F'), 0, 3), 'UTF-8');
    }

    public function timeLabel(): string
    {
        return $this->startsAt?->translatedFormat('H:i') ?? 'Saat duyurulacak';
    }

    public function groupLabel(): string
    {
        return $this->startsAt?->translatedFormat('F Y') ?: 'Tarihi belirlenecek';
    }
}
