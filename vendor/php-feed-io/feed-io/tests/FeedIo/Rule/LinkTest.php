<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 31/10/14
 * Time: 12:14
 */

namespace FeedIo\Rule;

use FeedIo\Feed\Item;

use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    /**
     * @var \FeedIo\Rule\Link
     */
    protected $object;

    public const LINK = 'http://localhost';

    protected function setUp(): void
    {
        $this->object = new Link();
    }

    public function testGetNodeName()
    {
        $this->assertEquals('link', $this->object->getNodeName());
    }

    public function testSet()
    {
        $item = new Item();

        $this->object->setProperty($item, new \DOMElement('link', self::LINK));
        $this->assertEquals(self::LINK, $item->getLink());
    }

    public function testCreateElement()
    {
        $item = new Item();
        $item->setLink(self::LINK);

        $document = new \DOMDocument();
        $rootElement = $document->createElement('feed');

        $this->object->apply($document, $rootElement, $item);

        $addedElement = $rootElement->firstChild;

        $this->assertEquals(self::LINK, $addedElement ->nodeValue);
        $this->assertEquals('link', $addedElement ->nodeName);

        $document->appendChild($rootElement);

        $this->assertXmlStringEqualsXmlString('<feed><link>' . self::LINK .'</link></feed>', $document->saveXML());
    }

    public function testSetWithWhitespace()
    {
        $item = new Item();
        // No initial link needed since we're testing absolute URLs with whitespace

        // Test URL with leading/trailing whitespace (like in RSS feeds)
        $urlWithWhitespace = "\nhttps://www.somedomain.de/test-article/view/news/123456";
        $expectedUrl = 'https://www.somedomain.de/test-article/view/news/123456';

        $this->object->setProperty($item, new \DOMElement('link', $urlWithWhitespace));
        $this->assertEquals($expectedUrl, $item->getLink());
    }

    public function testSetWithRelativeUrl()
    {
        $item = new Item();
        $item->setLink('https://example.com/base');

        // Test relative URL should still work correctly
        $relativeUrl = '/test-path';
        $expectedUrl = 'https://example.com/test-path';

        $this->object->setProperty($item, new \DOMElement('link', $relativeUrl));
        $this->assertEquals($expectedUrl, $item->getLink());
    }
}
