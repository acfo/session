<?php
declare(strict_types=1);

namespace Acfo\Session\Middleware\Slim3;

use Acfo\Session\Exceptions\InvalidMethodCallException;
use Acfo\Session\Exceptions\UnexpectedActiveSessionException;
use Acfo\Session\Middleware\ReadOnlySessionStrategy;
use Acfo\Session\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SessionMiddleware
{
    /**
     * @see http://php.net/manual/en/features.session.security.management.php
     */
    public const RECOMMENDED_SETTINGS = [
        'session.use_strict_mode' => '1'
    ];
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

    /**
     * SessionMiddleware constructor.
     * @param Session $session
     * @param ReadOnlySessionStrategy[] $readOnlySessionStrategies
     * @param array $settings
     */
    public function __construct(Session $session, array $readOnlySessionStrategies = [], array $settings = [])
    {
        $this->session = $session;
        $this->readOnlySessionStrategies = $readOnlySessionStrategies;
        $this->settings = $settings;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     *
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface {
        $isReadOnly = false;
        foreach ($this->readOnlySessionStrategies as $readOnlySessionStrategy) {
            $isReadOnly = $readOnlySessionStrategy->isReadOnly($request);
            if ($isReadOnly) {
                break;
            }
        }
        foreach ($this->settings as $name => $value) {
            ini_set($name, $value);
        }
        try {
            $this->session->start($isReadOnly);
        } catch (InvalidMethodCallException | UnexpectedActiveSessionException $e) {
            return $response->withStatus(500);
        }

        return $next($request, $response);
    }
}
