<?php

declare(strict_types=1);

namespace FeedIo\Adapter\Http;

use DateTime;
use FeedIo\Adapter\ClientInterface;
use FeedIo\Adapter\NotFoundException;
use FeedIo\Adapter\ResponseInterface;
use FeedIo\Adapter\ServerErrorException;
use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as PsrClientInterface;

class Client implements ClientInterface
{
    private const MAX_REDIRECTS = 10;

    public function __construct(private readonly PsrClientInterface $client)
    {
    }

    /**
     * @param string $url
     * @param DateTime|null $modifiedSince
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    public function getResponse(string $url, ?DateTime $modifiedSince = null): ResponseInterface
    {
        if ($modifiedSince) {
            $headResponse = $this->request('HEAD', $url, $modifiedSince);
            if (304 === $headResponse->getStatusCode()) {
                return $headResponse;
            }
        }

        return $this->request('GET', $url, $modifiedSince);
    }

    /**
     * @param string $method
     * @param string $url
     * @param DateTime|null $modifiedSince
     * @param int $redirectCount
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    protected function request(
        string $method,
        string $url,
        ?DateTime $modifiedSince = null,
        int $redirectCount = 0
    ): ResponseInterface {
        if ($redirectCount >= self::MAX_REDIRECTS) {
            throw new ServerErrorException(
                new \Nyholm\Psr7\Response(508, [], 'Too many redirects'),
                0
            );
        }

        $headers = [];

        if ($modifiedSince) {
            $headers['If-Modified-Since'] = $modifiedSince->format(DateTime::RFC2822);
        }

        $request = new Request($method, $url, $headers);

        $timeStart = microtime(true);
        $psrResponse = $this->client->sendRequest($request);
        $duration = microtime(true) - $timeStart;

        switch ($psrResponse->getStatusCode()) {
            case 200:
            case 304:
                return new Response($psrResponse, $duration);
            case 301:
            case 302:
            case 307:
            case 308:
                return $this->handleRedirect(
                    $method,
                    $url,
                    $psrResponse,
                    $modifiedSince,
                    $redirectCount,
                    $duration
                );
            case 303:
                // 303 See Other: change POST/PUT/DELETE to GET, but preserve HEAD
                $redirectMethod = $method === 'HEAD' ? 'HEAD' : 'GET';
                return $this->handleRedirect(
                    $redirectMethod,
                    $url,
                    $psrResponse,
                    $modifiedSince,
                    $redirectCount,
                    $duration
                );
            case 404:
                throw new NotFoundException('not found', $duration);
            default:
                throw new ServerErrorException($psrResponse, $duration);
        }
    }

    /**
     * Handle HTTP redirect responses
     *
     * @param string $method
     * @param string $currentUrl
     * @param \Psr\Http\Message\ResponseInterface $psrResponse
     * @param DateTime|null $modifiedSince
     * @param int $redirectCount
     * @param float $duration Duration of the redirect request (used for error reporting)
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    protected function handleRedirect(
        string $method,
        string $currentUrl,
        \Psr\Http\Message\ResponseInterface $psrResponse,
        ?DateTime $modifiedSince,
        int $redirectCount,
        float $duration
    ): ResponseInterface {
        $location = $psrResponse->getHeaderLine('Location');

        if (empty($location)) {
            throw new ServerErrorException($psrResponse, $duration);
        }

        // Handle relative URLs
        $redirectUrl = $this->resolveRedirectUrl($currentUrl, $location);

        return $this->request($method, $redirectUrl, $modifiedSince, $redirectCount + 1);
    }

    /**
     * Resolve potentially relative redirect URL to absolute URL
     *
     * @param string $currentUrl
     * @param string $location
     * @return string
     * @throws ServerErrorException
     */
    protected function resolveRedirectUrl(string $currentUrl, string $location): string
    {
        // Check if location has a scheme (absolute URL or potentially malicious scheme)
        if (preg_match('/^([a-z][a-z0-9+.-]*):(?:\/\/)?/i', $location, $matches)) {
            $scheme = strtolower($matches[1]);
            if (!in_array($scheme, ['http', 'https'], true)) {
                throw new ServerErrorException(
                    new \Nyholm\Psr7\Response(400, [], 'Invalid redirect scheme: ' . $scheme),
                    0
                );
            }
            return $location;
        }

        // Parse current URL
        $parts = parse_url($currentUrl);
        if (!$parts) {
            throw new ServerErrorException(
                new \Nyholm\Psr7\Response(500, [], 'Invalid URL'),
                0
            );
        }

        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        // Handle absolute path (starts with /)
        if (str_starts_with($location, '/')) {
            $normalizedPath = $this->normalizePath($location);
            return "{$scheme}://{$host}{$port}{$normalizedPath}";
        }

        // Handle relative path
        $currentPath = $parts['path'] ?? '/';
        $basePath = dirname($currentPath);
        if ($basePath === '.') {
            $basePath = '/';
        }

        // Combine base path with relative location
        $separator = str_ends_with($basePath, '/') ? '' : '/';
        $combinedPath = "{$basePath}{$separator}{$location}";
        
        // Normalize the path to resolve . and .. segments
        $normalizedPath = $this->normalizePath($combinedPath);

        return "{$scheme}://{$host}{$port}{$normalizedPath}";
    }

    /**
     * Normalize a URL path by resolving . and .. segments
     *
     * @param string $path
     * @return string
     */
    protected function normalizePath(string $path): string
    {
        // Split path into segments
        $segments = explode('/', $path);
        $normalized = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                // Skip empty segments and current directory references
                if ($segment === '' && empty($normalized)) {
                    // Keep leading slash
                    $normalized[] = '';
                }
                continue;
            } elseif ($segment === '..') {
                // Go up one directory
                if (!empty($normalized) && end($normalized) !== '' && end($normalized) !== '..') {
                    array_pop($normalized);
                } elseif (empty($normalized) || end($normalized) === '..') {
                    // Can't go above root or if we're already tracking ..
                    $normalized[] = '..';
                }
            } else {
                $normalized[] = $segment;
            }
        }

        // Reconstruct path
        $result = implode('/', $normalized);
        
        // Ensure absolute paths start with /
        if (str_starts_with($path, '/') && !str_starts_with($result, '/')) {
            $result = '/' . $result;
        }

        return $result ?: '/';
    }
}

