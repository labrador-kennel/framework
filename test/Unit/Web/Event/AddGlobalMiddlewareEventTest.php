<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Event;

use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Application\Event\AddGlobalMiddleware;
use Labrador\Web\Middleware\GlobalMiddlewareCollection;
use PHPUnit\Framework\TestCase;

final class AddGlobalMiddlewareEventTest extends TestCase {

    public function testNameIsAddGlobalMiddlewareEnumValue() : void {
        $subject = new AddGlobalMiddleware(new GlobalMiddlewareCollection());

        self::assertSame(
            ApplicationEvent::AddGlobalMiddleware->value,
            $subject->name()
        );
    }

    public function testPayloadIsGlobalMiddlewareCollectionAddedToEvent() : void {
        $subject = new AddGlobalMiddleware($collection = new GlobalMiddlewareCollection());

        self::assertSame($collection, $subject->payload());
    }

    public function testGetCreatedAt() : void {
        $subject = new AddGlobalMiddleware(new GlobalMiddlewareCollection());
        $createdAt = $subject->triggeredAt();

        $diff = $createdAt->diff(new \DateTimeImmutable());

        // Just make sure the datetime was created recently, i.e. within the last second.
        self::assertSame(0, $diff->s);
    }
}
