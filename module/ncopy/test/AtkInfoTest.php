<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AtkInfoTest.php 74223 2013-11-12 07:28:42Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ncopy/test/AtkInfoTest.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-11-12 07:28:42 +0000 (Tue, 12 Nov 2013) $
 * @version $Revision: 74223 $
 * @brief 
 *  
 **/
class AtkInfoTest extends PHPUnit_Framework_TestCase
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
        RPCContext::getInstance ()->setSession ( UserDef::SESSION_KEY_UID, self::$uid );
    }
    
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    protected function setUp()
    {
    }
    
    public function testAtkInfoClass()
    {
        $copyId = 1;
        $baseId = 1001;
        $baseLv = BaseLevel::NPC;
        AtkInfo::getInstance()->initAtkInfo($copyId, $baseId, $baseLv);
        
        $instance = AtkInfo::getInstance();
        $instance->setAtkInfoStatus(ATK_INFO_STATUS::PASS);
        $atkInfo1 = $instance->getAtkInfo();
        $this->assertTrue((get_class($instance) == 'AtkInfo'),'AtkInfo::getInstance() should get AtkInfo instance.');
        
        $instance1 = NCopyAtkInfo::getInstance();
        $instance1->setAtkInfoStatus(ATK_INFO_STATUS::FAIL);
        $atkInfo2 = $instance1->getAtkInfo();
        $this->assertTrue((get_class($instance1) == 'NCopyAtkInfo'),'NCopyAtkInfo::getInstance() should get NCopyAtkInfo instance.');
        
        $instance2 = AtkInfo::getInstance();
        $this->assertTrue(($instance2->getAtkInfoStatus() == ATK_INFO_STATUS::FAIL),'instance2 atkinfo status should be fail.');
        $this->assertTrue(($instance2 == $instance1),'instance2 is not equal to instance1');
        $this->assertTrue((get_class($instance2) == 'NCopyAtkInfo'),'after NCopyAtkInfo::getInstance(),AtkInfo::getInstance should get NCopyAtkInfo instance.');
        $atkInfo3 = $instance2->getAtkInfo();
        $this->assertTrue(($atkInfo3 == $atkInfo2),'after instance change to NCopyAtkInfo.atkinfo3 should not change.');
        $instance2->saveAtkInfo();
        AtkInfo::release();
        
        $atkInfo4 = NCopyAtkInfo::getInstance()->getAtkInfo();
        $this->assertTrue(($atkInfo4 == $atkInfo2),'release atkinfo.atkinfo4 should not change.');
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */