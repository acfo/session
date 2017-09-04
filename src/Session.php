<?php
declare(strict_types=1);

namespace Acfo\Session;

use Acfo\Session\Exceptions\AccessViolationException;
use Acfo\Session\Exceptions\InvalidMethodCallException;

interface Session
{
    /**
     * @param bool $isReadOnly
     *
     * @throws InvalidMethodCallException
     */
    public function start(bool $isReadOnly): void;

    /**
     * @throws AccessViolationException
     */
    public function regenerate(): void;

    /**
     * @throws AccessViolationException
     */
    public function destroy(): void;

    /**
     * @param string $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * @param string $key
     * @param mixed $value
     *
     * @throws AccessViolationException
     */
    public function set(string $key, $value): void;

    /**
     * @param string $key
     *
     * @throws AccessViolationException
     */
    public function delete(string $key): void;

    /**
     * @throws AccessViolationException
     */
    public function clearAll(): void;
}
