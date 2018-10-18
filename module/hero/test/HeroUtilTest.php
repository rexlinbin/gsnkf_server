<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroUtilTest.php 66998 2013-09-29 04:49:11Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/test/HeroUtilTest.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-09-29 04:49:11 +0000 (Sun, 29 Sep 2013) $
 * @version $Revision: 66998 $
 * @brief 
 *  
 **/
class HeroUtilTest extends PHPUnit_Framework_TestCase
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
    
    public function testGetHeroesWithFiveStar()
    {
        $arrHid = array();
        //一个五星武将
        $tmp = $this->addFiveStar();
        EnUser::getUserObj()->update();
        $arrHid = $arrHid + $tmp;
        $arrHidGet = HeroUtil::getHeroesWithFiveStar(self::$uid,3);
        Logger::trace('first assert.arrHid %s.arrHidGet %s.',$arrHid,$arrHidGet);
        $this->assertTrue(($arrHid == $arrHidGet),"1 five star.please check log.");
        echo "1 test \n";
        //三个五星武将
        $tmp = $this->addFiveStar();
        $arrHid = $arrHid + $tmp;
        $tmp = $this->addFiveStar();
        $arrHid = $arrHid + $tmp;
        EnUser::getUserObj()->update();
        $arrHidGet = HeroUtil::getHeroesWithFiveStar(self::$uid,3);
        Logger::trace('second assert.arrHid %s.arrHidGet %s.',$arrHid,$arrHidGet);
        $this->assertTrue(($arrHid == $arrHidGet),"3 five star.please check log.");
        echo "2 test \n";
        //100个五星武将
        for($i=0;$i<97;$i++)
        {
            $tmp = $this->addFiveStar();
            $arrHid = $arrHid + $tmp;
        }
        EnUser::getUserObj()->update();
        $arrHidGet = HeroUtil::getHeroesWithFiveStar(self::$uid);
        $arrHidPart = HeroUtil::getHeroesWithFiveStar(self::$uid,3);
        Logger::trace('100 five star.get 3 hero. %s',$arrHidPart);
        $this->assertTrue((count($arrHidPart) >= 3),'100 hero get 3 hero,not equal to 3');
        Logger::trace('third assert.arrHid %s.arrHidGet %s.',$arrHid,$arrHidGet);
        $this->assertTrue(($arrHid == $arrHidGet),"100 five star.please check log.");
        echo "3 test \n";
        //120个五星武将
        for($i=0;$i<20;$i++)
        {
            $tmp = $this->addFiveStar();
            $arrHid = $arrHid + $tmp;
        }
        EnUser::getUserObj()->update();
        $arrHidGet = HeroUtil::getHeroesWithFiveStar(self::$uid);
        $arrHidPart = HeroUtil::getHeroesWithFiveStar(self::$uid,3);
        Logger::trace('120 five star.get 3 hero. %s',$arrHidPart);
        $this->assertTrue((count($arrHidPart) >= 3),'120 hero get 3 hero,not equal to 3');
        Logger::trace('forth assert.arrHid %s.arrHidGet %s.',$arrHid,$arrHidGet);
        $this->assertTrue(($arrHid == $arrHidGet),"120 five star.please check log.");
        echo "4 test \n";
    }
    
    private function addFiveStar()
    {
        $fiveHeroes = btstore_get()->FIVESTARHERO->toArray();
        while(TRUE)
        {
            $index = array_rand($fiveHeroes);
            $htid = $fiveHeroes[$index];
            if(Creature::getHeroConf($htid, CreatureAttr::CAN_BE_RESOLVED) == 1)
            {
                break;
            }
        }
        $heroMng = EnUser::getUserObj()->getHeroManager();
        $hid = $heroMng->addNewHero($htid);
        return array($hid=>$htid);
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */