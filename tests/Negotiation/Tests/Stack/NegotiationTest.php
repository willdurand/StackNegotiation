<?php

namespace Negotiation\Tests\Stack;

use Negotiation\Stack\Negotiation;
use Negotiation\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class NegotiationTest extends TestCase
{
    public function testAcceptHeader()
    {
        $app = $this->createStackedApp();
        $req = $this->createRequest(null, [
            'Accept' => 'application/json',
        ]);

        $app->handle($req);

        $header = $req->attributes->get('_accept');
        $this->assertInstanceOf('Negotiation\AcceptHeader', $header);
        $this->assertEquals('application/json', $header->getValue());
    }

    public function testAcceptLanguageHeader()
    {
        $app = $this->createStackedApp();
        $req = $this->createRequest(null, [
            'Accept-Language' => 'en; q=0.1, fr; q=0.4, fu; q=0.9, de; q=0.2',
        ]);

        $app->handle($req);

        $header = $req->attributes->get('_accept_language');
        $this->assertInstanceOf('Negotiation\AcceptHeader', $header);
        $this->assertEquals('fu', $header->getValue());
    }

    /**
     * @dataProvider dataProviderForTestDecodeBody
     */
    public function testDecodeBody($method, $content, $mimeType, $expected)
    {
        $app = $this->createStackedApp();
        $req = $this->createRequest($content, [
            'Content-Type' => $mimeType,
            ]);
        $req->setMethod($method);

        $app->handle($req);

        $this->assertEquals($expected, $req->request->all());
    }

    public function dataProviderForTestDecodeBody()
    {
        return [
            [ 'POST', 'foo', 'application/json', [] ],
            [ 'POST', '<response><foo>bar</foo></response>', 'application/xml', [ 'foo' => 'bar' ] ],
            [ 'POST', '{ "foo": "bar" }', 'application/json', [ 'foo' => 'bar' ] ],
            [ 'PUT', '', 'application/json', [] ],
            [ 'GET', '{ "foo": "bar" }', 'application/json', [] ],
            [ 'GET', '<response><foo>bar</foo></response>', 'application/xml', [] ],
        ];
    }

    private function createStackedApp(array $responseHeaders = [])
    {
        return new Negotiation(new MockApp($responseHeaders));
    }

    private function createRequest($content = null, array $requestHeaders = [])
    {
        $request = new Request([], [], [], [], [], [], $content);
        $request->headers->add($requestHeaders);

        return $request;
    }
}

class MockApp implements HttpKernelInterface
{
    private $responseHeaders;

    public function __construct(array $responseHeaders)
    {
        $this->responseHeaders = $responseHeaders;
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $response = new Response();
        $response->headers->add($this->responseHeaders);

        return $response;
    }
}
