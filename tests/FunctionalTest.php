<?php
declare(strict_types = 1);

namespace Tests\Innmind\Async\Stream;

use Innmind\Async\Stream\Streams;
use Innmind\Stream\{
    Streams as Synchronous,
    Stream\Position,
};
use Innmind\TimeContinuum\Earth\{
    Clock,
    ElapsedPeriod,
};
use Innmind\Url\Path;
use Innmind\Mantle\{
    Forerunner,
    Source\Predetermined,
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class FunctionalTest extends TestCase
{
    public function testRead()
    {
        $queue = new \SplQueue;
        $synchronous = Synchronous::fromAmbientAuthority();
        $clock = new Clock;
        $source = Predetermined::of(
            static function($suspend) use ($clock, $synchronous, $queue) {
                $stream = Streams::of($synchronous, $suspend, $clock)
                    ->readable()
                    ->open(Path::of('fixtures/first.txt'));

                while (!$stream->end()) {
                    $stream->readLine()->match(
                        static fn($line) => $queue->push($line->toString()),
                        static fn() => null,
                    );
                }
            },
            static function($suspend) use ($clock, $synchronous, $queue) {
                $stream = Streams::of($synchronous, $suspend, $clock)
                    ->readable()
                    ->open(Path::of('fixtures/second.txt'));

                while (!$stream->end()) {
                    $stream->readLine()->match(
                        static fn($line) => $queue->push($line->toString()),
                        static fn() => null,
                    );
                }
            },
        );

        Forerunner::of($clock)(null, $source);

        $this->assertSame(
            [
                "first\n",
                "second\n",
                "first\n",
                "second\n",
                "first\n",
                "second\n",
                "first\n",
                "second\n",
            ],
            \iterator_to_array($queue),
        );
    }

    public function testWrite()
    {
        $queue = new \SplQueue;
        $tmp = \tempnam(\sys_get_temp_dir(), 'innmind');
        $synchronous = Synchronous::fromAmbientAuthority();
        $clock = new Clock;
        $source = Predetermined::of(
            static function($suspend) use ($clock, $synchronous, $tmp, $queue) {
                $stream = Streams::of($synchronous, $suspend, $clock)
                    ->writable()
                    ->open(Path::of($tmp));

                $stream->write(Str::of("first\n"));
                $queue->push('first');
                $stream->write(Str::of("first\n"));
                $queue->push('first');
                $stream->write(Str::of("first\n"));
                $queue->push('first');
                $stream->write(Str::of("first\n"));
                $queue->push('first');
            },
            static function($suspend) use ($clock, $synchronous, $tmp, $queue) {
                $stream = Streams::of($synchronous, $suspend, $clock)
                    ->writable()
                    ->open(Path::of($tmp));

                $stream->write(Str::of("second\n"));
                $queue->push('second');
                $stream->write(Str::of("second\n"));
                $queue->push('second');
                $stream->write(Str::of("second\n"));
                $queue->push('second');
                $stream->write(Str::of("second\n"));
                $queue->push('second');
            },
        );

        Forerunner::of($clock)(null, $source);

        // we use a queue to test it writes asynchronously instead of looking at
        // the stream written because there is a position synchronization error
        // and the stream is not a clean interlacing of both strings
        $this->assertSame(
            [
                'first',
                'second',
                'first',
                'second',
                'first',
                'second',
                'first',
                'second',
            ],
            \iterator_to_array($queue),
        );
    }

    public function testWatch()
    {
        $queue = new \SplQueue;
        $synchronous = Synchronous::fromAmbientAuthority();
        $clock = new Clock;
        $source = Predetermined::of(
            static function($suspend) use ($clock, $synchronous, $queue) {
                $watch = Streams::of($synchronous, $suspend, $clock)
                    ->watch()
                    ->timeoutAfter(ElapsedPeriod::of(100));

                $watch();

                $queue->push('first');
            },
            static function($suspend) use ($clock, $synchronous, $queue) {
                $watch = Streams::of($synchronous, $suspend, $clock)
                    ->watch()
                    ->timeoutAfter(ElapsedPeriod::of(50));

                $watch();

                $queue->push('second');
            },
        );

        Forerunner::of($clock)(null, $source);

        $this->assertSame(
            [
                'second',
                'first',
            ],
            \iterator_to_array($queue),
        );
    }
}
