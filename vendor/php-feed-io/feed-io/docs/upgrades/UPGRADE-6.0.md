# UPGRADE FROM 5.x to 6.0

Several major changes in version 6.0:
 - Requires PHP 8.1
 - The factory has been removed. Use `new` to construct your FeedIO instance: `new \FeedIo\FeedIo($client, $logger)`
 - Feed IO comes no longer bundled with a default HTTP client, but uses HTTPlug instead. To continue using Guzzle, please require `php-http/guzzle7-adapter`.
 - Feed IO does no longer set a custom user agent. However, HTTP clients usually add a default themselves. If the feed you want to read requires a specific user agent, please configure your HTTP client accordingly, before you inject it into Feed IO. 

## Migrating from Factory

### Before (5.x)
```php
use \FeedIo\Factory;

$feedIo = Factory::create()->getFeedIo();
```

### After (6.0+)
```php
use \FeedIo\Adapter\Http\Client;
use \Http\Discovery\Psr18ClientDiscovery;

$client = new Client(Psr18ClientDiscovery::find());
$feedIo = new \FeedIo\FeedIo($client);
```

### With Logging (6.0+)
```php
use \FeedIo\Adapter\Http\Client;
use \Http\Discovery\Psr18ClientDiscovery;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$client = new Client(Psr18ClientDiscovery::find());
$logger = new Logger('feed-io', [new StreamHandler('php://stdout')]);
$feedIo = new \FeedIo\FeedIo($client, $logger);
```

### With Guzzle Client (6.0+)
```php
use \FeedIo\Adapter\Guzzle\Client;
use \GuzzleHttp\Client as GuzzleClient;

$client = new Client(new GuzzleClient());
$feedIo = new \FeedIo\FeedIo($client);
``` 
