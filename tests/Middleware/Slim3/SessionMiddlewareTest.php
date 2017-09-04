<?php
declare(strict_types=1);

namespace Acfo\Session\Tests\Middleware;

use Acfo\Session\Exceptions\InvalidMethodCallException;
use Acfo\Session\Middleware\GetRequestReadOnlySessionStrategy;
use Acfo\Session\Middleware\Slim3\SessionMiddleware;
use Acfo\Session\Session;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SessionMiddlewareTest extends TestCase
{
    use PHPMock;

    /**
     * @var Session
     */
    private $session;
    /**
     * @var array
     */
    private $readOnlySessionStrategies;
    /**
     * @var array
     */
    private $settings;

    protected function setUp()
    {
        parent::setUp();

        $this->session = $this->prophesize(Session::class);
        $this->readOnlySessionStrategies = [new GetRequestReadOnlySessionStrategy()];
        $this->settings = SessionMiddleware::RECOMMENDED_SETTINGS;
    }

    private function getSut()
    {
        return new SessionMiddleware(
            $this->session->reveal(),
            $this->readOnlySessionStrategies,
            $this->settings
        );
    }

    public function testInvokeWithSessionErrorShouldReturnResponse500()
    {
        /** @var ServerRequestInterface $request */
        $request = $this->prophesize(ServerRequestInterface::class);
        $method = 'GET';
        $request
            ->getMethod()
            ->willReturn($method);
        /** @var ResponseInterface $response */
        $response = $this->prophesize(ResponseInterface::class);
        /** @var ResponseInterface $expectedResponse */
        $expectedResponse = $this->prophesize(ResponseInterface::class);
        $expectedResponse
            ->getStatusCode()
            ->willReturn(500);
        $response
            ->withStatus(500)
            ->willReturn($expectedResponse->reveal());
        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        };
        $ini_set = $this->getFunctionMock('Acfo\Session\Middleware\Slim3', 'ini_set');
        $ini_set
            ->expects($this->once())
            ->with('session.use_strict_mode', '1')
            ->willReturn(true);
        $this->session
            ->start(true)
            ->willThrow(InvalidMethodCallException::class);

        $actual = $this->getSut()->__invoke($request->reveal(), $response->reveal(), $next);

        $this->assertEquals(500, $actual->getStatusCode());
    }

    public function testStartShouldReturnResponse200()
    {
        $this->readOnlySessionStrategies = [];
        $this->settings = [];
        /** @var ServerRequestInterface $request */
        $request = $this->prophesize(ServerRequestInterface::class);
        /** @var ResponseInterface $response */
        $response = $this->prophesize(ResponseInterface::class);
        $response
            ->getStatusCode()
            ->willReturn(200);
        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        };
        $this->session
            ->start(false)
            ->shouldBeCalledTimes(1);

        $actual = $this->getSut()->__invoke($request->reveal(), $response->reveal(), $next);

        $this->assertEquals(200, $actual->getStatusCode());
    }
}
