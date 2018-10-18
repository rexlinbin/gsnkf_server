<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: SwitchTest.php 72896 2013-11-05 10:43:50Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/switch/test/SwitchTest.php $
 * @author $Author: TiantianZhang $(lanhongyu@babeltime.com)
 * @date $Date: 2013-11-05 10:43:50 +0000 (Tue, 05 Nov 2013) $
 * @version $Revision: 72896 $
 * @brief 
 *  
 **/
class SwitchTest extends PHPUnit_Framework_TestCase
{
    private static $uid;
    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        $pid = time();
        $str = strval($pid);
        $uname = substr($str, strlen($str) - UserConf::MAX_USER_NAME_LEN);
    
        $ret = UserLogic::createUser($pid, 1, $uname);
    
        if($ret['ret'] != 'ok')
        {
            echo "create use failed\n";
            exit();
        }
        Logger::trace('create user ret %s.',$ret);
        self::$uid = $ret['uid'];
    
    }
    
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    protected function setUp()
    {
        RPCContext::getInstance ()->setSession ( UserDef::SESSION_KEY_UID, self::$uid );
    
    }
    
    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    
    }
    
    /**
     * This method is called after the last test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function tearDownAfterClass()
    {
    
    }
    
    public function testSwitch()
    {
        EnSwitch::checkSwitch();
        //新创建的用户   开启的switch
        $arrOpenSwitch = array();
        $arrConf = btstore_get()->SWITCH;
        foreach($arrConf as $switchId => $switchConf)
        {
            if(empty($switchConf['openNeedBase']) && ($switchConf['openLv'] == 1))
            {
                $this->assertTrue(EnSwitch::isSwitchOpen($switchId),'new create user.switch '.$switchId.' should be open.');
            }
        }
        //对玩家进行升级  升级到10级
        $level = 10;
        $expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
        $exp = $expTable[$level];
        $userObj = Enuser::getUserObj();
        $userObj->addExp($exp - $userObj->getAllExp());
        $userObj->update();
        EnSwitch::checkSwitch();
        foreach($arrConf as $switchId => $switchConf)
        {
            if(empty($switchConf['openNeedBase']) && ($switchConf['openLv'] <= 10))
            {
                $this->assertTrue(EnSwitch::isSwitchOpen($switchId),'user has level 10.switch '.$switchId.' should be open.');
            }
        }
        $arrNeedBase = btstore_get()->SWITCHBASE->toArray();
        sort($arrNeedBase);
        $baseId = current($arrNeedBase);
        $copyId = btstore_get()->BASE[$baseId]['copyid'];
        $preBase = btstore_get()->COPY[$copyId]['base_open']; 
        if(!empty($preBase))
        {
            $preCopyId = btstore_get()->BASE[$preBase]['copyid'];
            $console = new Console();
            $console->passNCopies($preCopyId);
        }       
        $copyObj = MyNCopy::getInstance()->getCopyObj($copyId);
        $copyObj->updBaseStatus($baseId, BaseStatus::SIMPLEPASS);
        MyNCopy::getInstance()->setCopyInfo($copyId, $copyObj->getCopyInfo());
        MyNCopy::getInstance()->save();
        EnSwitch::checkSwitch();
        foreach($arrConf as $switchId => $switchConf)
        {
            if(!empty($switchConf['openNeedBase']) && ($switchConf['openLv'] <= 10)
                    && ($switchConf['openNeedBase'] == $baseId))
            {
                $this->assertTrue(EnSwitch::isSwitchOpen($switchId),'user has level 10,open base '.$baseId.' switch '.$switchId.' should be open.');
            }
        }
    }
}	
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */