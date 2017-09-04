<?php
declare(strict_types=1);

namespace Acfo\Session\Tests\Middleware;

use Acfo\Session\Middleware\GetRequestReadOnlySessionStrategy;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class GetRequestReadOnlySessionStrategyTest extends TestCase
{
    public function testIsReadOnlyWithGetRequestShouldReturnTrue()
    {
        /** @var ServerRequestInterface $request */
        $request = $this->prophesize(ServerRequestInterface::class);
        $request
            ->getMethod()
            ->willReturn('GET');

        $actual = (new GetRequestReadOnlySessionStrategy())->isReadOnly($request->reveal());

        $this->assertTrue($actual);
    }
}
