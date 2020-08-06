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
 * Class for interacting with the admin "Config" page.
 * The methods of this class assume that the current page is an admin "Config" page.
 */
class AdminConfigPage
{
    /**
     * Indicates if cron jobs are allowed.
     */
    public static function areCronJobsAllowed($session)
    {
        $page = $session->getPage();
        $cronAllowed = $page->hasCheckedField('allowCron');
        return $cronAllowed;
    }

    /**
     * Indicates if on-demand jobs are allowed.
     */
    public static function areOnDemandJobsAllowed($session)
    {
        $page = $session->getPage();
        $cronAllowed = $page->hasCheckedField('allowOnDemand');
        return $cronAllowed;
    }
}
