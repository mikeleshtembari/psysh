<?php

/*
 * This file is part of Psy Shell.
 *
 * (c) 2012-2017 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Psy\Test\CodeCleaner;

use PhpParser\NodeTraverser;
use Psy\CodeCleaner\LabelContextPass;

class LabelContextPassTest extends CodeCleanerTestCase
{
    public function setUp()
    {
        $this->pass      = new LabelContextPass();
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor($this->pass);
    }

    /**
     * @dataProvider validStatements
     */
    public function testProcessStatementPasses($code)
    {
        $stmts = $this->parse($code);
        $this->traverser->traverse($stmts);
    }

    public function validStatements()
    {
        return array(
            array('function foo() { foo: "echo"; goto foo; }'),
            array('function foo() { "echo"; goto foo; }'),
            array('begin: foreach (range(1, 5) as $i) { goto end; } end: goto begin;'),
            array('bar: if (true) goto bar;'),

            // False negative
            // PHP Fatal error: 'goto' into loop or switch statement is disallowed
            'false negative1' => array('while (true) { label: "error"; } goto label;'),
            // PHP Fatal error:  'goto' to undefined label 'none'
            'false negative2' => array('$f = function () { goto none; };'),
        );
    }

    /**
     * @dataProvider invalidStatements
     * @expectedException \Psy\Exception\FatalErrorException
     */
    public function testInvalid($code)
    {
        $stmts = $this->parse($code);
        $this->traverser->traverse($stmts);
    }

    public function invalidStatements()
    {
        return array(
            array('goto bar;'),
            array('if (true) goto bar;'),
            array('buz: if (true) goto bar;'),
        );
    }

    /**
     * @dataProvider unreachableLabelStatements
     * @expectedException \Psy\Exception\ErrorException
     */
    public function testUnreachedLabel($code)
    {
        $stmts = $this->parse($code);
        $this->traverser->traverse($stmts);
    }

    public function unreachableLabelStatements()
    {
        return array(
            array('buz:'),
            array('foo: buz: goto foo;'),
            array('foo: buz: goto buz;'),
        );
    }
}