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
        if ($this->isSessionActive) {
            throw new InvalidMethodCallException('start called although session has already been started');
        }
        $this->isReadOnly = $isReadOnly;
        if (!$this->isLazyLoadEnabled) {
            $this->init();
        }
    }

    /**
     * @throws UnexpectedActiveSessionException
     */
    private function init(): void
    {
    	if (session_status() == PHP_SESSION_ACTIVE) {
			throw new UnexpectedActiveSessionException('session has already been started somewhere else');
		}
        session_start(['read_and_close' => (int)$this->isReadOnly]);
        $this->isSessionActive = true;
    }

    /**
     * @throws AccessViolationException
     * @throws UnexpectedActiveSessionException
     */
    public function regenerate(): void
    {
        if ($this->isReadOnly) {
            throw new AccessViolationException('regenerate called on read-only session');
        }
        if (!$this->isSessionActive) {
            $this->init();
        }
        session_regenerate_id(true);
    }

    /**
     * @throws AccessViolationException
     * @throws UnexpectedActiveSessionException
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
        if (!$this->isSessionActive) {
            $this->init();
        }
        session_destroy();
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
        if (!$this->isSessionActive) {
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
     * @throws UnexpectedActiveSessionException
     */
    public function set(string $key, $value): void
    {
        if ($this->isReadOnly) {
            throw new AccessViolationException('set called on read-only session');
        }
        if (!$this->isSessionActive) {
            $this->init();
        }
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
        if ($this->isReadOnly) {
            throw new AccessViolationException('delete called on read-only session');
        }
        if (!$this->isSessionActive) {
            $this->init();
        }
        unset($_SESSION[$key]);
    }

    /**
     * @throws AccessViolationException
     * @throws UnexpectedActiveSessionException
     */
    public function deleteAll(): void
    {
        if ($this->isReadOnly) {
            throw new AccessViolationException('clearAll called on read-only session');
        }
        if (!$this->isSessionActive) {
            $this->init();
        }
        $_SESSION = [];
    }

    /**
     * @throws InvalidMethodCallException
     * @throws UnexpectedActiveSessionException
     */
    public function close(): void
    {
        if ($this->isReadOnly) {
            $this->isSessionActive = false;
            return;
        }
        if (!$this->isSessionActive) {
            throw new InvalidMethodCallException('close called although session has not been started');
        }
        session_write_close();
        $this->isSessionActive = false;
    }
}
