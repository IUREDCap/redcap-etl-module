<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class AdminConfigTest extends TestCase
{
    public function testCreate()
    {
        $adminConfig = new AdminConfig();
        $this->assertNotNull($adminConfig, 'Object creation test');

        $caCertFile = $adminConfig->getCaCertFile();
        $this->assertNull($caCertFile, 'CA cert file null check');


        $expectedCaCertFile = '/tmp/cacert.pem';

        $properties = array();
        $properties[AdminConfig::ALLOWED_CRON_TIMES] = array([0 => 'on'], [], [], [], [], [], []);
        $properties[AdminConfig::CA_CERT_FILE] = $expectedCaCertFile;
        $adminConfig->set($properties);

        $allowed = $adminConfig->isAllowedCronTime(1, 12);
        $this->assertFalse($allowed, 'Allowed cron time false test');

        $allowed = $adminConfig->isAllowedCronTime(0, 0);
        $this->assertTrue($allowed, 'Allowed cron time true test');

        $caCertFile = $adminConfig->getCaCertFile();
        $this->assertEquals($expectedCaCertFile, $caCertFile, 'CA cert file check');

        # SSL verify - should be false, because no value was provided for properties in set call
        $sslVerify = $adminConfig->getSslVerify();
        $this->assertFalse($sslVerify, 'SSL verify check');
    }
}
