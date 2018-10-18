<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: DressRoomTest.php 139439 2014-11-11 03:29:52Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/dressroom/test/DressRoomTest.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-11-11 03:29:52 +0000 (Tue, 11 Nov 2014) $$
 * @version $$Revision: 139439 $$
 * @brief 
 *  
 **/
class DressRoomTest extends PHPUnit_Framework_TestCase
{
    protected static $pid = 0;
    protected static $uid = 0;
    protected static $uname = '';

    public static function setUpBeforeClass()
    {
        self::$pid = IdGenerator::nextId('uid');
        self::$uname = strval(self::$pid);
        $ret = UserLogic::createUser(self::$pid, 1, self::$uname);

        if($ret['ret'] != 'ok')
        {
            echo "create user failed\n";
            exit();
        }
        self::$uid = $ret['uid'];
    }

    protected function setUp()
    {
        RPCContext::getInstance ()->setSession ( 'global.uid', self::$uid );
    }

    protected function tearDown()
    {
    }

    public function testGetNewDress()
    {
        $arrAviableDress = DressRoomUtil::getAviableDressFromConf();
        $newDress = $arrAviableDress[3];

        $arrDress = DressRoomManager::getInstance()->getArrDress();
        $this->assertNotContains($newDress, array_keys($arrDress));

        EnDressRoom::getNewDress($newDress);

        $arrDress = DressRoomManager::getInstance()->getArrDress();
        $this->assertContains($newDress, array_keys($arrDress));
    }

    /*public function testGetAddAttrByDress()
    {
        $ret = EnDressRoom::getAddAttrByDress(self::$uid);
        var_dump($ret);
    }*/

    public function testGetArrActiveYesDress()
    {
        $arrActiveYesDress = DressRoomManager::getInstance()->getArrActiveYesDress();
        $arrAviableDress = DressRoomUtil::getAviableDressFromConf();
        $arrDress = DressRoomManager::getInstance()->getArrDress();

        foreach($arrActiveYesDress as $dress)
        {
            $this->assertTrue(in_array($dress, $arrAviableDress));
            $this->assertEquals($arrDress[$dress]['as'], DressRoomDef::ACTIVESTATUSYES);
        }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */