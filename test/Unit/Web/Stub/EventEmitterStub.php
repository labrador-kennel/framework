<?php

namespace Labrador\Test\Unit\Web\Stub;

use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\EventEmitter;
use Labrador\AsyncEvent\Listener;
use Labrador\AsyncEvent\ListenerProvider;
use Labrador\AsyncEvent\ListenerRegistration;
use Labrador\CompositeFuture\CompositeFuture;

final class EventEmitterStub implements EventEmitter {

    private array $events = [];
    private array $queuedEvents = [];

    public function register(Listener|ListenerProvider $listener) : ListenerRegistration {
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