<?php
declare(strict_types = 1);

namespace Innmind\Async\Stream\Streams;

use Innmind\Async\Stream\Readable\Async;
use Innmind\Stream\{
    Capabilities,
    Readable as Read,
};
use Innmind\Mantle\Suspend;
use Innmind\Url\Path;

final class Readable implements Capabilities\Readable
{
    private Capabilities\Readable $synchronous;
    private Suspend $suspend;

    private function __construct(
        Capabilities\Readable $synchronous,
        Suspend $suspend,
    ) {
        $this->synchronous = $synchronous;
        $this->suspend = $suspend;
    }

    /**
     * @internal
     */
    public static function of(
        Capabilities\Readable $synchronous,
        Suspend $suspend,
    ): self {
        return new self($synchronous, $suspend);
    }

    public function open(Path $path): Read
    {
        return Async::of(
            $this->synchronous->open($path),
            $this->suspend,
        );
    }

    public function acquire($resource): Read
    {
        return Async::of(
            $this->synchronous->acquire($resource),
            $this->suspend,
        );
    }
}
