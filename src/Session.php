<?php
declare(strict_types=1);

namespace Acfo\Session;

use Acfo\Session\Exceptions\AccessViolationException;
use Acfo\Session\Exceptions\InvalidMethodCallException;
use Acfo\Session\Exceptions\UnexpectedActiveSessionException;

interface Session
{
    /**
     * @param bool $isReadOnly
     *
     * @throws InvalidMethodCallException
     * @throws UnexpectedActiveSessionException
     */
    public function start(bool $isReadOnly): void;

    /**
     * @throws AccessViolationException
     * @throws UnexpectedActiveSessionException
     */
    public function regenerate(): void;

    /**
     * @throws AccessViolationException
     * @throws UnexpectedActiveSessionException
     */
    public function destroy(): void;

    /**
     * @param string $key
     * @param mixed|null $default
     *
     * @return mixed
     *
     * @throws UnexpectedActiveSessionException
     */
    public function get(string $key, $default = null);

    /**
     * @param string $key
     * @param mixed $value
     *
     * @throws AccessViolationException
     * @throws UnexpectedActiveSessionException
     */
    public function set(string $key, $value): void;

    /**
     * @param string $key
     *
     * @throws AccessViolationException
     * @throws UnexpectedActiveSessionException
     */
    public function delete(string $key): void;

    /**
     * @throws AccessViolationException
     * @throws UnexpectedActiveSessionException
     */
    public function deleteAll(): void;

    /**
     * @throws InvalidMethodCallException
     */
    public function close(): void;
}
