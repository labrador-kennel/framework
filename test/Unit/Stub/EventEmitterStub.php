<?php

namespace Cspray\Labrador\Http\Test\Unit\Stub;

use Cspray\Labrador\AsyncEvent\Event;
use Cspray\Labrador\AsyncEvent\EventEmitter;
use Cspray\Labrador\AsyncEvent\Listener;
use Cspray\Labrador\AsyncEvent\ListenerRegistration;
use Labrador\CompositeFuture\CompositeFuture;

final class EventEmitterStub implements EventEmitter {

    private array $events = [];
    private array $queuedEvents = [];

    public function register(Listener $listener) : ListenerRegistration {
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

    public function listenerCount(string $event) : int {
        throw new \RuntimeException('You should not attempt to count listeners for this stub');
    }

    public function getListeners(string $event) : array {
        throw new \RuntimeException('You should not attempt to get listeners for this stub');
    }

    public function queue(Event $event) : void {
        $this->queuedEvents[] = $event;
    }
}