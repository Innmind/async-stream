<?php
declare(strict_types = 1);

namespace Innmind\Async\Stream\Writable;

use Innmind\Stream\{
    Writable,
    Stream\Position,
    Stream\Position\Mode,
};
use Innmind\Mantle\Suspend;
use Innmind\Immutable\{
    Maybe,
    Either,
    Str,
};

final class Async implements Writable
{
    private Writable $synchronous;
    private Suspend $suspend;

    private function __construct(Writable $synchronous, Suspend $suspend)
    {
        $this->synchronous = $synchronous;
        $this->suspend = $suspend;
    }

    /**
     * @internal
     */
    public static function of(Writable $synchronous, Suspend $suspend): self
    {
        return new self($synchronous, $suspend);
    }

    /**
     * @psalm-mutation-free
     */
    public function resource()
    {
        return $this->synchronous->resource();
    }

    /** @psalm-suppress InvalidReturnType */
    public function write(Str $data): Either
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this
            ->synchronous
            ->write($data)
            ->map(function($synchronous) {
                ($this->suspend)();

                return new self($synchronous, $this->suspend);
            });
    }

    public function position(): Position
    {
        return $this->synchronous->position();
    }

    /** @psalm-suppress InvalidReturnType */
    public function seek(Position $position, Mode $mode = null): Either
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->synchronous->seek($position, $mode)->map(fn() => $this);
    }

    /** @psalm-suppress InvalidReturnType */
    public function rewind(): Either
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->synchronous->rewind()->map(fn() => $this);
    }

    /**
     * @psalm-mutation-free
     */
    public function end(): bool
    {
        return $this->synchronous->end();
    }

    /**
     * @psalm-mutation-free
     */
    public function size(): Maybe
    {
        return $this->synchronous->size();
    }

    public function close(): Either
    {
        return $this->synchronous->close();
    }

    /**
     * @psalm-mutation-free
     */
    public function closed(): bool
    {
        return $this->synchronous->closed();
    }
}
