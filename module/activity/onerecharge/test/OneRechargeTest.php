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
class OneRechargeTest extends PHPUnit_Framework_TestCase
{
    private static $uid = 20024;
    private static $pid = 20023;
    private static $tname = 'action3015';
/*
    public static function setUpBeforeClass()
    {
        self::$pid = IdGenerator::nextId('uid');
        $utid = 1;
        $uname = 'action'.rand(0, 9999);
        $ret = UserLogic::createUser(self::$pid, $utid, $uname);

        if ($ret['ret'] != 'ok')
        {
            echo "create user failed \n";
            exit();
        }

        self::$uid = $ret['uid'];
    }
*/
    protected function setUp()
    {
        RPCContext::getInstance()->setSession('global.uid', self::$uid);
    }

    protected function tearDown()
    {
        RPCContext::getInstance()->resetSession();
        RPCContext::getInstance()->unsetSession('global.uid');
    }

    public function test_getInfo()
    {
        $oneRecharge = new OneRecharge();
        $ret = $oneRecharge->getInfo();
        var_dump($ret);
    }
    /*
    public function test_gainReward_gainAll_fail()
    {
        $oneRecharge = new OneRecharge();
        //测试：本应全部领取，但select为负数的情况，断言领取失败
        $ret0 = $oneRecharge->gainReward(1, -1);
        echo '-----------------------gainAll_fail';
        $this->getInfo();
        $this->assertEquals('ok', $ret0);
    }
    public function test_gainReward_gainAll_fail2()
    {
        $oneRecharge = new OneRecharge();
        //测试：本应全部领取，但select为整数的情况，断言领取失败
        $ret0 = $oneRecharge->gainReward(1, 2);
        echo '-----------------------gainAll_fail2';
        $this->getInfo();
        $this->assertEquals('ok', $ret0);
    }
    public function test_gainReward_gain_1_N_fail()
    {
        $oneRecharge = new OneRecharge();
        //测试：领取N选1，但select为0，断言领取失败
        $ret0 = $oneRecharge->gainReward(3, 0);
        echo '-----------------------gain_1_N_fail';
        $this->getInfo();
        $this->assertEquals('ok', $ret0);
    }
    public function test_gainReward_gain_1_N_fail2()
    {
        $oneRecharge = new OneRecharge();
        //测试：领取N选1，但select为-2，断言领取失败
        $ret0 = $oneRecharge->gainReward(3, -2);
        echo '-----------------------gain_1_N_fail2';
        $this->getInfo();
        $this->assertEquals('ok', $ret0);
    }
    public function test_gainReward_gain_1_N_fail3()
    {
        $oneRecharge = new OneRecharge();
        //测试：领取N选1，但select超出范围，断言领取失败
        $ret0 = $oneRecharge->gainReward(3, 3);
        echo '-----------------------gain_1_N_fail3';
        $this->getInfo();
        $this->assertEquals('ok', $ret0);
    }
*/
    public function test_gainReward_gainAll_ok()
    {
        $oneRecharge = new OneRecharge();
        //已经充值500，测试领取全部，断言成功
        $ret0 = $oneRecharge->gainReward(4, 2);
        $this->test_getInfo();
        $this->assertEquals('ok', $ret0);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */