<?php

namespace Labrador\Test\Unit\Web\Stub;

use Labrador\AsyncEvent\Emitter;
use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\EventName;
use Labrador\AsyncEvent\FinishedNotifier;
use Labrador\AsyncEvent\Listener;
use Labrador\AsyncEvent\ListenerRegistration;
use Labrador\CompositeFuture\CompositeFuture;

final class EventEmitterStub implements Emitter {

    private array $events = [];
    private array $queuedEvents = [];

    public function register(string|EventName $event, Listener $listener) : ListenerRegistration {
        throw new \RuntimeException('You should not register listeners to this stub.');
    }

    public function emit(Event $event) : CompositeFuture {
        $this->events[] = $event;
        return new CompositeFuture([]);
    }

    public function getEmittedEvents() : array {
        return $this->events;
    }

    public function getQueuedEvents() : array {
        return $this->queuedEvents;
    }

    public function clearEmittedEvents() : void {
        $this->events = [];
    }

    public function listeners(EventName|string $eventName) : array {
        throw new \RuntimeException('You should not attempt to get listeners for this stub');
    }

    public function queue(Event $event) : FinishedNotifier {
        $this->queuedEvents[] = $event;
        return new class implements FinishedNotifier {

            public function finished(callable $callable) : void {
                throw new \RuntimeException('You should not attempt to be notified when your queued event is finished, this stub does not invoke events.');
            }
        };
    }
}