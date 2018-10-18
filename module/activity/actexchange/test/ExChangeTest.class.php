<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(hoping@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
class ExChangeTest extends PHPUnit_Framework_TestCase
{
    protected static $uid = 0;
    protected static $myExc = NULL;

    public static function createUser()
    {
        $pid = IdGenerator::nextId('uid');
        $uname = strval($pid);
        $ret = UserLogic::createUser($pid, 1, $uname);
        self::$uid = $ret['uid'];
    }

    public static function setUpBeforeClass()
    {
        self::createUser();
        RPCContext::getInstance()->setSession('global.uid', self::$uid);
        self::$myExc = new MyActExchange(self::$uid);
    }

    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    public function test_getMidNightTime()
    {
        $this->assertEquals(self::$myExc->getMidNightTime(), strtotime(date('y-m-d',Util::getTime())));
    }

    public function test_sysRfrGoodsList()
    {
        $this->assertFalse(self::$myExc->sysRfrGoodsList());
        //$this->assertEquals(self::$myExc->getGoodslist(), self::$myExc->getGoodslist());
    }

    public function test_resetRefreshNum()
    {
        self::$myExc->playerRfrGoodsListByGold(1);
        $times = self::$myExc->getPlayerRfrNum(1);
        echo $times;
        $this->assertEquals($times, 1);
        self::$myExc->resetRefreshNum();
        $times = self::$myExc->getPlayerRfrNum(1);
        echo $times;
        $this->assertEquals($times, 0);
    }

    public function test_isActExchangeOpen()
    {
         $this->assertTrue(self::$myExc->isActExchangeOpen());
    }

    public function test_refreshGoodsList()
    {
        $r1 = self::$myExc->getGoodslist();
        $goodinfo1 = self::$myExc->getInfoById(1);
        $goodinfo2 = self::$myExc->getInfoById(2);
        self::$myExc->refreshGoodsList(1, 1);
        $r2 = self::$myExc->getGoodslist();
        $newgoodinfo2 = self::$myExc->getInfoById(1);
        $newgoodinfo2 = self::$myExc->getInfoById(2);
        $this->assertNotEquals($r1, $r2);
        $this->assertNotEquals($goodinfo1, $newgoodinfo2);
        $this->assertEquals($goodinfo2, $newgoodinfo2);
    }

    public function test_refreshBoth()
    {
        $r1 = self::$myExc->getGoodslist();
        self::$myExc->refreshBoth();
        $r2 = self::$myExc->getGoodslist();
        $this->assertNotEquals($r1, $r2);
    }

    public function test_getInfoById()
    {
        $shopInfo = self::$myExc->getShopInfo();
        $goodlist = $shopInfo[ActExchangeDef::TBL_FIELD_VA_GOODSLIST];
        foreach($goodlist as $k => $v)
        {
            $goodinfo = self::$myExc->getInfoById($k);
            $this->assertArrayHasKey('req', $goodinfo[$k]);
            $this->assertArrayHasKey('acq', $goodinfo[$k]);
            $this->assertArrayHasKey('refresh_num', $goodinfo[$k]);
            $this->assertArrayHasKey('soldNum', $goodinfo[$k]);
        }
    }

    public function test_getShopInfo()
    {
        $shopInfo = self::$myExc->getShopInfo();
        $this->assertArrayHasKey(ActExchangeDef::TBL_FIELD_VA_GOODSLIST, $shopInfo);
        $this->assertArrayHasKey('sys_refresh_cd', $shopInfo);
    }

    public function test_getPlayerRfrNum()
    {
        $this->assertEquals(self::$myExc->getPlayerRfrNum(3), 0);
        self::$myExc->playerRfrGoodsListByGold(3);
        self::$myExc->playerRfrGoodsListByGold(3);
        self::$myExc->playerRfrGoodsListByGold(3);
        $this->assertEquals(self::$myExc->getPlayerRfrNum(3), 3);
    }

    public function test_canBuy()
    {
        $this->assertTrue(self::$myExc->canBuy(3));
        for($i = 0; $i<=40; $i++)
        {
            BagManager::getInstance()->getBag()->addItem(101101);
            BagManager::getInstance()->getBag()->addItem(410042);
            BagManager::getInstance()->getBag()->addItem(410112);
        }
        for($i=0; $i<10; $i++)
        {
            self::$myExc->exchange(3);
        }
        $this->assertFalse(self::$myExc->canBuy(3));
    }

    public function test_getActivityConf()
    {
        print_r(self::$myExc->getActivityConf());
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */