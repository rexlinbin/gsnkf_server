<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MineralTest.php 243166 2016-05-17 08:42:30Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mineral/test/MineralTest.php $
 * @author $Author: QingYao $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-05-17 08:42:30 +0000 (Tue, 17 May 2016) $
 * @version $Revision: 243166 $
 * @brief 
 *  
 **/
class MineralTest extends PHPUnit_Framework_TestCase
{
	
    private static $arrUid=array();//厉害一点 抢资源矿时能赢
    
    private static $time;
    private static $guildid;
    private static $addItems = array(
            //'WEAPON'=>
            101101=>1,
            //'RING'=>
            102423=>1,
            //'ARMOR'=>
            103423=>1,
            //'HAT'=>
            104423=>1,
            //'NECKLACE'=>
            );
    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
       $ret=self::createUser();
       self::$arrUid[1]=$ret['uid'];
       //第一个建工会
       self::changeToUser(self::$arrUid[1]);
       self::openMineralSwitch(self::$arrUid[1]);
       self::createGuild(self::$arrUid[1]);
       //然后是第二个到第五个
       for ($i=2;$i<=5;$i++)
       {
       		$ret=self::createUser();
       		self::$arrUid[$i]=$ret['uid'];
       		self::changeToUser(self::$arrUid[$i]);
       		self::openMineralSwitch(self::$arrUid[$i]);
       		self::applyGuild(self::$guildid);
       }
       
        self::changeToUser(self::$arrUid[1]);
        for ($i=2;$i<=5;$i++)
        {
        	self::agreeApply(self::$arrUid[$i]);
        }
        
        $bag = BagManager::getInstance()->getBag(self::$arrUid[1]);
        $bag->addItemsByTemplateID(self::$addItems);
        $hero = new Hero();
        $hid = EnUser::getUserObj()->getMasterHid();
        $hero->equipBestArming($hid);
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    protected function setUp()
    {
        //RPCContext::getInstance()->setSession('global.uid', $this->uid);
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
    
    public static function openMineralSwitch($uid)
    {
        self::changeToUser($uid);
        $console = new Console();
        $switchId = SwitchDef::MINERAL;
        $openConf = btstore_get()->SWITCH[$switchId];
        $needLv    =    $openConf['openLv'];
        $needBase  =    $openConf['openNeedBase'];
        if(!empty($needLv))
        {
            $console->level($needLv);
        }
        if(!empty($needBase))
        {
            $baseId    =    intval($needBase);
            $copyId = btstore_get()->BASE[$baseId]['copyid'];
            $copyId = intval($copyId);
            $console->passNCopies($copyId,BaseLevel::SIMPLE);
        }
    }
    
    public function explorePit($uid)
    {
        self::changeToUser($uid);
        $mineral = new Mineral();
        $ret = $mineral->explorePit();
        if(empty($ret))
        {
            return 0;
        }        
        foreach($ret as $index => $pitInfo)
        {
            if($pitInfo[TblMineralField::UID] == 0)
            {
                return $pitInfo;
            }
        }
        throw new FakeException('no pit is free.');
    }
    
    public function testCapturePit()
    {
    	//让五个人占同一页矿，然后到时之后看收益
    	for ($i=1;$i<=5;$i++)
    	{
    		echo "testCapturePit start.\n";
    		self::changeToUser(self::$arrUid[$i]);
    		$userObj = EnUser::getUserObj();
    		$preExe = $userObj->getCurExecution();
    		echo "uid".self::$arrUid[$i]."guildId:".$userObj->getGuildId()."\n";
    		
    		$pitInfo = $this->explorePit(self::$arrUid[$i]);
    		$mineral = new Mineral();
    		$domainId = $pitInfo[TblMineralField::DOMAINID];
    		$pitId = $pitInfo[TblMineralField::PITID];
    		$preSelfPits = $mineral->getSelfPitsInfo();
    		Logger::trace('testCapturePit capturePit start.');
    		$ret = $mineral->capturePit($domainId, $pitId);
    		Logger::trace('testCapturePit capturePit end.');
    	}
    	
    }
    
    public function testGrabPit()
    {
        echo "testGrabPit start.\n";
        self::changeToUser(self::$arrUid[2]);
        $pitInfo = $this->explorePit(self::$arrUid[2]);
        $mineral = new Mineral();
        $domainId = $pitInfo[TblMineralField::DOMAINID];
        $pitId = $pitInfo[TblMineralField::PITID];
        $ret = $mineral->capturePit($domainId, $pitId);
        if($ret['err'] != MineralDef::CAPTURE_PIT_OK)
        {
            throw new FakeException('capture pit failed.');
        }
        $result = $ret['appraisal'];
        //输了
        if(BattleDef::$APPRAISAL[$result] > BattleDef::$APPRAISAL['D'])
        {
            throw new FakeException('testGrabPit .capture failed.');
        }
        $selfPits = $mineral->getSelfPitsInfo();
        $this->assertTrue((count($selfPits)==2),'other user after capturepit,should get one pit.');
        
        //thisuser 抢矿
        //Otheruser占矿成功了
        //ThisUser开始抢矿
        $needGold = FALSE;
        if(self::isDuringCaptureTime() == FALSE)
        {
            $needGold = TRUE;
        }
        self::changeToUser(self::$arrUid[1]);
        $mineral = new Mineral();
        $userObj = EnUser::getUserObj();
        $preGold = $userObj->getGold();
        $preExec = $userObj->getCurExecution();
        $preSelfPit = $mineral->getSelfPitsInfo();
        if($needGold == FALSE)
        {
            $gold = 0;
            $ret = $mineral->grabPit($domainId, $pitId);
        }
        else 
        {
            $gold = MineralConf::$GRAB_PIT_BY_GOLD_NUM;
            $ret = $mineral->grabPitByGold($domainId, $pitId);            
        }
        $result = $ret['appraisal'];
        //输了
        if(BattleDef::$APPRAISAL[$result] > BattleDef::$APPRAISAL['D'])
        {
            throw new FakeException('testGrabPit .grabpit failed.');
        }
        self::changeToUser(self::$arrUid[1]);
        $userObj = Enuser::getUserObj();
        $afterGold = $userObj->getGold();
        $afterExec = $userObj->getCurExecution();
        $afterSelfPit = $mineral->getSelfPitsInfo();
        $this->assertTrue(($preGold-$gold==$afterGold),
                "grappit sub gold failed.pre $preGold need $gold after $afterGold");
        $exec = MineralConf::$CAPTURE_PIT_NEED_EXECUTION;
        $this->assertTrue(($preExec-$exec == $afterExec),
                "grabpit sub execution failed pre $preExec need $exec after $afterExec");
        $this->assertTrue((count($preSelfPit)+1 == count($afterSelfPit)),
                'after grabpit,should got one pit');
        
        
        
        //otheruser抢矿
        self::changeToUser(self::$arrUid[2]);
        $mineral = new Mineral();
        $userObj = EnUser::getUserObj();
        $preGold = $userObj->getGold();
        $preExec = $userObj->getCurExecution();
        $preSelfPit = $mineral->getSelfPitsInfo();
        if($needGold == FALSE)
        {
            $gold = 0;
            $ret = $mineral->grabPit($domainId, $pitId);
        }
        else
        {
            $gold = MineralConf::$GRAB_PIT_BY_GOLD_NUM;
            $ret = $mineral->grabPitByGold($domainId, $pitId);
        }
        $result = $ret['appraisal'];
        //输了
        $fail = false;
        if(BattleDef::$APPRAISAL[$result] > BattleDef::$APPRAISAL['D'])
        {
            $fail = true;
        }
        self::changeToUser(self::$arrUid[2]);
        $mineral = new Mineral();
        $userObj = Enuser::getUserObj();
        $afterGold = $userObj->getGold();
        $afterExec = $userObj->getCurExecution();
        $afterSelfPit = $mineral->getSelfPitsInfo();
        $this->assertTrue(($preGold-$gold==$afterGold),
                "other user grappit sub gold failed.pre $preGold need $gold after $afterGold");
        $this->assertTrue(($preExec-$exec == $afterExec),
                "other user grabpit sub execution failed pre $preExec need $exec after $afterExec");
        if($fail)
        {
            $this->assertTrue((count($preSelfPit) == count($afterSelfPit)),
                    'other user after grabpit,should got one pit');
        }
        else
        {
            $this->assertTrue((count($preSelfPit)+1 == count($afterSelfPit)),
                    'other user after grabpit,should got one pit');
        }
        if($fail)
        {
            self::changeToUser(self::$arrUid[1]);
            $mineral = new Mineral();
        }
        $mineral->giveUpPit($domainId, $pitId);
    }
    
    public function testGiveUpPit()
    {
        echo "testGiveUpPit start.\n";
         $this->changeToUser(self::$arrUid[1]);
         $mineral = new Mineral();
         $pitInfo = $this->getFreePit(MineralType::NORMAL);
         if(empty($pitInfo))
         {
             return 'no free pit'."\n";
         }
         $domainId = $pitInfo[TblMineralField::DOMAINID];
         $pitId = $pitInfo[TblMineralField::PITID];
         $ret = $mineral->capturePit($domainId, $pitId);
         $result = $ret['appraisal'];
        //输了
        $fail = false;
        if(BattleDef::$APPRAISAL[$result] > BattleDef::$APPRAISAL['D'])
        {
            throw new FakeException('fail');
        }
        $prePits = $mineral->getSelfPitsInfo();
         $mineral->giveUpPit($domainId, $pitId);
         $this->changeToUser(self::$arrUid[1]);
         $afterPits = $mineral->getSelfPitsInfo();
         $this->assertTrue((count($prePits) - 1 == count($afterPits)),'giveup pit should have one less pit');
    }
    
    public function testExplorePit()
    {
        echo "testExplorePit start.\n";
        $domainType = MineralType::NORMAL;
        $domainId = $this->getAnyDomainIdByType($domainType);
        $this->changeToUser(self::$arrUid[1]);
        $mineral = new Mineral();
        $mineral->getPitsByDomain($domainId);
        $ret = $mineral->explorePit();
        if($this->hasFreePitByType($domainType) == FALSE)
        {
            $this->assertTrue((empty($ret) == TRUE),'should have no free pit.');
        }
        else
        {
            $this->assertTrue((empty($ret)!=TRUE),'should have free pit');
            $pitInfo = $ret[0];
            $this->assertTrue(($pitInfo['domain_type'] == $domainType),'domaintype is wrong');
        }
    }
    
    public function testThreeCapture()
    {
    	
    }
    
    
    
    private function getAnyDomainIdByType($type)
    {
        $arrDomain = btstore_get()->MINERAL->toArray();
        while(true)
        {
            $domainId = array_rand($arrDomain);
            if($arrDomain[$domainId]['domain_type'] == $type)
            {
                return $domainId;
            }
        }
    }
    
    private function hasFreePitByType($type)
    {
        $ret = MineralDAO::explorePit($type, 1);
        if(empty($ret))
        {
            return FALSE;
        }
        return TRUE;
    }
    
    private function getFreePit($type=MineralType::SENIOR)
    {
        $ret = MineralDao::explorePit($type, 1);
        return $ret;
    }
    
    
    public static function isDuringCaptureTime()
    {
        $now    =    Util::getTime();
        $date    =    date("Y-m-d",$now);
        $openTime    =    strtotime($date." ".MineralConf::$CAPTURE_PIT_START_TIME.":00:00");
        $endTime     =    strtotime($date." ".MineralConf::$CAPTURE_PIT_END_TIME.":00:00");
        if($now > $openTime &&  ($now  < $endTime))
        {
            return TRUE;
        }
        return FALSE;
    }
    
    public static function openGuildSwitch($uid)
    {
    	EnSwitch::getSwitchObj($uid)->addNewSwitch(SwitchDef::GUILD);
    	EnSwitch::getSwitchObj($uid)->save();
    }
    public static function createGuild($uid)
    {
    	EnUser::release();
    	GuildObj::release(0);
    	GuildMemberObj::release(0);
    	$conf = btstore_get()->GUILD;
    	$level = $conf[GuildDef::GUILD_USER_LEVEL];
    	$subGold = $conf[GuildDef::GUILD_GOLD_CREATE];
    	$subSilver = $conf[GuildDef::GUILD_SILVER_CREATE];
    	$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
    	
    	$user = EnUser::getUserObj($uid);
    	$user->addExp($expTable[$level]);
    	$user->addSilver($subSilver);
    	$user->update();
    	$silverBefore = $user->getSilver();
    	$guild = new Guild();
    	$name = 'sg'.rand(1000,2000);
    	$ret = $guild->createGuild($name);
    	$ret = GuildDao::selectMember($uid);
    	self::$guildid= $ret[GuildDef::GUILD_ID];
    	echo "createguild successguildid:".self::$guildid."\n";
    }
    public static function applyGuild($guildId)
    {
    	EnUser::release();
    	GuildObj::release(0);
    	GuildMemberObj::release(0);
    	$guild = new Guild();
    	echo "applyguild\n";
    	$ret = $guild->applyGuild($guildId);
    }
    public static function agreeApply($uid)
    {
    	EnUser::release();
    	GuildObj::release(0);
    	GuildMemberObj::release(0);
    	$guild = new Guild();
    	echo "agreeguild\n";
    	$ret = $guild->agreeApply($uid);
    }
    
    public function testQuitGuild()
    {
    	EnUser::release();
    	GuildObj::release(0);
    	GuildMemberObj::release(0);
    	
    	self::changeToUser(self::$arrUid[3]);
    	$guild=new Guild();
    	echo "test quit guild\n";
    	$guild->quitGuild();
    	
    }
    private static function changeToUser($uid)
    {
    	RPCContext::getInstance()->resetSession();
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
        RPCContext::getInstance()->unsetSession(CopySessionName::COPYLIST);
        RPCContext::getInstance()->unsetSession(CopySessionName::ECOPYLIST);
        RPCContext::getInstance()->unsetSession(CopySessionName::COPYID);
        EnUser::release();
        CData::$QUERY_CACHE = NULL;
    }
    private static function createUser()
    {
    	self::$time = time();
    	//创建用户
    	$pid = self::$time;
    	$str = strval($pid);
    	$uname = substr($str, strlen($str) - UserConf::MAX_USER_NAME_LEN);
    	$ret = UserLogic::createUser($pid, 1, $uname);
    	if($ret['ret'] != 'ok')
    	{
    		echo "create user failed\n";
    		exit();
    	}
    	Logger::trace('create user ret %s.',$ret);
    	return $ret;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */