<?php
declare(strict_types=1);

namespace Acfo\Session\Middleware;

use Psr\Http\Message\ServerRequestInterface;

interface ReadOnlySessionStrategy
{
    /**
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function isReadOnly(ServerRequestInterface $request): bool;
}
