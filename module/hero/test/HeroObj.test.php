<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroObj.test.php 78602 2013-12-04 06:46:21Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/test/HeroObj.test.php $
 * @author $Author: TiantianZhang $(lanhongyu@babeltime.com)
 * @date $Date: 2013-12-04 06:46:21 +0000 (Wed, 04 Dec 2013) $
 * @version $Revision: 78602 $
 * @brief 
 *  
 **/

//require_once (MOD_ROOT . '/user/User.class.php');
require_once (ROOT.'/def/Creature.def.php');

class HeroObjTest extends PHPUnit_Framework_TestCase
{

	private static $uid;
	private static $pid;
	
	
	public static function setUpBeforeClass()
	{
	    self::$pid = IdGenerator::nextId('uid');
      	$utid = 1;
		$uname = 't' . self::$pid;
		$ret = UserLogic::createUser(self::$pid, $utid, $uname);
		$users = UserLogic::getUsers( self::$pid );
		self::$uid = $users[0]['uid'];
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		$switchObj = EnSwitch::getSwitchObj();
		$switchObj->addNewSwitch(SwitchDef::HEROFORGE);
		$switchObj->addNewSwitch(SwitchDef::HEROENFORCE);
		$switchObj->addNewSwitch(SwitchDef::HEROTRANSFER);
		$switchObj->save();
	}
	
	
	protected function setUp() 
	{
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
	}

		
	public function test_enforce_bysoul()
	{
	    Logger::debug('======%s======', __METHOD__);
	    $htid    =    $this->getNormalHtid();
	    $userObj = EnUser::getUserObj(self::$uid);
	    $heroMng = $userObj->getHeroManager();
	    $hid = $heroMng->addNewHero($htid);
	    $heroObj = $heroMng->getHeroObj($hid);
	    //强化一次
	    $enforceNum = 1;
	    $this->enforceBySoul($hid, $enforceNum);
        //强化多次  强化到最大等级
	    $maxLevel = $heroObj->getMaxEnforceLevel();
	    $enforceNum = $maxLevel - $heroObj->getLevel();
	    $userObj->addSoul(200000000);
	    $userObj->addSilver(200000000);
	    $this->enforceBySoul($hid, $enforceNum);
	    //强化一次
	    $enforceNum = 1;
	    $this->enforceBySoul($hid, $enforceNum);
	    //强化主角
	    $masterHid = $userObj->getMasterHid();
	    $this->enforceBySoul($masterHid, 1);
	}
	
	private function enforceBySoul($hid,$enforceNum)
	{
	    $userObj = EnUser::getUserObj(self::$uid);
	    $heroMng = $userObj->getHeroManager();
	    $heroObj = $heroMng->getHeroObj($hid);
	    $hero = new Hero();
	    $userSoul = $userObj->getSoul();
	    $userSilver = $userObj->getSilver();
	    $oldLv = $heroObj->getLevel();
	    $oldSoul = $heroObj->getSoul();
	    try 
	    {
	        $ret = $hero->enforce($hid, $enforceNum);
	    }
	    catch(Exception $e)
	    {
	        Logger::warning('enforce failed ,error message %s.',$e->getMessage());
	        return;
	    }
	    $expTblId = $heroObj->getConf(CreatureAttr::EXP_ID);
	    Logger::trace('hero %s.htid %s.level %s.exptbl %s.',$heroObj->getHid(),$heroObj->getHtid(),
	            $heroObj->getLevel(),$expTblId);
	    $expTbl		= btstore_get()->EXP_TBL[$expTblId];
	    $soul = $expTbl[$heroObj->getLevel()];
	    $this->assertTrue(($oldLv+$enforceNum == $heroObj->getLevel()),"enforce hero,but not level up");
	    $this->assertTrue(($soul == $heroObj->getSoul()),'level up,hero soul not equal to exp tbl.');
	    $this->assertTrue(($soul - $oldSoul == ($userSoul - $userObj->getSoul())),"hero enforce expend user soul wrongly.");
	    $silverDelt = intval(($soul - $oldSoul) * $heroObj->getConf(CreatureAttr::LVLUP_RATIIO)/100);
	    $this->assertTrue(($userSilver - $userObj->getSilver() == $silverDelt),'hero enforce expend user silver wrongly. please check.');
	}
	
	public function test_enforce_byhero()
	{
	    Logger::debug('======%s======', __METHOD__);
	    $htid    =    $this->getNormalHtid();
	    $userObj = EnUser::getUserObj(self::$uid);
	    $heroMng = $userObj->getHeroManager();
	    $hid = $heroMng->addNewHero($htid);
	    $heroObj = $heroMng->getHeroObj($hid);
	    //消耗一个武将进行强化
	    for($i=0;$i<1;$i++)
	    {
	        $htid    =    $this->getHtidNoFiveStar();
	        $consumeHids[] = $heroMng->addNewHero($htid);
	    }
	    $userObj->update();
	    $this->enforceByHero($hid, $consumeHids);
	    //消耗三个武将进行强化
	    $consumeHids = array();
	    for($i=0;$i<3;$i++)
	    {
	        $htid    =    $this->getHtidNoFiveStar();
	        $consumeHids[] = $heroMng->addNewHero($htid);
	    }
	    $userObj->update();
	    $this->enforceByHero($hid, $consumeHids);
	    //消耗五星武将进行强化
	    $consumeHids = array();
	    for($i=0;$i<1;$i++)
	    {
	        $htid    =    $this->getHtidFiveStar();
	        $consumeHids[] = $heroMng->addNewHero($htid);
	    }
	    $userObj->update();
	    $this->enforceByHero($hid, $consumeHids);
	    //消耗转生武将进行强化
	    $consumeHids = array();
	    for($i=0;$i<1;$i++)
	    {
	        $htid    =    $this->getHtidNoFiveStar();
	        $hid = $heroMng->addNewHero($htid);
	        $consumeHids[] = $hid;
	        $heroObj = $heroMng->getHeroObj($hid);
	        $heroObj->convertUp();
	    }
	    $userObj->update();
	    $this->enforceByHero($hid, $consumeHids);
	    //消耗阵型武将进行强化
	    $consumeHids = array();
	    $userObj->levelUp(UserConf::MAX_LEVEL - $userObj->getLevel());
	    for($i=0;$i<1;$i++)
	    {
	        $htid    =    $this->getHtidNoFiveStar();
	        $hid = $heroMng->addNewHero($htid);
	        $consumeHids[] = $hid;
	        $fmt = EnFormation::getFormationObj(self::$uid);
	        $fmt->addHero($hid, 3);
	    }
	    $userObj->update();
	    $this->enforceByHero($hid, $consumeHids);
	}
	
	private function enforceByHero($hid,$consumeHids)
	{
	    $userObj = EnUser::getUserObj(self::$uid);
	    $heroMng = $userObj->getHeroManager();
	    $heroObj = $heroMng->getHeroObj($hid);
	    $addSoul = 0;
	    $soul = $heroObj->getSoul();
	    $userSilver = $userObj->getSilver();
	    foreach($consumeHids as $index => $conHid)
	    {
	        $tmpHero = $heroMng->getHeroObj($conHid);
	        $addSoul += ($tmpHero->getConf(CreatureAttr::SOUL) 
	                + $tmpHero->getSoul());
	    }
	    $needSilver = intval($addSoul * ($heroObj->getConf(CreatureAttr::LVLUP_RATIIO)/100));
	    $hero = new Hero();
	    try
	    {
	        $ret = $hero->enforceByHero($hid, $consumeHids);
	    }
	    catch(Exception $e)
	    {
	        Logger::warning('enforceByHero failed ,error message %s.',$e->getMessage());
	        return;
	    }
	    $this->assertTrue(($userObj->getSilver() + $needSilver == $userSilver),'enforcehero by hero expend user silver.');
	    $this->assertTrue(($soul + $addSoul == $heroObj->getSoul()),'enforce by hero add soul wrongly.');
	    $allHero = $heroMng->getAllHero();
	    foreach($consumeHids as $index => $conHid)
	    {
	        $this->assertTrue((isset($allHero[$conHid]) == FALSE),'consume hero '.$conHid.' failed.');
	    }
	}

	public function test_sell()
	{	
	    Logger::debug('======%s======', __METHOD__);
		$userObj = EnUser::getUserObj(self::$uid);
	    $heroMng = $userObj->getHeroManager();
		$userObj->addSoul(1000000);
		$userObj->addSilver(1000000);
		//卖出一个武将
		$sellHero = array();
		for($i=0;$i<1;$i++)
		{
		    $htid = $this->getHtidNoFiveStar();
		    $sellHero[] = $heroMng->addNewHero($htid);
		}
		$userObj->update();
		$this->sell($sellHero);
		//卖出三个武将
		$sellHero = array();
		for($i=0;$i<3;$i++)
		{
		    $htid = $this->getHtidNoFiveStar();
		    $sellHero[] = $heroMng->addNewHero($htid);
		}
		$userObj->update();
		$this->sell($sellHero);
		//卖出大于五星的武将
		$sellHero = array();
		for($i=0;$i<1;$i++)
		{
		    $htid = $this->getHtidFiveStar();
		    $sellHero[] = $heroMng->addNewHero($htid);
		}
		$userObj->update();
		$this->sell($sellHero);
		//卖出主角武将
		$sellHero[] = $userObj->getMasterHid();
		$this->sell($sellHero);
		//卖出阵型上的武将
		$userObj->levelUp(UserConf::MAX_LEVEL - $userObj->getLevel());
		$fmt = EnFormation::getFormationObj(self::$uid);
		$sellHero = array();
		for($i=0;$i<1;$i++)
		{
		    $htid = $this->getHtidFiveStar();
		    $hid = $heroMng->addNewHero($htid);
		    $sellHero[] = $hid;
		    $fmt->addHero($hid, 2);
		}
		$userObj->update();
		$this->sell($sellHero);
		//卖出转生过的武将
		$sellHero = array();
		for($i=0;$i<1;$i++)
		{
		    $htid = $this->getHtidFiveStar();
		    $hid = $heroMng->addNewHero($htid);
		    $sellHero[] = $hid;
		    $heroObj = $heroMng->getHeroObj($hid);
		    $heroObj->convertUp();
		}
		$userObj->update();
		$this->sell($sellHero);
	}
	
	private function sell($hids)
	{
	    $userObj = EnUser::getUserObj(self::$uid);
	    $userSilver = $userObj->getSilver();
	    $heroMng = $userObj->getHeroManager();
	    $addSilver = 0;
	    foreach($hids as $index => $hid)
	    {
	        $heroObj = $heroMng->getHeroObj($hid);
	        $addSilver += $heroObj->getConf(CreatureAttr::PRICE);
	    }
	    $hero = new Hero();
	    try
	    {
	        $ret = $hero->sell($hids);
	    }
	    catch(Exception $e)
	    {
	        Logger::warning('enforceByHero failed ,error message %s.',$e->getMessage());
	        return;
	    }
	    $this->assertTrue(($userObj->getSilver()-$addSilver == $userSilver),"sell hero,add silver to user failed.");
	    $allHero = $heroMng->getAllHero();
	    foreach($hids as $index => $hid)
	    {
	        $this->assertTrue((isset($allHero[$hid]) == FALSE),'sell hero '.$hid.' failed.');
	    }
	}
	
	private function evolveHero($hid,$consumeHids)
	{
	    $userObj = EnUser::getUserObj(self::$uid);
	    $heroMng = $userObj->getHeroManager();
	    $heroObj = $heroMng->getHeroObj($hid);
	    $evolveLv = $heroObj->getEvolveLv();
	    $hero = new Hero();
	    try
	    {
	        $ret = $hero->evolve($hid, $consumeHids);
	    }
	    catch(Exception $e)
	    {
	        Logger::warning('evolve hero failed ,error message %s.',$e->getMessage());
	        return;
	    }
	    $this->assertTrue(($heroObj->getEvolveLv()-1 == $evolveLv),'hero evolve,evolvelv up.');
	    $allHero = $heroMng->getAllHero();
	    foreach($consumeHids as $index => $hid)
	    {
	        $this->assertTrue((isset($allHero[$hid]) == FALSE),'sell hero '.$hid.' failed.');
	    }
	}
	
