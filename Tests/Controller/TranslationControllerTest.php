<?php

namespace Regelwerk\TranslationBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase {

    public function testIndex() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/translator/de');
        $this->assertTrue($crawler->filter('html:contains("regelwerk_translation")')->count() > 0);
        $crawler = $client->request('GET', '/translator/de/domain/regelwerk_translation');
        $this->assertTrue($crawler->filter('html:contains("regelwerk_translation")')->count() > 0);
        
    }

}
