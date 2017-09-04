<?php
declare(strict_types=1);

namespace Acfo\Session;

use Acfo\Session\Exceptions\AccessViolationException;
use Acfo\Session\Exceptions\InvalidMethodCallException;

class SessionImpl implements Session
{
    /** @var bool */
    private $isLazyLoadEnabled;
    /** @var bool */
    private $isReadOnly;

    /**
     * SessionImpl constructor.
     *
     * @param bool $isLazyLoadEnabled
     */
    public function __construct(bool $isLazyLoadEnabled = true)
    {
        $this->isLazyLoadEnabled = $isLazyLoadEnabled;
        $this->isReadOnly = false;
    }

    /**
     * @param bool $isReadOnly
     *
     * @throws InvalidMethodCallException
     */
    public function start(bool $isReadOnly): void
    {
        if ($this->isSessionActive()) {
            throw new InvalidMethodCallException('start called although session is already active');
        }
        $this->isReadOnly = $isReadOnly;
        if (!$this->isLazyLoadEnabled) {
            $this->init();
        }
    }

    private function init()
    {
        session_start(['read_and_close' => (int)$this->isReadOnly]);
    }

    /**
     * @return bool
     */
    private function isSessionActive()
    {

        return session_status() == PHP_SESSION_ACTIVE;
    }

    /**
     * @throws AccessViolationException
     */
    public function regenerate(): void
    {
        if ($this->isReadOnly) {
            throw new AccessViolationException('regenerate called on read-only session');
        }
        if (!$this->isSessionActive()) {
            $this->init();
        }
        session_regenerate_id(true);
    }

    /**
     * @throws AccessViolationException
     */
    public function destroy(): void
    {
        if ($this->isReadOnly) {
            throw new AccessViolationException('destroy called on read-only session');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $cookieParams = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $cookieParams['path'],
                $cookieParams['domain'],
                $cookieParams['secure'],
                $cookieParams['httponly']
            );
        }
        if (!$this->isSessionActive()) {
            $this->init();
        }
        session_destroy();
    }

    /**
     * @param string $key
     * @param null $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if (!$this->isSessionActive()) {
            $this->init();
        }
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
     */
    public function set(string $key, $value): void
    {
        if ($this->isReadOnly) {
            throw new AccessViolationException('set called on read-only session');
        }
        if (!$this->isSessionActive()) {
            $this->init();
        }
        $_SESSION[$key] = $value;
    }

    /**
     * @param string $key
     *
     * @throws AccessViolationException
     */
    public function delete(string $key): void
    {
        if ($this->isReadOnly) {
            throw new AccessViolationException('delete called on read-only session');
        }
        if (!$this->isSessionActive()) {
            $this->init();
        }
        unset($_SESSION[$key]);
    }

    /**
     * @throws AccessViolationException
     */
    public function clearAll(): void
    {
        if ($this->isReadOnly) {
            throw new AccessViolationException('clearAll called on read-only session');
        }
        if (!$this->isSessionActive()) {
            $this->init();
        }
        $_SESSION = [];
    }
}
