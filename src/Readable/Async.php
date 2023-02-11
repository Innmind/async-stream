<?php
declare(strict_types = 1);

namespace Innmind\Async\Stream\Readable;

use Innmind\Stream\{
    Readable,
    Stream\Position,
    Stream\Position\Mode,
};
use Innmind\Mantle\Suspend;
use Innmind\Immutable\{
    Maybe,
    Either,
};

final class Async implements Readable
{
    private Readable $synchronous;
    private Suspend $suspend;

    private function __construct(Readable $synchronous, Suspend $suspend)
    {
        $this->synchronous = $synchronous;
        $this->suspend = $suspend;
    }

    /**
     * @internal
     */
    public static function of(Readable $synchronous, Suspend $suspend): self
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

    public function read(int $length = null): Maybe
    {
        // no suspension when reading as we assume the stream is "ready" and the
        // user can advance the its task synchronously
        // in case of reading chunk by chunk the task will be suspend by the
        // async writable stream when writing each chunk
        // @see Innmind\Async\Stream\Writable\Async
        return $this->synchronous->read($length);
    }

    public function readLine(): Maybe
    {
        return $this
            ->synchronous
            ->readLine()
            ->map(function($line) {
                // we suspend after reading a line as we assume the user is
                // reading a file line by line and the file could be very large
                ($this->suspend)();

                return $line;
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

    public function toString(): Maybe
    {
        // no suspension here for now as the use case is not clear yet
        return $this->synchronous->toString();
    }
}
