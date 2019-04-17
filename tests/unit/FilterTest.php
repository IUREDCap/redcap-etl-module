<?php

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
    public function setup()
    {
    }

    public function testCreate()
    {
        $filter = new Filter();
        $this->assertNotNull($filter);
    }

    public function testEscapeForHtmlAttribute()
    {
        $text = 'test';
        $escapedText = Filter::escapeForHtmlAttribute($text);
        $this->assertEquals($text, $escapedText);
    }
}
