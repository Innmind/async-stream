# Async Stream

[![Build Status](https://github.com/innmind/async-stream/workflows/CI/badge.svg?branch=main)](https://github.com/innmind/async-stream/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/innmind/async-stream/branch/develop/graph/badge.svg)](https://codecov.io/gh/innmind/async-stream)
[![Type Coverage](https://shepherd.dev/github/innmind/async-stream/coverage.svg)](https://shepherd.dev/github/innmind/async-stream)

Async implementation of [`innmind/stream`](https://packagist.org/packages/innmind/stream) to allow switching to another task when reading, writing or watching for streams.

## Installation

```sh
composer require innmind/async-stream
```

## Usage

```php
use Innmind\Async\Stream\Streams;
use Innmind\Stream\Streams as Synchronous;
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\Url\Path;
use Innmind\Mantle\{
    Source\Predetermined,
    Suspend,
    Forerunner,
};

$clock = new Clock;
$synchronous = Synchronous::fromAmbientAuthority();
$source = Predetermined::of(
    static function(Suspend $suspend) use ($clock, $synchronous) {
        $stream = Streams::of($synchronous, $suspend, $clock)
            ->readable()
            ->open(Path::of('fixtures/first.txt'));

        while (!$stream->end()) {
            echo $stream->readLine()->match(
                static fn($line) => $line->toString(),
                static fn() => '',
            );
        }
    },
    static function(Suspend $suspend) use ($clock, $synchronous) {
        $stream = Streams::of($synchronous, $suspend, $clock)
            ->readable()
            ->open(Path::of('fixtures/second.txt'));

        while (!$stream->end()) {
            echo $stream->readLine()->match(
                static fn($line) => $line->toString(),
                static fn() => '',
            );
        }
    },
);

Forerunner::of($clock)(null, $source); // will print interlaced lines of both files
```
