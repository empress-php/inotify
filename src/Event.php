<?php

namespace Empress\Inotify;

class Event
{
    private int $descriptor;
    private int $mask;
    private int $cookie;
    private string $name;

    public function __construct(
        int $descriptor,
        int $mask,
        int $cookie,
        string $name
    ) {
        $this->descriptor = $descriptor;
        $this->mask = $mask;
        $this->cookie = $cookie;
        $this->name = $name;
    }

    public static function create(array $eventData): self
    {
        return new self(
            $eventData['wd'],
            $eventData['mask'],
            $eventData['cookie'],
            $eventData['name']
        );
    }

    public function getDescriptor(): int
    {
        return $this->descriptor;
    }

    public function getMask(): int
    {
        return $this->mask;
    }

    public function getCookie(): int
    {
        return $this->cookie;
    }

    public function getName(): string
    {
        return $this->name;
    }
}