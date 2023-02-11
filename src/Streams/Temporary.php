<?php
declare(strict_types = 1);

namespace Innmind\Async\Stream\Streams;

use Innmind\Async\Stream\Bidirectional\Async;
use Innmind\Stream\{
    Capabilities,
    Bidirectional,
};
use Innmind\Mantle\Suspend;

final class Temporary implements Capabilities\Temporary
{
    private Capabilities\Temporary $synchronous;
    private Suspend $suspend;

    private function __construct(
        Capabilities\Temporary $synchronous,
        Suspend $suspend,
    ) {
        $this->synchronous = $synchronous;
        $this->suspend = $suspend;
    }

    /**
     * @internal
     */
    public static function of(
        Capabilities\Temporary $synchronous,
        Suspend $suspend,
    ): self {
        return new self($synchronous, $suspend);
    }

    public function new(): Bidirectional
    {
        return Async::of(
            $this->synchronous->new(),
            $this->suspend,
        );
    }
}
