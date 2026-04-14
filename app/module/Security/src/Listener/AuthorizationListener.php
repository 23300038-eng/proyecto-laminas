<?php

declare(strict_types=1);

namespace Security\Listener;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Security\Support\AccessHelper;

class AuthorizationListener
{
    private AdapterInterface $db;

    public function __construct(AdapterInterface $db)
    {
        $this->db = $db;
    }

    public function onRoute(MvcEvent $event): ?Response
    {
        AccessHelper::startSession();

        $request = $event->getRequest();
        $path = $request->getUri()->getPath();

        if (in_array($path, ['/auth/login', '/auth/logout', '/login'], true)) {
            return null;
        }

        if (!AccessHelper::isAuthenticated()) {
            return null;
        }

        if (AccessHelper::isPathAllowed($this->db, $path)) {
            return null;
        }

        $response = $event->getResponse();
        $response->setStatusCode(302);
        $response->getHeaders()->addHeaderLine('Location', '/');
        $event->setResponse($response);

        return $response;
    }
}
