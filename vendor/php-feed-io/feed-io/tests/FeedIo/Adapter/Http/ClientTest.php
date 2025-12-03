<?php

declare(strict_types=1);

namespace FeedIo\Adapter\Http;

use DateTime;
use FeedIo\Adapter\NotFoundException;
use FeedIo\Adapter\ServerErrorException;
use Nyholm\Psr7\Response as PsrResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface as PsrClientInterface;
use Psr\Http\Message\RequestInterface;

class ClientTest extends TestCase
{
    private PsrClientInterface $psrClient;
    private Client $client;

    protected function setUp(): void
    {
        $this->psrClient = $this->createMock(PsrClientInterface::class);
        $this->client = new Client($this->psrClient);
    }

    public function testGetResponseWithSuccess(): void
    {
        $psrResponse = new PsrResponse(200, [], 'feed content');

        $this->psrClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($psrResponse);

        $response = $this->client->getResponse('https://example.com/feed.xml');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('feed content', $response->getBody());
    }

    public function testGetResponseWith304NotModified(): void
    {
        $modifiedSince = new DateTime('2025-01-01');
        
        // HEAD request returns 304
        $headResponse = new PsrResponse(304);

        $this->psrClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($modifiedSince) {
                return $request->getMethod() === 'HEAD' 
                    && $request->hasHeader('If-Modified-Since')
                    && $request->getHeaderLine('If-Modified-Since') === $modifiedSince->format(DateTime::RFC2822);
            }))
            ->willReturn($headResponse);

        $response = $this->client->getResponse('https://example.com/feed.xml', $modifiedSince);

        $this->assertEquals(304, $response->getStatusCode());
    }

    public function testGetResponseWithModifiedSinceButFeedChanged(): void
    {
        $modifiedSince = new DateTime('2025-01-01');
        
        // HEAD request returns 200 (modified)
        $headResponse = new PsrResponse(200);
        // GET request also returns 200 with content
        $getResponse = new PsrResponse(200, [], 'new feed content');

        $this->psrClient
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls($headResponse, $getResponse);

        $response = $this->client->getResponse('https://example.com/feed.xml', $modifiedSince);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('new feed content', $response->getBody());
    }

    public function testGetResponseThrowsNotFoundOn404(): void
    {
        $psrResponse = new PsrResponse(404);

        $this->psrClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($psrResponse);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('not found');

        $this->client->getResponse('https://example.com/feed.xml');
    }

    public function testGetResponseThrowsServerErrorOnServerError(): void
    {
        $psrResponse = new PsrResponse(500);

        $this->psrClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($psrResponse);

        $this->expectException(ServerErrorException::class);

        $this->client->getResponse('https://example.com/feed.xml');
    }

    /**
     * @dataProvider redirectStatusCodeProvider
     */
    public function testGetResponseFollowsRedirects(int $statusCode): void
    {
        $redirectResponse = new PsrResponse($statusCode, ['Location' => 'https://example.com/new-feed.xml']);
        $finalResponse = new PsrResponse(200, [], 'redirected feed content');

        $this->psrClient
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls($redirectResponse, $finalResponse);

        $response = $this->client->getResponse('https://example.com/old-feed.xml');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('redirected feed content', $response->getBody());
    }

    public static function redirectStatusCodeProvider(): array
    {
        return [
            '301 Moved Permanently' => [301],
            '302 Found' => [302],
            '303 See Other' => [303],
            '307 Temporary Redirect' => [307],
            '308 Permanent Redirect' => [308],
        ];
    }

    public function testGetResponseFollowsMultipleRedirects(): void
    {
        $redirect1 = new PsrResponse(301, ['Location' => 'https://example.com/redirect2.xml']);
        $redirect2 = new PsrResponse(302, ['Location' => 'https://example.com/final.xml']);
        $finalResponse = new PsrResponse(200, [], 'final content');

        $this->psrClient
            ->expects($this->exactly(3))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls($redirect1, $redirect2, $finalResponse);

        $response = $this->client->getResponse('https://example.com/start.xml');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('final content', $response->getBody());
    }

    public function testGetResponsePreservesModifiedSinceThroughRedirects(): void
    {
        $modifiedSince = new DateTime('2025-01-01');
        
        // HEAD request with If-Modified-Since returns redirect
        $headRedirect = new PsrResponse(301, ['Location' => 'https://example.com/new-location.xml']);
        // HEAD request to new location returns 200
        $headResponse = new PsrResponse(200);
        // GET request to new location
        $getResponse = new PsrResponse(200, [], 'content');

        $this->psrClient
            ->expects($this->exactly(3))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls($headRedirect, $headResponse, $getResponse);

        $response = $this->client->getResponse('https://example.com/old-location.xml', $modifiedSince);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetResponseWithEmptyLocationHeaderThrowsException(): void
    {
        $redirectResponse = new PsrResponse(301, ['Location' => '']);

        $this->psrClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($redirectResponse);

        $this->expectException(ServerErrorException::class);
        
        $this->client->getResponse('https://example.com/feed.xml');
    }

    public function testResponseDurationIsTracked(): void
    {
        $psrResponse = new PsrResponse(200, [], 'content');

        $this->psrClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($psrResponse);

        $response = $this->client->getResponse('https://example.com/feed.xml');

        $this->assertIsFloat($response->getDuration());
        $this->assertGreaterThanOrEqual(0, $response->getDuration());
    }

    public function testResponseDurationIsTrackedOnError(): void
    {
        $psrResponse = new PsrResponse(404);

        $this->psrClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($psrResponse);

        try {
            $this->client->getResponse('https://example.com/feed.xml');
            $this->fail('Expected NotFoundException to be thrown');
        } catch (NotFoundException $e) {
            $this->assertIsFloat($e->getDuration());
            $this->assertGreaterThanOrEqual(0, $e->getDuration());
        }
    }

    public function test303RedirectPreservesHeadMethod(): void
    {
        // Directly test that a 303 redirect preserves HEAD method
        // We'll use reflection to call the protected request() method with HEAD
        
        $redirectResponse = new PsrResponse(303, ['Location' => 'https://example.com/new-feed.xml']);
        $finalResponse = new PsrResponse(200, [], 'content');

        $requestCount = 0;
        $this->psrClient
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use ($redirectResponse, $finalResponse, &$requestCount) {
                $requestCount++;
                
                if ($requestCount === 1) {
                    // First request: HEAD
                    $this->assertEquals('HEAD', $request->getMethod());
                    return $redirectResponse;
                }
                
                // Second request: should still be HEAD (not changed to GET for 303)
                $this->assertEquals('HEAD', $request->getMethod());
                $this->assertEquals('https://example.com/new-feed.xml', (string) $request->getUri());
                return $finalResponse;
            });

        // Use reflection to call protected request() method with HEAD
        $reflection = new \ReflectionClass($this->client);
        $method = $reflection->getMethod('request');
        $method->setAccessible(true);
        
        $response = $method->invoke($this->client, 'HEAD', 'https://example.com/old-feed.xml', null, 0);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @dataProvider maliciousSchemeProvider
     */
    public function testRejectsRedirectsWithMaliciousSchemes(string $maliciousUrl): void
    {
        $redirectResponse = new PsrResponse(301, ['Location' => $maliciousUrl]);

        $this->psrClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($redirectResponse);

        $this->expectException(ServerErrorException::class);

        $this->client->getResponse('https://example.com/feed.xml');
    }

    public static function maliciousSchemeProvider(): array
    {
        return [
            'file scheme' => ['file:///etc/passwd'],
            'ftp scheme' => ['ftp://malicious.com/data'],
            'javascript scheme' => ['javascript:alert(1)'],
            'data scheme' => ['data:text/html,<script>alert(1)</script>'],
            'mailto scheme' => ['mailto:test@example.com'],
            'tel scheme' => ['tel:+1234567890'],
        ];
    }

    public function testAllowsHttpAndHttpsRedirects(): void
    {
        $httpRedirect = new PsrResponse(301, ['Location' => 'http://example.com/feed.xml']);
        $httpsRedirect = new PsrResponse(301, ['Location' => 'https://secure.example.com/feed.xml']);
        $finalResponse = new PsrResponse(200, [], 'content');

        $this->psrClient
            ->expects($this->exactly(3))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls($httpRedirect, $httpsRedirect, $finalResponse);

        $response = $this->client->getResponse('https://example.com/feed.xml');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testNormalizesRelativePathWithDotDotSegments(): void
    {
        // Current URL: https://example.com/path/to/feed.xml
        // Redirect to: ../newpath/feed.xml
        // Should resolve to: https://example.com/path/newpath/feed.xml
        
        $redirectResponse = new PsrResponse(301, ['Location' => '../newpath/feed.xml']);
        $finalResponse = new PsrResponse(200, [], 'content');

        $requestCount = 0;
        $this->psrClient
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use ($redirectResponse, $finalResponse, &$requestCount) {
                $requestCount++;
                
                if ($requestCount === 1) {
                    $this->assertEquals('https://example.com/path/to/feed.xml', (string) $request->getUri());
                    return $redirectResponse;
                }
                
                // Verify the path was properly normalized
                $this->assertEquals('https://example.com/path/newpath/feed.xml', (string) $request->getUri());
                return $finalResponse;
            });

        $response = $this->client->getResponse('https://example.com/path/to/feed.xml');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testNormalizesAbsolutePathWithDotDotSegments(): void
    {
        // Redirect to: /path/../other/feed.xml
        // Should resolve to: /other/feed.xml
        
        $redirectResponse = new PsrResponse(301, ['Location' => '/path/../other/feed.xml']);
        $finalResponse = new PsrResponse(200, [], 'content');

        $requestCount = 0;
        $this->psrClient
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use ($redirectResponse, $finalResponse, &$requestCount) {
                $requestCount++;
                
                if ($requestCount === 1) {
                    return $redirectResponse;
                }
                
                // Verify the path was properly normalized
                $this->assertEquals('https://example.com/other/feed.xml', (string) $request->getUri());
                return $finalResponse;
            });

        $response = $this->client->getResponse('https://example.com/some/path.xml');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testNormalizesPathWithDotSegments(): void
    {
        // Redirect to: /path/./to/./feed.xml
        // Should resolve to: /path/to/feed.xml
        
        $redirectResponse = new PsrResponse(301, ['Location' => '/path/./to/./feed.xml']);
        $finalResponse = new PsrResponse(200, [], 'content');

        $requestCount = 0;
        $this->psrClient
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use ($redirectResponse, $finalResponse, &$requestCount) {
                $requestCount++;
                
                if ($requestCount === 1) {
                    return $redirectResponse;
                }
                
                // Verify the path was properly normalized
                $this->assertEquals('https://example.com/path/to/feed.xml', (string) $request->getUri());
                return $finalResponse;
            });

        $response = $this->client->getResponse('https://example.com/old.xml');
        $this->assertEquals(200, $response->getStatusCode());
    }
}
