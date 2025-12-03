<?php
/*
 * This file is part of the feed-io package.
 *
 * (c) Alexandre Debril <alex.debril@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FeedIo\Rule\Atom;

use FeedIo\Feed\Item;

use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    /**
     * @var Link
     */
    protected $object;

    public const LINK = 'http://localhost';

    protected function setUp(): void
    {
        $this->object = new Link();
    }

    public function testSet()
    {
        $item = new Item();
        $document = new \DOMDocument();

        $link = $document->createElement('link');
        $link->setAttribute('href', 'http://localhost');
        $this->object->setProperty($item, $link);
        $this->assertEquals('http://localhost', $item->getLink());
    }

    public function testCreateElement()
    {
        $item = new Item();
        $item->setLink(self::LINK);

        $document = new \DOMDocument();
        $rootElement = $document->createElement('feed');
        $this->object->apply($document, $rootElement, $item);

        $addedElement = $rootElement->firstChild;
        $this->assertInstanceOf('\DomElement', $addedElement);
        $this->assertEquals(self::LINK, $addedElement->getAttribute('href'));
        $this->assertEquals('link', $addedElement->nodeName);

        $document->appendChild($rootElement);

        $this->assertXmlStringEqualsXmlString(
            '<feed><link href="http://localhost"/></feed>',
            $document->saveXML()
        );
    }

    public function testRelativeHrefWithoutLeadingSlash()
    {
        $item = new Item();
        $item->setLink('https://example.com/some/path.html');
        $document = new \DOMDocument();

        $link = $document->createElement('link');
        $link->setAttribute('href', 'page.html');
        $link->setAttribute('rel', 'alternate');
        $this->object->setProperty($item, $link);
        
        $this->assertEquals('https://example.com/page.html', $item->getLink());
    }

    public function testRelativeHrefWithLeadingSlash()
    {
        $item = new Item();
        $item->setLink('https://example.com/some/path.html');
        $document = new \DOMDocument();

        $link = $document->createElement('link');
        $link->setAttribute('href', '/absolute/path.html');
        $link->setAttribute('rel', 'alternate');
        $this->object->setProperty($item, $link);
        
        $this->assertEquals('https://example.com/absolute/path.html', $item->getLink());
    }

    public function testAbsoluteHrefIsNotModified()
    {
        $item = new Item();
        $item->setLink('https://example.com/some/path.html');
        $document = new \DOMDocument();

        $link = $document->createElement('link');
        $link->setAttribute('href', 'https://other.com/page.html');
        $link->setAttribute('rel', 'alternate');
        $this->object->setProperty($item, $link);
        
        $this->assertEquals('https://other.com/page.html', $item->getLink());
    }

    public function testNonAlternateLinkIsIgnored()
    {
        $item = new Item();
        $item->setLink('https://example.com/original.html');
        $document = new \DOMDocument();

        $link = $document->createElement('link');
        $link->setAttribute('href', '/new/path.html');
        $link->setAttribute('rel', 'stylesheet');
        $this->object->setProperty($item, $link);
        
        $this->assertEquals('https://example.com/original.html', $item->getLink());
    }

    public function testLinkWithoutRelAttributeWhenNodeLinkIsNull()
    {
        $item = new Item();
        $item->setLink(null);
        $document = new \DOMDocument();

        $link = $document->createElement('link');
        $link->setAttribute('href', '/path.html');
        $this->object->setProperty($item, $link);
        
        $this->assertEquals('/path.html', $item->getLink());
    }

    public function testLinkWithNullBaseUrl()
    {
        $item = new Item();
        $item->setLink(null);
        $document = new \DOMDocument();

        $link = $document->createElement('link');
        $link->setAttribute('href', 'relative.html');
        $link->setAttribute('rel', 'alternate');
        $this->object->setProperty($item, $link);
        
        $this->assertEquals('relative.html', $item->getLink());
    }

    public function testProtocolRelativeUrl()
    {
        $item = new Item();
        $item->setLink('https://example.com/path.html');
        $document = new \DOMDocument();

        $link = $document->createElement('link');
        $link->setAttribute('href', '//cdn.example.com/resource.css');
        $link->setAttribute('rel', 'alternate');
        $this->object->setProperty($item, $link);
        
        $this->assertEquals('//cdn.example.com/resource.css', $item->getLink());
    }

    public function testFragmentUrl()
    {
        $item = new Item();
        $item->setLink('https://example.com/page.html');
        $document = new \DOMDocument();

        $link = $document->createElement('link');
        $link->setAttribute('href', '#section1');
        $link->setAttribute('rel', 'alternate');
        $this->object->setProperty($item, $link);
        
        $this->assertEquals('https://example.com/#section1', $item->getLink());
    }

    public function testQueryParameterUrl()
    {
        $item = new Item();
        $item->setLink('https://example.com/page.html');
        $document = new \DOMDocument();

        $link = $document->createElement('link');
        $link->setAttribute('href', '?param=value');
        $link->setAttribute('rel', 'alternate');
        $this->object->setProperty($item, $link);
        
        $this->assertEquals('https://example.com/?param=value', $item->getLink());
    }

    public function testHttpScheme()
    {
        $item = new Item();
        $item->setLink('http://example.com/path.html');
        $document = new \DOMDocument();

        $link = $document->createElement('link');
        $link->setAttribute('href', '/secure/path.html');
        $link->setAttribute('rel', 'alternate');
        $this->object->setProperty($item, $link);
        
        $this->assertEquals('http://example.com/secure/path.html', $item->getLink());
    }

    public function testEmptyHref()
    {
        $item = new Item();
        $item->setLink('https://example.com/original.html');
        $document = new \DOMDocument();

        $link = $document->createElement('link');
        $link->setAttribute('href', '');
        $link->setAttribute('rel', 'alternate');
        $this->object->setProperty($item, $link);
        
        $this->assertEquals('https://example.com/', $item->getLink());
    }
}
