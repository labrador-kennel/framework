<?php declare(strict_types=1);

namespace Labrador\Web\Security;

use Amp\Cache\Cache;
use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Security\TokenGenerator;
use Labrador\Web\Exception\SessionNotEnabled;
use Ramsey\Uuid\Uuid;

#[Service]
final class CsrfTokenManager {

    public function __construct(
        private readonly TokenGenerator $tokenGenerator,
        private readonly Cache $cache
    ) {}

    public function generateAndStore(Request $request) : string {
        if (!$request->hasAttribute('session')) {
            throw SessionNotEnabled::fromCsrfTokenManagerRequiresSession();
        }

        $session = $request->getAttribute('session');
        assert($session instanceof Session);

        $id = Uuid::uuid4()->toString();
        $token = $this->tokenGenerator->generateToken();

        if ($session->has('csrfStoreIds')) {
            $storeIds = json_decode($session->get('csrfStoreIds'), true);
        } else {
            $storeIds = [];
        }

        $storeIds[] = $id;
        $session->set('csrfStoreIds', json_encode($storeIds));

        $this->cache->set($id, $token);

        return $token;
    }

    public function validateAndExpire(Request $request, string $csrfToken) : bool {
        if (!$request->hasAttribute('session')) {
            throw SessionNotEnabled::fromCsrfTokenManagerRequiresSession();
        }

        $session = $request->getAttribute('session');
        assert($session instanceof Session);
        if (!$session->has('csrfStoreIds')) {
            return false;
        }

        $storeIds = json_decode($session->get('csrfStoreIds'), true);
        $valid = false;
        foreach ($storeIds as $id) {
            $token = $this->cache->get($id);
            if ($token === $csrfToken) {
                $valid = true;
                $this->cache->delete($id);
                $session->set('csrfStoreIds', json_encode(array_diff($storeIds, [$id])));
                break;
            }
        }

        return $valid;
    }

}