	public function test_evolve()
	{
	    Logger::debug('======%s======', __METHOD__);
	    Logger::trace('uid1 %s,uid2 %s.',RPCContext::getInstance()->getUid(),self::$uid);
		$userObj = EnUser::getUserObj(self::$uid);
		$heroMng = $userObj->getHeroManager();
		$htid = $this->getHtidWithEvlTbl();
		$hid = $heroMng->addNewHero($htid);
		$heroObj = $heroMng->getHeroObj($hid);
		$evolveTblIds    =    Creature::getHeroConf($htid, CreatureAttr::EVOLVE_TBLID);
		Logger::trace('get htid %s with evolve tbl %s.',$htid,$evolveTblIds->toArray());
		$evolveTblId    =    $evolveTblIds[$heroObj->getEvolveLv()];
		$this->evolveHero($hid, array());
		//消耗卡牌  消耗物品
		$needItems = btstore_get()->HERO_CONVERT[$evolveTblId]['arrNeedItem'];
		$bag = BagManager::getInstance()->getBag(self::$uid);
		foreach($needItems as $item)
		{
		    $bag->addItem($item[0],intval($item[1]));
		}
		//消耗的武将
		$consumeHids = array();
		$needHero	=	btstore_get()->HERO_CONVERT[$evolveTblId]['arrNeedHero']->toArray();
		foreach($needHero as $index => $info)
		{
		    $num = $info[2];
		    for($i=0;$i<$num;$i++)
		    {
		        $hid = $heroMng->addNewHero($info[0]);
		        $tmpHero = $heroMng->getHeroObj($hid);
		        $tmpHero->levelUp($info[1]-$tmpHero->getLevel());
		        $consumeHids[] = $hid;
		    }
		}
		$userObj->update();
		$this->evolveHero($hid, $consumeHids);
	}

	private function getNormalHtid()
	{
	    $heroes = btstore_get()->HEROES->toArray();
	    foreach($heroes as $htid => $heroInfo)
	    {
	        if(HeroUtil::isMasterHtid($htid) == TRUE)
	        {
	            unset($heroes[$htid]);
	        }
	    }
	    $htid    =    array_rand($heroes,1);
	    return $htid;
	}
	
	private function getHtidNoFiveStar()
	{
	    $htid = $this->getNormalHtid();
	    while(Creature::getHeroConf($htid, CreatureAttr::STAR_LEVEL) >= 5)
	    {
	        $htid = $this->getNormalHtid();
	    }
	    return $htid;
	}
	
	private function getHtidFiveStar()
	{
	    $htid = $this->getNormalHtid();
	    while(Creature::getHeroConf($htid, CreatureAttr::STAR_LEVEL) < 5)
	    {
	        $htid = $this->getNormalHtid();
	    }
	    return $htid;
	}
	
	private function getHtidWithEvlTbl()
	{
	    $htid = $this->getNormalHtid();
	    $evlTbl = Creature::getHeroConf($htid, CreatureAttr::EVOLVE_TBLID)->toArray();
	    while(empty($evlTbl))
	    {
	        $htid = $this->getNormalHtid();
	        $evlTbl = Creature::getHeroConf($htid, CreatureAttr::EVOLVE_TBLID)->toArray();
	    }
	    return $htid;
	}
	private function getHtidNoEvlTbl()
	{
	    $htid = $this->getNormalHtid();
	    $evlTbl = Creature::getHeroConf($htid, CreatureAttr::EVOLVE_TBLID)->toArray();
	    while(empty($evlTbl) == FALSE)
	    {
	        $htid = $this->getNormalHtid();
	        $evlTbl = Creature::getHeroConf($htid, CreatureAttr::EVOLVE_TBLID)->toArray();
	    }
	    return $htid;
	}
	
	public function testBattleData()
	{
	    $htid = $this->getNormalHtid();
	    $userObj = EnUser::getUserObj(self::$uid);
	    $heroMng = $userObj->getHeroManager();
	    $hid = $heroMng->addNewHero($htid);
	    
	    //1.进阶
	    //2.连携加成
	    //3.装备加成
	    //4.名将加成
	    
	}
	
	private function getBattleByEvolveLvTest()
	{
	    $htid = $this->getNormalHtid();
	    $userObj = EnUser::getUserObj(self::$uid);
	    $heroMng = $userObj->getHeroManager();
	    $hid = $heroMng->addNewHero($htid);
	    $heroObj = $heroMng->getHeroObj($hid);
	    $evoLv = 3;
	    $heroObj->setEvolveLevel($evoLv);
	    $battleData = $heroObj->getBattleInfo();
	    
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
