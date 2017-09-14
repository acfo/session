<?php
declare(strict_types=1);

namespace Acfo\Session;

use Acfo\Session\Exceptions\AccessViolationException;
use Acfo\Session\Exceptions\InvalidMethodCallException;
use Acfo\Session\Exceptions\UnexpectedActiveSessionException;

class SessionImpl implements Session
{
    /** @var bool */
    private $isLazyLoadEnabled;
    /** @var bool */
    private $isReadOnly;
    /** @var bool */
    private $isSessionActive;

    /**
     * SessionImpl constructor.
     *
     * @param bool $isLazyLoadEnabled
     */
    public function __construct(bool $isLazyLoadEnabled = true)
    {
        $this->isLazyLoadEnabled = $isLazyLoadEnabled;
        $this->isReadOnly = false;
        $this->isSessionActive = false;
    }

    /**
     * @param bool $isReadOnly
     *
     * @throws InvalidMethodCallException
     * @throws UnexpectedActiveSessionException
     */
    public function start(bool $isReadOnly): void
    {
        $this->guardAgainstStartedSession();
        $this->isReadOnly = $isReadOnly;
        if (!$this->isLazyLoadEnabled) {
            $this->init();
        }
    }

    /**
     * @throws InvalidMethodCallException
     */
    private function guardAgainstStartedSession()
    {
        if ($this->isSessionActive) {
            throw new InvalidMethodCallException('start called although session has already been started');
        }
    }

    /**
     * @throws UnexpectedActiveSessionException
     */
    private function ensureInitialized()
    {
        if (!$this->isSessionActive) {
            $this->init();
        }
    }

    /**
     * @throws UnexpectedActiveSessionException
     */
    private function init(): void
    {
        $this->guardAgainstExternallyActivatedSession();
        session_start(['read_and_close' => (int)$this->isReadOnly]);
        $this->isSessionActive = true;
    }

    /**
     * @throws UnexpectedActiveSessionException
     */
    private function guardAgainstExternallyActivatedSession()
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            throw new UnexpectedActiveSessionException('session has already been started somewhere else');
        }
    }

    /**
     * @throws AccessViolationException
     * @throws UnexpectedActiveSessionException
     */
    public function regenerate(): void
    {
        $this->guardAgainstReadOnlySession();
        $this->ensureInitialized();
        session_regenerate_id(true);
    }

    /**
     * @throws AccessViolationException
     */
    private function guardAgainstReadOnlySession()
    {
        if ($this->isReadOnly) {
            throw new AccessViolationException('write access on read-only session');
        }
    }

    /**
     * @throws AccessViolationException
     * @throws UnexpectedActiveSessionException
     */
    public function destroy(): void
    {
        $this->guardAgainstReadOnlySession();
        $_SESSION = [];
        $this->deleteSessionCookie();
        $this->ensureInitialized();
        session_destroy();
    }

    private function deleteSessionCookie()
    {
        if (ini_get('session.use_cookies')) {
            $cookieParams = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 3600, // set the expiration date to one hour ago
                $cookieParams['path'],
                $cookieParams['domain'],
                $cookieParams['secure'],
                $cookieParams['httponly']
            );
        }
    }

    /**
     * @param string $key
     * @param null $default
     *
     * @return mixed
     *
     * @throws UnexpectedActiveSessionException
     */
    public function get(string $key, $default = null)
    {
        $this->ensureInitialized();
        if (!isset($_SESSION[$key])) {
            return $default;
        }

        return $_SESSION[$key];
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @throws AccessViolationException
     * @throws UnexpectedActiveSessionException
     */
    public function set(string $key, $value): void
    {
        $this->guardAgainstReadOnlySession();
        $this->ensureInitialized();
        $_SESSION[$key] = $value;
    }

    /**
     * @param string $key
     *
     * @throws AccessViolationException
     * @throws UnexpectedActiveSessionException
     */
    public function delete(string $key): void
    {
        $this->guardAgainstReadOnlySession();
        $this->ensureInitialized();
        unset($_SESSION[$key]);
    }

    /**
     * @throws AccessViolationException
     * @throws UnexpectedActiveSessionException
     */
    public function deleteAll(): void
    {
        $this->guardAgainstReadOnlySession();
        $this->ensureInitialized();
        $_SESSION = [];
    }

    /**
     * @throws InvalidMethodCallException
     */
    public function close(): void
    {
        if (!$this->isSessionActive) {
            if ($this->isLazyLoadEnabled) {
                return;
            }
            throw new InvalidMethodCallException('close called although session has not been started');
        }
        if (!$this->isReadOnly) {
            session_write_close();
        }
        $this->isSessionActive = false;
    }
}
