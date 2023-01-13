<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Rules;

use PHPUnit\Framework\TestCase;
use IU\REDCapETL\RedCapEtl;

/**
 * PHPUnit tests for the Rule class.
 */
class RuleTest extends TestCase
{
    public function testCreateRule()
    {

        // Test that a Rule can be created
        $expectedLine = 'Test Rule String';
        $expectedLineNumber = 37;

        $rule = new Rule($expectedLine, $expectedLineNumber);
        $this->assertNotNull($rule, 'rule not null');

        // Test that the rule returns the expected line and lineNumber
        $line = $rule->getLine();
        $this->assertEquals($expectedLine, $line, 'line check');

        $lineNumber = $rule->getLineNumber();
        $this->assertEquals($expectedLineNumber, $lineNumber, 'lineNumber check');


        // Test that the rule is empty of errors
        $hasErrors = $rule->hasErrors();
        $this->assertEquals(false, $hasErrors, 'No errors check');
        $errors = $rule->getErrors();
        $this->assertEquals(0, count($errors), 'No errors returned check');


        // Test that the rule accepts and returns errors
        $error1 = "First error";
        $error2 = "Second error";
        $expectedErrors = array($error1, $error2);
        $rule->addError($error1);
        $rule->addError($error2);

        $hasErrors = $rule->hasErrors();
        $this->assertEquals(true, $hasErrors, 'Errors check');
        $errors = $rule->getErrors();
        $this->assertEquals($expectedErrors, $errors, 'Errors returned check');
    }
}
