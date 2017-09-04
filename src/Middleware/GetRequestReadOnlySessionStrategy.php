<?php
declare(strict_types=1);

namespace Acfo\Session\Middleware;

use Psr\Http\Message\ServerRequestInterface;

class GetRequestReadOnlySessionStrategy implements ReadOnlySessionStrategy
{
    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    public function isReadOnly(ServerRequestInterface $request): bool
    {
        return $request->getMethod() == 'GET';
    }
}
