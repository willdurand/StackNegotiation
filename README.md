StackNegotiation
================

[![Build
Status](https://travis-ci.org/willdurand/StackNegotiation.png?branch=master)](http://travis-ci.org/willdurand/StackNegotiation)
[![Latest Stable
Version](https://poser.pugx.org/willdurand/stack-negotiation/v/stable.png)](https://packagist.org/packages/willdurand/stack-negotiation)

[Stack](http://stackphp.com) middleware for content negotiation.


Installation
------------

The recommended way to install StackNegotiation is through
[Composer](http://getcomposer.org/):

``` json
{
    "require": {
        "willdurand/stack-negotiation": "@stable"
    }
}
```

**Protip:** you should browse the
[`willdurand/stack-negotiation`](https://packagist.org/packages/willdurand/stack-negotiation)
page to choose a stable version to use, avoid the `@stable` meta constraint.


Usage
-----

```php
use Negotiation\Stack\Negotiation;

$app = new Negotiation($app);
```

### Headers

#### `Accept` Header

This middleware adds a `_accept` attribute to the request, containing a
`AcceptHeader` object (see:
[Negotiation](https://github.com/willdurand/Negotiation) library).

#### `Accept-Language` Header

This middleware adds a `_accept_language` attribute to the request, containing a
`AcceptHeader` object (see:
[Negotiation](https://github.com/willdurand/Negotiation) library).

#### `Content-Type` Header

This middleware is able to decode a request body, and fill in request data. It
is inspired by Silex's recipe [Accepting a JSON Request
Body](http://silex.sensiolabs.org/doc/cookbook/json_request_body.html) and
[FOSRestBundle Body
Listener](https://github.com/FriendsOfSymfony/FOSRestBundle/blob/master/Resources/doc/3-listener-support.md#body-listener).


Unit Tests
----------

Setup the test suite using Composer:

    $ composer install --dev

Run it using PHPUnit:

    $ ./vendor/bin/phpunit


Contributing
------------

See
[CONTRIBUTING](https://github.com/willdurand/StackNegotiation/blob/master/CONTRIBUTING.md)
file.


License
-------

StackNegotiation is released under the MIT License. See the bundled LICENSE file
for details.
