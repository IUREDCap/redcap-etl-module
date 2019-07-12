<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

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

    public function testSanitizeInt()
    {
        $text = '123<scrtip>alert("xss");</script>';
        $sanitizedText = Filter::sanitizeInt($text);
        $expectedResult = '123';
        $this->assertEquals($expectedResult, $sanitizedText, 'Sanitized int check');
    }
}
