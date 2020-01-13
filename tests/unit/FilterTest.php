<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
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
    
    public function testSanitizeDate()
    {
        $date = '   12/31/2019 ';
        $sanitizedDate = Filter::sanitizeDate($date);
        $this->assertEquals('12/31/2019', $date, 'Sanitized date check');
    }


    public function testStripTags()
    {
        $text = '<b>This is a test</b>';
        $filteredText = Filter::stripTags($text);
        $this->assertEquals('This is a test', $filteredText, 'Scalar test');
    }

    public function testStripTagsArray()
    {
        $post = [
            'prop1' => '<b>test</b>',
            'prop2' => [
                'prop2_1' => '<i>test21</i>',
                'prop2_2' => [
                    'prop3_1' => '<p>test31</p>', 'prop3_2' => '<em>test32</em>'
                ]
            ]
        ];

        $filteredPost = Filter::stripTagsArrayRecursive($post);

        $expectedPost = [
            'prop1' => 'test',
            'prop2' => [
                'prop2_1' => 'test21',
                'prop2_2' => [
                    'prop3_1' => 'test31', 'prop3_2' => 'test32'
                ]
            ]
        ];

        $this->assertEquals($expectedPost, $filteredPost, 'Array test');
    }
}
