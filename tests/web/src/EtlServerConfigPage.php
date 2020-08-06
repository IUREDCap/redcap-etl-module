<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule\WebTests;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\SnippetAcceptingContext;

/**
 * Class for interacting with the admin "ETL Server Config" page. Methods assume that
 * the current page is an ETL server configuration page.
 */
class EtlServerConfigPage
{
    /**
     * Indicates if the server is active.
     */
    public static function isActive($session)
    {
        $page = $session->getPage();
        $isActive = $page->hasCheckedField('isActive');
        return $isActive;
    }

    public static function getAccessLevel($session)
    {
        $page = $session->getPage();
        $element = $page->findById('accessLevelId');
        $accessLevel = $element->getValue();
        return $accessLevel;
    }
}
