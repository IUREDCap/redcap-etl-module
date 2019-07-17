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
 * Class for interacting with the user "Schedule" page.
 */
class SchedulePage
{
    public static function scheduleForNextHour($session)
    {
        $page = $session->getPage();

        $now = new \DateTime();
        $day     = $now->format('w');  // 0-6 (day of week; Sunday = 0)
        $hour    = $now->format('G');  // 0-23 (24-hour format without leading zeroes)

        if ($hour < 23) {
            $hour++;
        } else {
            if ($day < 6) {
                $day++;
                $hour = 0;
            } else {
                $day = 0;
                $hour++;
            }
        }

        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $dayName = $days[$day];

        $page->fillField($dayName, $hour);
    }
}
