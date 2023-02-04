<?php
declare(strict_types = 1);

namespace Innmind\Async\Stream;

use Innmind\Async\Stream\Streams\{
    Watch,
    Writable,
    Readable,
    Temporary,
};
use Innmind\Stream\Capabilities;
use Innmind\Mantle\Suspend;
use Innmind\TimeContinuum\Clock;

final class Streams implements Capabilities
{
    private Capabilities $synchronous;
    private Suspend $suspend;
    private Clock $clock;

    private function __construct(
        Capabilities $synchronous,
        Suspend $suspend,
        Clock $clock,
    ) {
        $this->synchronous = $synchronous;
        $this->suspend = $suspend;
        $this->clock = $clock;
    }

    public static function of(
        Capabilities $synchronous,
        Suspend $suspend,
        Clock $clock,
    ): self {
        return new self($synchronous, $suspend, $clock);
    }

    public function temporary(): Capabilities\Temporary
    {
        return Temporary::of(
            $this->synchronous->temporary(),
            $this->suspend,
        );
    }

    public function readable(): Capabilities\Readable
    {
        return Readable::of(
            $this->synchronous->readable(),
            $this->suspend,
        );
    }

    public function writable(): Capabilities\Writable
    {
        return Writable::of(
            $this->synchronous->writable(),
            $this->suspend,
        );
    }

    public function watch(): Capabilities\Watch
    {
        return Watch::of(
            $this->synchronous->watch(),
            $this->suspend,
            $this->clock,
        );
    }
}
