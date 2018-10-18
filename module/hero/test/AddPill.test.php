<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(liduo@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/
class Foster extends PHPUnit_Framework_TestCase
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
    
    public function testFosterInfo()
    {
    	$userObj = EnUser::getUserObj(self::$uid);
    	$userObj->addGold(10000, 0);
    	$userObj->addSilver(10000);
    	
    	$HeroMgr = $userObj->getHeroManager();
    	$hid = $HeroMgr->addNewHero(70020);
		
    	
    	$conf = btstore_get()->PILL;
    	$bag = BagManager::getInstance()->getBag();
    	foreach($conf as $index => $pillConf)
    	{
    		$itemTplId = $pillConf[PillDef::PILL_ID];
    		$bag->addItemByTemplateID($itemTplId, 20);
    	}
    	
    	$cls = new Hero();
    	$ret = $cls->addArrPills($hid, 1);
    	
    	var_dump($ret);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */