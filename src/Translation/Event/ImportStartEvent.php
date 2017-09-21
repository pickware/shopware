<?php

namespace Shopware\Translation\Event;

use Symfony\Component\EventDispatcher\Event;

class ImportStartEvent extends Event
{
    const EVENT_NAME = 'translation.import.start';

    /**
     * @var int
     */
    private $count;

    /**
     * @var bool
     */
    private $truncateBeforeRun;

    public function __construct(int $count = 0, bool $truncateBeforeRun = false)
    {
        $this->count = $count;
        $this->truncateBeforeRun = $truncateBeforeRun;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function isTruncateBeforeRun(): bool
    {
        return $this->truncateBeforeRun;
    }
}
