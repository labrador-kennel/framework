<?php declare(strict_types=1);

namespace Labrador\Web\Session;

use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Web\Session\Exception\SessionNotAttachedToRequest;

#[Service]
final readonly class SessionHelper {

    public function id(Request $request) : ?string {
        return $this->session($request)->getId();
    }

    public function has(Request $request, SessionAttribute $attribute) : bool {
        return $this->session($request)->has($attribute->key());
    }

    public function get(Request $request, SessionAttribute $attribute) : mixed {
        return $this->session($request)->get($attribute->key());
    }

    public function getAll(Request $request) : array {
        return $this->session($request)->getData();
    }

    public function set(Request $request, SessionAttribute $attribute, mixed $value) : void {
        $this->session($request)->set($attribute->key(), $value);
    }

    public function unset(Request $request, SessionAttribute $attribute) : void {
        $this->session($request)->unset($attribute->key());
    }

    public function destroy(Request $request) : void {
        $this->session($request)->destroy();
    }

    public function regenerate(Request $request) : void {
        $this->session($request)->regenerate();
    }

    public function read(Request $request) : void {
        $this->session($request)->read();
    }

    public function lock(Request $request) : void {
        $this->session($request)->lock();
    }

    public function commit(Request $request) : void {
        $this->session($request)->commit();
    }

    public function rollback(Request $request) : void {
        $this->session($request)->rollback();
    }

    public function isLocked(Request $request) : bool {
        return $this->session($request)->isLocked();
    }

    public function isRead(Request $request) : bool {
        return $this->session($request)->isRead();
    }

    private function session(Request $request) : Session {
        if (!$request->hasAttribute(Session::class)) {
            throw SessionNotAttachedToRequest::fromSessionNotAttachedToRequest();
        }

        $session = $request->getAttribute(Session::class);
        assert($session instanceof Session);

        return $session;
    }
}
