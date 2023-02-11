<?php
declare(strict_types = 1);

namespace Innmind\Async\Stream\Watch;

use Innmind\Stream\{
    Capabilities,
    Watch,
    Watch\Ready,
    Readable,
    Writable,
    Stream,
};
use Innmind\Mantle\Suspend;
use Innmind\TimeContinuum\{
    ElapsedPeriod as ElapsedPeriodInterface,
    Clock,
    PointInTime,
    Earth\ElapsedPeriod,
};
use Innmind\Immutable\Maybe;

final class Async implements Watch
{
    private Watch $synchronous;
    private Suspend $suspend;
    /** @var Maybe<array{Clock, ElapsedPeriodInterface}> */
    private Maybe $timeout;

    /**
     * @param Maybe<array{Clock, ElapsedPeriodInterface}> $timeout
     */
    private function __construct(
        Watch $synchronous,
        Suspend $suspend,
        Maybe $timeout,
    ) {
        $this->synchronous = $synchronous;
        $this->suspend = $suspend;
        $this->timeout = $timeout;
    }

    public function __invoke(): Maybe
    {
        $start = $this->timeout->map(
            static fn($pair) => $pair[0]->now(),
        );

        do {
            // we suspend first before polling the streams because the assumption is
            // that if someone want to watch streams it may be a while before the
            // streams are ready so we better switch to another task
            ($this->suspend)();

            // note that if the suspend strategy is Synchronous or there is only one
            // task being run it will poll the streams a lot resulting in a lot of
            // cpu usage
            $ready = ($this->synchronous)();

            $canReturn = $ready->match(
                fn($ready) => $this->canReturn($ready, $start),
                static fn() => false,
            );
        } while (!$canReturn);

        return $ready;
    }

    /**
     * @internal
     */
    public static function waitForever(Capabilities\Watch $synchronous, Suspend $suspend): self
    {
        /** @var Maybe<array{Clock, ElapsedPeriodInterface}> */
        $timeout = Maybe::nothing();

        // use polling to never block the tasks
        return new self(
            $synchronous->timeoutAfter(ElapsedPeriod::of(0)),
            $suspend,
            $timeout,
        );
    }

    /**
     * @internal
     */
    public static function timeoutAfter(
        Capabilities\Watch $synchronous,
        Suspend $suspend,
        Clock $clock,
        ElapsedPeriodInterface $timeout,
    ): self {
        // use polling to never block the tasks
        return new self(
            $synchronous->timeoutAfter(ElapsedPeriod::of(0)),
            $suspend,
            Maybe::just([$clock, $timeout]),
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function forRead(Readable $read, Readable ...$reads): self
    {
        return new self(
            $this->synchronous->forRead($read, ...$reads),
            $this->suspend,
            $this->timeout,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function forWrite(Writable $write, Writable ...$writes): self
    {
        return new self(
            $this->synchronous->forWrite($write, ...$writes),
            $this->suspend,
            $this->timeout,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function unwatch(Stream $stream): self
    {
        return new self(
            $this->synchronous->unwatch($stream),
            $this->suspend,
            $this->timeout,
        );
    }

    /**
     * @param Maybe<PointInTime> $start
     */
    private function canReturn(Ready $ready, Maybe $start): bool
    {
        if (!$ready->toRead()->empty()) {
            return true;
        }

        if (!$ready->toWrite()->empty()) {
            return true;
        }

        return $this
            ->timeout
            ->map(static fn($pair) => [
                $pair[0]->now(),
                $pair[1],
            ])
            ->flatMap(static fn($pair) => $start->map(
                static fn($start) => $pair[0]
                    ->elapsedSince($start)
                    ->longerThan($pair[1]),
            ))
            ->match(
                static fn($canReturn) => $canReturn,
                static fn() => false, // no timeout so wait forever
            );
    }
}
