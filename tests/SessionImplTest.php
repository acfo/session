<?php
declare(strict_types=1);

namespace Acfo\Session\Tests;

use Acfo\Session\Exceptions\AccessViolationException;
use Acfo\Session\Exceptions\InvalidMethodCallException;
use Acfo\Session\Exceptions\UnexpectedActiveSessionException;
use Acfo\Session\SessionImpl;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class SessionImplTest extends TestCase
{
    use PHPMock;

    /** @var bool */
    private $isLazyLoadEnabled;

    protected function setUp()
    {
        parent::setUp();

        global $_SESSION;
        $_SESSION = [];
        $this->isLazyLoadEnabled = false;
    }

    private function getSut()
    {
        return new SessionImpl($this->isLazyLoadEnabled);
    }

    public function testStartWithSessionActiveShouldThrowInvalidMethodCallException()
    {
        $session_status = $this->getFunctionMock('Acfo\Session', 'session_status');
        $session_status
            ->expects($this->once())
            ->willReturn(PHP_SESSION_ACTIVE);
        $this->expectException(UnexpectedActiveSessionException::class);

        $this->getSut()->start(false);
    }

    public function testStartWithNotLazyLoadAndNotReadOnlyShouldCallStartSession()
    {
        $session_status = $this->getFunctionMock('Acfo\Session', 'session_status');
        $session_status
            ->expects($this->once())
            ->willReturn(PHP_SESSION_NONE);
        $session_start = $this->getFunctionMock('Acfo\Session', 'session_start');
        $options = ['read_and_close' => 0];
        $session_start
            ->expects($this->once())
            ->with($options)
            ->willReturn(true);

        $this->getSut()->start(false);
    }

    public function testStartWithInitAlreadyCalledShouldThrowInvalidMethodCallException()
    {
        $session_status = $this->getFunctionMock('Acfo\Session', 'session_status');
        $session_status
            ->expects($this->once())
            ->willReturn(PHP_SESSION_NONE);
        $session_start = $this->getFunctionMock('Acfo\Session', 'session_start');
        $options = ['read_and_close' => 0];
        $session_start
            ->expects($this->once())
            ->with($options)
            ->willReturn(true);
        $this->expectException(InvalidMethodCallException::class);

        $sut = $this->getSut();
        $sut->start(false);
        $sut->start(false);
    }


    public function testRegenerateWithReadOnlySessionShouldThrowAccessViolationException()
    {
        $session_status = $this->getFunctionMock('Acfo\Session', 'session_status');
        $session_status
            ->expects($this->once())
            ->willReturn(PHP_SESSION_NONE);
        $session_start = $this->getFunctionMock('Acfo\Session', 'session_start');
        $options = ['read_and_close' => 1];
        $session_start
            ->expects($this->once())
            ->with($options)
            ->willReturn(true);
        $this->expectException(AccessViolationException::class);

        $sut = $this->getSut();
        $sut->start(true);
        $sut->regenerate();
    }

    public function testRegenerateWithLazyLoadSessionShouldStartSessionAndRegenerate()
    {
        $session_status = $this->getFunctionMock('Acfo\Session', 'session_status');
        $session_status
            ->expects($this->once())
            ->willReturn(PHP_SESSION_NONE);
        $session_start = $this->getFunctionMock('Acfo\Session', 'session_start');
        $options = ['read_and_close' => 0];
        $session_start
            ->expects($this->once())
            ->with($options)
            ->willReturn(true);
        $session_regenerate_id = $this->getFunctionMock('Acfo\Session', 'session_regenerate_id');
        $session_regenerate_id
            ->expects($this->once())
            ->with(true)
            ->willReturn(true);

        $this->getSut()->regenerate();
    }

    public function testDestroyWithReadOnlySessionShouldThrowAccessViolationException()
    {
        $session_status = $this->getFunctionMock('Acfo\Session', 'session_status');
        $session_status
            ->expects($this->once())
            ->willReturn(PHP_SESSION_NONE);
        $session_start = $this->getFunctionMock('Acfo\Session', 'session_start');
        $options = ['read_and_close' => 1];
        $session_start
            ->expects($this->once())
            ->with($options)
            ->willReturn(true);
        $this->expectException(AccessViolationException::class);

        $sut = $this->getSut();
        $sut->start(true);
        $sut->destroy();
    }

    public function testDestroyWithLazyLoadSessionShouldDeleteCookieStartAndDestroySession()
    {
        $session_status = $this->getFunctionMock('Acfo\Session', 'session_status');
        $session_status
            ->expects($this->once())
            ->willReturn(PHP_SESSION_NONE);
        $ini_get = $this->getFunctionMock('Acfo\Session', 'ini_get');
        $ini_get
            ->expects($this->once())
            ->with('session.use_cookies')
            ->willReturn(true);
        $cookieParams = [
            'lifetime' => 0,
            'path' => 'path',
            'domain' => 'domain',
            'secure' => false,
            'httponly' => true
        ];
        $session_get_cookie_params = $this->getFunctionMock('Acfo\Session', 'session_get_cookie_params');
        $session_get_cookie_params
            ->expects($this->once())
            ->willReturn($cookieParams);
        $session_name = $this->getFunctionMock('Acfo\Session', 'session_name');
        $session_name
            ->expects($this->once())
            ->willReturn('session_name');
        $time = $this->getFunctionMock('Acfo\Session', 'time');
        $time
            ->expects($this->once())
            ->willReturn(0);
        $setcookie = $this->getFunctionMock('Acfo\Session', 'setcookie');
        $setcookie
            ->expects($this->once())
            ->with('session_name', '', -3600, 'path', 'domain', false, true)
            ->willReturn(0);
        $session_start = $this->getFunctionMock('Acfo\Session', 'session_start');
        $options = ['read_and_close' => 0];
        $session_start
            ->expects($this->once())
            ->with($options)
            ->willReturn(true);
        $session_destroy = $this->getFunctionMock('Acfo\Session', 'session_destroy');
        $session_destroy
            ->expects($this->once())
            ->willReturn(true);

        $this->getSut()->destroy();
    }

    public function testGetWithUnknownKeyShouldReturnDefault()
    {
        $session_status = $this->getFunctionMock('Acfo\Session', 'session_status');
        $session_status
            ->expects($this->once())
            ->willReturn(PHP_SESSION_NONE);
        $session_start = $this->getFunctionMock('Acfo\Session', 'session_start');
        $options = ['read_and_close' => 0];
        $session_start
            ->expects($this->once())
            ->with($options)
            ->willReturn(true);

        $actual = $this->getSut()->get('key');

        $this->assertNull($actual);
    }

    public function testGetWithKnownKeyShouldReturnValue()
    {
        $session_status = $this->getFunctionMock('Acfo\Session', 'session_status');
        $session_status
            ->expects($this->once())
            ->willReturn(PHP_SESSION_NONE);
        $session_start = $this->getFunctionMock('Acfo\Session', 'session_start');
        $options = ['read_and_close' => 0];
        $session_start
            ->expects($this->once())
            ->with($options)
            ->willReturn(true);
        $expected = 'value';
        $_SESSION['key'] = $expected;

        $actual = $this->getSut()->get('key');

        $this->assertEquals($expected, $actual);
    }

    public function testSetWithReadOnlySessionShouldThrowAccessViolationException()
    {
        $session_status = $this->getFunctionMock('Acfo\Session', 'session_status');
        $session_status
            ->expects($this->once())
            ->willReturn(PHP_SESSION_NONE);
        $session_start = $this->getFunctionMock('Acfo\Session', 'session_start');
        $options = ['read_and_close' => 1];
        $session_start
            ->expects($this->once())
            ->with($options)
            ->willReturn(true);
        $this->expectException(AccessViolationException::class);

        $sut = $this->getSut();
        $sut->start(true);
        $sut->set('key', 'value');
    }

    public function testSetWithSessionNotActiveShouldStartSessionAndSetValue()
    {
        $session_status = $this->getFunctionMock('Acfo\Session', 'session_status');
        $session_status
            ->expects($this->once())
            ->willReturn(PHP_SESSION_NONE);
        $session_start = $this->getFunctionMock('Acfo\Session', 'session_start');
        $options = ['read_and_close' => 0];
        $session_start
            ->expects($this->once())
            ->with($options)
            ->willReturn(true);

        $this->getSut()->set('key', 'value');

        $this->assertEquals('value', $_SESSION['key']);
    }

    public function testDeleteWithReadOnlySessionShouldThrowAccessViolationException()
    {
        $session_status = $this->getFunctionMock('Acfo\Session', 'session_status');
        $session_status
            ->expects($this->once())
            ->willReturn(PHP_SESSION_NONE);
        $session_start = $this->getFunctionMock('Acfo\Session', 'session_start');
        $options = ['read_and_close' => 1];
        $session_start
            ->expects($this->once())
            ->with($options)
            ->willReturn(true);
        $this->expectException(AccessViolationException::class);

        $sut = $this->getSut();
        $sut->start(true);
        $sut->delete('key');
    }

    public function testDeleteWithSessionNotActiveShouldStartSessionAndDeleteValue()
    {
        $session_status = $this->getFunctionMock('Acfo\Session', 'session_status');
        $session_status
            ->expects($this->once())
            ->willReturn(PHP_SESSION_NONE);
        $session_start = $this->getFunctionMock('Acfo\Session', 'session_start');
        $options = ['read_and_close' => 0];
        $session_start
            ->expects($this->once())
            ->with($options)
            ->willReturn(true);
        $_SESSION['key'] = 'value';

        $this->getSut()->delete('key');

        $this->assertArrayNotHasKey('key', $_SESSION);
    }

    public function testDeleteAllWithReadOnlySessionShouldThrowAccessViolationException()
    {
        $session_status = $this->getFunctionMock('Acfo\Session', 'session_status');
        $session_status
            ->expects($this->once())
            ->willReturn(PHP_SESSION_NONE);
        $session_start = $this->getFunctionMock('Acfo\Session', 'session_start');
        $options = ['read_and_close' => 1];
        $session_start
            ->expects($this->once())
            ->with($options)
            ->willReturn(true);
        $this->expectException(AccessViolationException::class);

        $sut = $this->getSut();
        $sut->start(true);
        $sut->deleteAll();
    }

    public function testDeleteAllWithSessionNotActiveShouldStartSessionAndDeleteValue()
    {
        $session_status = $this->getFunctionMock('Acfo\Session', 'session_status');
        $session_status
            ->expects($this->once())
            ->willReturn(PHP_SESSION_NONE);
        $session_start = $this->getFunctionMock('Acfo\Session', 'session_start');
        $options = ['read_and_close' => 0];
        $session_start
            ->expects($this->once())
            ->with($options)
            ->willReturn(true);
        $_SESSION['key'] = 'value';

        $this->getSut()->deleteAll();

        $this->assertArrayNotHasKey('key', $_SESSION);
    }

    public function testCloseWithReadOnlySessionShouldNotInvokeSessionWriteClose()
    {
        $this->isLazyLoadEnabled = true;
        $session_write_close = $this->getFunctionMock('Acfo\Session', 'session_write_close');
        $session_write_close
            ->expects($this->never());

        $sut = $this->getSut();
        $sut->start(true);
        $sut->close();
    }

    public function testCloseWithSessionNotActiveShouldThrowExceptionInvalidMethodCallException()
    {
        $this->expectException(InvalidMethodCallException::class);

        $this->getSut()->close();
    }

    public function testCloseWithSessionActiveShouldInvokeSessionWriteClose()
    {
        $session_status = $this->getFunctionMock('Acfo\Session', 'session_status');
        $session_status
            ->expects($this->once())
            ->willReturn(PHP_SESSION_NONE);
        $session_start = $this->getFunctionMock('Acfo\Session', 'session_start');
        $options = ['read_and_close' => 0];
        $session_start
            ->expects($this->once())
            ->with($options)
            ->willReturn(true);
        $session_write_close = $this->getFunctionMock('Acfo\Session', 'session_write_close');
        $session_write_close
            ->expects($this->once());

        $sut = $this->getSut();
        $sut->start(false);
        $sut->close();
    }
}
