#Session

Simple strict typed session class which supports read only (read_and_close) and lazy loading.
This package also provides a useful PSR-7 compatible middleware, e.g. for the Slim Framework 3.x, for starting a session.

###Installation

```command
composer require acfo/session
```

###Usage

Add the session class an middleware to your dependency injection container (e.g. Pimple):

```php
$container[\Acfo\Session\Session::class] = function () {
    return new \Acfo\Session\SessionImpl();
};

$container[\Acfo\Session\Middleware\Slim3\SessionMiddleware] = function ($container) {
    return new \Acfo\Session\Middleware\Slim3\SessionMiddleware(
        $container->get(\Acfo\Session\Session::class)
    );
};
```

Add middleware (e.g. Slim 3.x): 

```php
$app->add(\Acfo\Session\Middleware\Slim3\SessionMiddleware::class);
```

This setup will allow read and write operations and not start the session before it is actually used, e.g.:

```php
$session = $container->get(\Acfo\Session\Session::class);

$value = $session->get('key'); // lazy load: first access will invoke start_session

$session->set('key', 1234);

$session->regenerate();

$session->delete('key');

$session->deleteAll();

$session->close(); // call close if you want to end a read/write session as soon as possible. 
 
```

###Advanced usage 

The session class supports starting the session with the read_and_close flag introduced with PHP 7.0.
This feature can help keep the time a session is locked for a specific user to the necessary minimum. 
This is especially useful for asynchronous web applications which generally do not 
write data in a GET-Request.   

To specify which requests only need read access to the session data, pass a list of 
objects implementing the ReadOnlySessionStrategy interface as a parameter of the
the middleware constructor, e.g.: 

```php
class GetRequestReadOnlySessionStrategy implements ReadOnlySessionStrategy
{
    public function isReadOnly(ServerRequestInterface $request): bool
    {
        return $request->getMethod() == 'GET';
    }
}

$container[\Acfo\Session\Middleware\Slim3\SessionMiddleware] = function ($container) {
    $readOnlySessionStrategies = [
        new GetRequestReadOnlySessionStrategy()
    ];

    return new \Acfo\Session\Middleware\Slim3\SessionMiddleware(
        $container->get(\Acfo\Session\Session::class),
        $readOnlySessionStrategies
    );
};

$app->add(\Acfo\Session\Middleware\Slim3\SessionMiddleware::class);
```

The default settings for sessions are suitable for most applications. 
Should you need to adjust a setting, the most efficient way would be to edit the php.ini file.
If you cannot or do not want to edit the php.ini file you can pass a list of settings to
the SessionMiddleware.

```php
$settings = \Acfo\Session\Middleware\Slim3\SessionMiddleware::RECOMMENDED_SETTINGS;

$container[\Acfo\Session\Middleware\Slim3\SessionMiddleware] = function ($container) {
    $settings = \Acfo\Session\Middleware\Slim3\SessionMiddleware::RECOMMENDED_SETTINGS;

    return new \Acfo\Session\Middleware\Slim3\SessionMiddleware(
        $container->get(\Acfo\Session\Session::class),
        null,
        $settings
    );
};


$app->add(\Acfo\Session\Middleware\Slim3\SessionMiddleware::class);
```

Enjoy!