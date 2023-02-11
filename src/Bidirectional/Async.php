<?php
declare(strict_types = 1);

namespace Innmind\Async\Stream\Bidirectional;

use Innmind\Async\Stream\{
    Readable\Async as Readable,
    Writable\Async as Writable,
};
use Innmind\Stream\{
    Bidirectional,
    Stream\Position,
    Stream\Position\Mode,
};
use Innmind\Mantle\Suspend;
use Innmind\Immutable\{
    Maybe,
    Either,
    Str,
};

final class Async implements Bidirectional
{
    private Readable $readable;
    private Writable $writable;

    private function __construct(
        Readable $readable,
        Writable $writable,
    ) {
        $this->readable = $readable;
        $this->writable = $writable;
    }

    /**
     * @internal
     */
    public static function of(Bidirectional $synchronous, Suspend $suspend): self
    {
        return new self(
            Readable::of($synchronous, $suspend),
            Writable::of($synchronous, $suspend),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function resource()
    {
        return $this->readable->resource();
    }

    public function read(int $length = null): Maybe
    {
        return $this->readable->read($length);
    }

    public function readLine(): Maybe
    {
        return $this->readable->readLine();
    }

    /** @psalm-suppress InvalidReturnType */
    public function write(Str $data): Either
    {
        /**
         * @psalm-suppress InvalidReturnStatement
         * @psalm-suppress ArgumentTypeCoercion
         */
        return $this
            ->writable
            ->write($data)
            ->map(fn($writable) => new self($this->readable, $writable));
    }

    public function position(): Position
    {
        return $this->readable->position();
    }

    /** @psalm-suppress InvalidReturnType */
    public function seek(Position $position, Mode $mode = null): Either
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->readable->seek($position, $mode)->map(fn() => $this);
    }

    /** @psalm-suppress InvalidReturnType */
    public function rewind(): Either
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->readable->rewind()->map(fn() => $this);
    }

    /**
     * @psalm-mutation-free
     */
    public function end(): bool
    {
        return $this->readable->end();
    }

    /**
     * @psalm-mutation-free
     */
    public function size(): Maybe
    {
        return $this->readable->size();
    }

    public function close(): Either
    {
        return $this->readable->close();
    }

    /**
     * @psalm-mutation-free
     */
    public function closed(): bool
    {
        return $this->readable->closed();
    }

    public function toString(): Maybe
    {
        return $this->readable->toString();
    }
}
