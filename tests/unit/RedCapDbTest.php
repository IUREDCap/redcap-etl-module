<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class RedCapDbTest extends TestCase
{
    public function testCreate()
    {
        $redCapDb = new RedCapDb();
        $this->assertNotNull($redCapDb);
    }

    public function testsCommitException()
    {
        $db = new RedCapDb();

        $query = 'COMMIT';
        $isException = true;
        RedCapFunctions::addDbQueryResult($query, true, 'Database commit error');

        $commit = true;

        $exceptionCaught = false;
        try {
            $db->endTransaction($commit);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
            print $exception->getMessage() . "\n";
        }
        # Test that if a commit causes an exception,
        # the endTransaction method does NOT throw an exception
        $this->assertFalse($exceptionCaught);

        $lastQuery = RedCapFunctions::getLastQuery();
        $this->assertEquals('SET AUTOCOMMIT=1', $lastQuery, 'Autocommit reset test');
    }
}
