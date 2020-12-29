# Instagram PHP Scraper (proxified)
This library is based on the Instagram web version. We develop it because nowadays it is hard to get an approved Instagram application. The purpose is to support every feature that the web desktop and mobile version support. 

**This branch contains RapidApi proxified version of scraper due to difficulties with bypassing Instagram firewalls from regular data center ip ranges.**

RapidAPI proxy handles balancing and bypasses Instagram firewalls intelligently.
Currently only public methods (not requiring auth, like analyzing public accounts and their posts and comments) of API are proxified, since Instagram may track ip addresses of login and mark your account as suspicious when proxy balancer switches ip address. So use withCredentials() with caution. 
See examples/getAccountByUsername.php and examples/getAccountMediasByUsername.php as safe examples of using proxified version.

Proxified methods:
- getAccount()
- getAccountById()
- getMedias()
- getMediaByUrl()
- getMediaByCode()

RapidAPI subscription page: https://rapidapi.com/restyler/api/instagram40

## Dependencies

- [PSR-16](http://www.php-fig.org/psr/psr-16/)


## Code Example
```php
$instagram = \InstagramScraper\Instagram();
$instagram->setRapidApiKey('YOUR-RAPID-API-KEY');
$account = $instagram->getAccountById(3);
echo $account->getUsername();
```

Some methods do not require authentication: 
```php
$instagram = new \InstagramScraper\Instagram();
$instagram->setRapidApiKey('YOUR-RAPID-API-KEY');
$nonPrivateAccountMedias = $instagram->getMedias('kevin');
echo $nonPrivateAccountMedias[0]->getLink();
```

If you use authentication it is recommended to cache the user session. In this case you don't need to run the `$instagram->login()` method every time your program runs:

```php
use Phpfastcache\Helper\Psr16Adapter;

$instagram = \InstagramScraper\Instagram::withCredentials('username', 'password', new Psr16Adapter('Files'));
$instagram->login(); // will use cached session if you want to force login $instagram->login(true)
$instagram->saveSession();  //DO NOT forget this in order to save the session, otherwise have no sense
$account = $instagram->getAccountById(3);
echo $account->getUsername();
```

Using proxy for requests **(not needed for public endpoints like getAccount() and getMedias() since they go through RapidAPI, so only makes sense for private endpoints requiring login)**:

```php
$instagram = new \InstagramScraper\Instagram();
Instagram::setProxy([
    'address' => '111.112.113.114',
    'port'    => '8080',
    'tunnel'  => true,
    'timeout' => 30,
]);
// Request with proxy
$account = $instagram->getAccount('kevin');
Instagram::disableProxy();
// Request without proxy
$account = $instagram->getAccount('kevin');
```

## Installation

### Using composer

Since this is a fork which is not yet published as a package to composer, open your composer.json ant put:
```
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/restyler/instagram-php-scraper"
        }
    ],
    "require": {
        "raiym/instagram-php-scraper": "dev-proxified"
    }
}
```
make sure that you put "repositories" and "require" sections in correct parts of composer.json file if it was not empty.
then launch
```
composer update
```


### If you don't have composer
You can download it [here](https://getcomposer.org/download/).

## Examples
See examples [here](https://github.com/postaddictme/instagram-php-scraper/tree/master/examples).

## Other
Java library: https://github.com/postaddictme/instagram-java-scraper
