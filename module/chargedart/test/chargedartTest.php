<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: chargedartTest.php 238976 2016-04-19 03:01:24Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chargedart/test/chargedartTest.php $
 * @author $Author: ShuoLiu $(hoping@babeltime.com)
 * @date $Date: 2016-04-19 03:01:24 +0000 (Tue, 19 Apr 2016) $
 * @version $Revision: 238976 $
 * @brief 
 *  
 **/

class chargedartTest extends PHPUnit_Framework_TestCase
{
    private $uid = 0;
    protected function setUp()
    {      
        $this->uid = 20197;
        parent::setUp ();
    }
    
    /**
     * @group test_enter
     */
    public function test_enter()
    {
        var_dump(ChargeDartLogic::enterChargeDart($this->uid));
    }
    
    /**
     * @group test_ship
     */
    public function test_ship()
    {
        var_dump(ChargeDartLogic::beginShipping(20897));
        var_dump(ChargeDartLogic::beginShipping(20197));
        var_dump(ChargeDartLogic::beginShipping(20513));
        var_dump(ChargeDartLogic::beginShipping(62175));
        var_dump(ChargeDartLogic::beginShipping(108493));
        var_dump(ChargeDartLogic::beginShipping(63896));
    }
    
    /**
     * @group test_getreward
     */
    public function test_getreward()
    {
        var_dump(ChargeDartLogic::getReward(1,40,0,1));
    }
    
    /**
     * @group test_getone
     */
    public function test_getone()
    {
        var_dump(ChargeDartLogic::getOnePageInfo(1,2));
    }
    
    /**
     * @group test_getinfo
     */
    public function test_getinfo()
    {
        var_dump(ChargeDartLogic::getChargeDartInfo($this->uid, 108493));
    }
    
    /**
     * @group test_look
     */
    public function test_look()
    {
        var_dump(ChargeDartLogic::ChargeDartLook($this->uid, 108493));
    }
    
    /**
     * @group test_rob 
     */
    public function test_rob()
    {
        var_dump(ChargeDartLogic::rob($this->uid,1, 108493));
    }
    
    
    /**
     * @group test_shippage
     */
    public function test_shippage()
    {
        var_dump(ChargeDartLogic::enterShipPage($this->uid));
    }
    
    /**
     * @group test_refresh
     */
    public function test_refresh()
    {
        var_dump(ChargeDartLogic::refreshStage($this->uid));
    }
    

    /**
     * @group test_beship
     */
    public function test_beship()
    {
        var_dump(ChargeDartLogic::beginShipping($this->uid));
    }
    
    /**
     * @group test_oprage
     */
    public function test_oprage()
    {
        var_dump(ChargeDartLogic::openRage($this->uid,0));
    }
    
    /**
     * @group test_finish
     */
    public function test_finish()
    {
        var_dump(ChargeDartLogic::finishByGold($this->uid));
    }
    
    /**
     * @group test_buy
     */
    public function test_buy()
    {
        var_dump(ChargeDartLogic::buyRobNum($this->uid));
        var_dump(ChargeDartLogic::buyAssistanceNum($this->uid));
        var_dump(ChargeDartLogic::buyShipNum($this->uid));
    }
    
    /**
     * @group test_getbrid
     */
    public function test_getbrid()
    {
        var_dump(ChargeDartLogic::getAllMyInfo($this->uid));
    }
    
    /**
     * @group test_invite
     */
    public function test_invite()
    {
        var_dump(ChargeDartLogic::inviteFriend($this->uid,107726));
    }
    
    /**
     * @group test_acc
     */
    public function test_acc()
    {
        $flag = 0;
        var_dump(ChargeDartLogic::inviteFriend(107726,$this->uid,$flag));
    }
}



/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */