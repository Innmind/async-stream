<?php
declare(strict_types = 1);

namespace Innmind\Async\Stream\Streams;

use Innmind\Async\Stream\Writable\Async;
use Innmind\Stream\{
    Capabilities,
    Writable as Write,
};
use Innmind\Mantle\Suspend;
use Innmind\Url\Path;

final class Writable implements Capabilities\Writable
{
    private Capabilities\Writable $synchronous;
    private Suspend $suspend;

    private function __construct(
        Capabilities\Writable $synchronous,
        Suspend $suspend,
    ) {
        $this->synchronous = $synchronous;
        $this->suspend = $suspend;
    }

    /**
     * @internal
     */
    public static function of(
        Capabilities\Writable $synchronous,
        Suspend $suspend,
    ): self {
        return new self($synchronous, $suspend);
    }

    public function open(Path $path): Write
    {
        return Async::of(
            $this->synchronous->open($path),
            $this->suspend,
        );
    }

    public function acquire($resource): Write
    {
        return Async::of(
            $this->synchronous->acquire($resource),
            $this->suspend,
        );
    }
}
