<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class HelpTest extends TestCase
{
    public function testCreate()
    {
        $help = new Help();
        $this->assertNotNull($help);
    }

    public function testGetTitle()
    {
        $title = Help::getTitle('api-token-user');
        $this->assertEquals('API Token User', $title);

        $title = Help::getTitle('batch-size');
        $this->assertEquals('Batch Size', $title);
    }

    public function testGetDefaultHelp()
    {
        $help = Help::getDefaultHelp('email-errors');
        $this->assertNotNull($help, 'Help not null');
    }

    public function testGetCustomHelp()
    {
        $topic = 'batch-size';
        $expectedHelp = 'The batch size indicates how many record IDs are processed at a time.';

        $this->getMockBuilder('ExternalModules\AbstractExternalModule')->getMock();

        $moduleMock = $this->createMock(RedCapEtlModule::class);
        $moduleMock->expects($this->any())->method('getCustomHelp')
            ->with($topic)->will($this->returnValue($expectedHelp));

        $help = Help::getCustomHelp($topic, $moduleMock);
        $this->assertNotNull($help, 'Help not null');

        $this->assertEquals($expectedHelp, $help, 'Help text check');
    }

    public function testGetTopics()
    {
        $topics = Help::getTopics();
        $this->assertNotNull($topics, 'Topics not null');

        $this->assertTrue(is_array($topics), 'Topics are array');

        $this->assertTrue(in_array('batch-size', $topics), 'Topics contains batch-size');
    }
}
