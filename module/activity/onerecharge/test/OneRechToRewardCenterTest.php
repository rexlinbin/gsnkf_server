<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 *
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(jinyang@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief
 *
 **/
class OneRechToRewardCenterTest extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {

    }

    protected function tearDown()
    {

    }

    public function test_rewardToCenter()
    {
        $oneRech = new OneRecharge();
        $oneRech->doReward();
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */