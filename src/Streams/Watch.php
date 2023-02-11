<?php
declare(strict_types = 1);

namespace Innmind\Async\Stream\Streams;

use Innmind\Async\Stream\Watch\Async;
use Innmind\Stream\{
    Capabilities,
    Watch as WatchInterface,
};
use Innmind\TimeContinuum\{
    Clock,
    ElapsedPeriod,
};
use Innmind\Mantle\Suspend;

final class Watch implements Capabilities\Watch
{
    private Capabilities\Watch $synchronous;
    private Suspend $suspend;
    private Clock $clock;

    private function __construct(
        Capabilities\Watch $synchronous,
        Suspend $suspend,
        Clock $clock,
    ) {
        $this->synchronous = $synchronous;
        $this->suspend = $suspend;
        $this->clock = $clock;
    }

    /**
     * @internal
     */
    public static function of(
        Capabilities\Watch $synchronous,
        Suspend $suspend,
        Clock $clock,
    ): self {
        return new self($synchronous, $suspend, $clock);
    }

    public function timeoutAfter(ElapsedPeriod $timeout): WatchInterface
    {
        return Async::timeoutAfter(
            $this->synchronous,
            $this->suspend,
            $this->clock,
            $timeout,
        );
    }

    public function waitForever(): WatchInterface
    {
        return Async::waitForever($this->synchronous, $this->suspend);
    }
}
