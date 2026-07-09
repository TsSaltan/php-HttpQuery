<?php
namespace HttpQuery\Tests;

use PHPUnit\Framework\TestCase;
use HttpQuery\HttpQuery;

class IPInfoTest extends TestCase
{
    public function testIPInfo(): void
    {
        $q = new HttpQuery('https://ipinfo.io/json');
		$res = $q->get();
        $this->assertTrue(isset($res->json['ip']));
    }
}