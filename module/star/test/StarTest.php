<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: StarTest.php 128531 2014-08-22 03:24:43Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/star/test/StarTest.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-08-22 03:24:43 +0000 (Fri, 22 Aug 2014) $
 * @version $Revision: 128531 $
 * @brief 
 *  
 **/
class StarTest extends PHPUnit_Framework_TestCase
{
	protected static $uid = 22828;
	
	public static function setUpBeforeClass()
	{
		self::createUser();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		EnSwitch::getSwitchObj(self::$uid)->addNewSwitch(SwitchDef::STAR);
		EnSwitch::getSwitchObj(self::$uid)->save();
		self::addHeroStar();
	}
	
	protected function setUp()
	{
	}

	protected function tearDown()
	{
	}
	
	public static function createUser() 
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval($pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		self::$uid = $ret['uid'];
		echo "test user: " . self::$uid . "\n";
	}
	
	public static function addHeroStar($stid = 0)
	{
		$allStarConf = btstore_get()->STAR;
		if (empty($stid)) 
		{
			$stid = key($allStarConf);
		}
		$htid = $allStarConf[$stid][StarDef::STAR_NEED_HER0];
		$user = EnUser::getUserObj(self::$uid);
		$user->getHeroManager()->addNewHero($htid);
		$user->update();
	}
	
	public static function getTypeNum($reward)
	{
		$num = array();
		$user = EnUser::getUserObj();
		foreach ($reward as $type => $value)
		{
			switch ($type)
			{
				case StarConf::STAR_TYPE_SILVER:
					$num[$type] = $user->getSilver();
					break;
				case StarConf::STAR_TYPE_GOLD:
					$num[$type] = $user->getGold();
					break;
				case StarConf::STAR_TYPE_SOUL:
					$num[$type] = $user->getSoul();
					break;
				case StarConf::STAR_TYPE_STAMINA:
					$num[$type] = $user->getStamina();
					break;
				case StarConf::STAR_TYPE_EXECUTION:
					$num[$type] = $user->getCurExecution();
					break;
				case StarConf::STAR_TYPE_EXP:
					$num[$type] = $user->getExp();
					break;
				case StarConf::STAR_TYPE_STAMINA_LIMIT:
					$num[$type] = $user->getStaminaMaxNum();
					break;
			}
		}
		return $num;
	}
	
	public function test_getAllStarInfo()
	{
		Logger::debug('======%s======', __METHOD__);
		$star = new Star();
		$ret = $star->getAllStarInfo();
		Logger::trace('all star info:%s', $ret);
		$this->assertEquals('ok', $ret['ret']);
		$this->assertTrue(!empty($ret['allStarInfo']));
		$this->assertEquals(0, $ret['allStarInfo']['send_num']);
		$this->assertTrue(!empty($ret['allStarInfo']['star_list']));
		$this->assertEquals(1, count($ret['allStarInfo']['star_list']));
	}
	
	public function test_addFavorByGift()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = BagManager::getInstance()->getBag();
		$ret = $bag->bagInfo();
		$propBagBefore = $ret[BagDef::BAG_PROPS];
		
		$star = new Star();
		$ret = $star->getAllStarInfo();
		Logger::trace('all star info:%s', $ret);
		$sid = key($ret['allStarInfo']['star_list']);
		$stid = $ret['allStarInfo']['star_list'][$sid]['star_tid'];
		$levelBefore = $ret['allStarInfo']['star_list'][$sid]['level'];
		$expBefore = $ret['allStarInfo']['star_list'][$sid]['total_exp'];
		
		$itemTplId = btstore_get()->STAR[$stid][StarDef::STAR_FAVOR_GIFT][0];
		$levelId = btstore_get()->STAR[$stid][StarDef::STAR_LEVEL_ID];
		$addFavor = btstore_get()->ITEMS[$itemTplId][ItemDef::ITEM_ATTR_NAME_GOODWILL_EXP];
		$addLevel = 1;
		$favorAbility = StarLogic::getFavorAbility($stid, $addLevel);
		$needLevel = $favorAbility[1];
		$user = EnUser::getUserObj(self::$uid);
		if ($user->getLevel() < $needLevel)
		{
			$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
			$user->addExp($expTable[$needLevel + 10]);
			$user->update();
		}
		$addExp = btstore_get()->STAR_LEVEL[$levelId][StarDef::STAR_FAVOR_LEVEL][$addLevel];
		$itemNum = 1 + $addExp / $addFavor;
		$abilityId = btstore_get()->STAR[$stid][StarDef::STAR_FAVOR_ABILITY][$addLevel][0];
		$abConf = btstore_get()->STAR_ABILITY[$abilityId]->toArray();
		$items = $abConf[StarDef::STAR_ABILITY_ITEM];
		$reward = $abConf[StarDef::STAR_ABILITY_REWARD];
		$reward[StarConf::STAR_TYPE_STAMINA_LIMIT] = $abConf[StarDef::STAR_ABILITY_STAMINA];
		$numBefore = $this->getTypeNum($reward);
		if(isset($reward[StarConf::STAR_TYPE_GOODWILL]))
		{
			$reward[StarConf::STAR_TYPE_GOODWILL] += $addExp;
			$numBefore[StarConf::STAR_TYPE_GOODWILL] = $expBefore;
		}
		
		$bag->addItemByTemplateID($itemTplId, $itemNum);
		$bag->update();
		
		$ret = $star->addFavorByGift($sid, $itemTplId, $itemNum);
		$this->assertEquals(false, $ret);
		
		$ret = $star->getAllStarInfo();
		$levelAfter = $ret['allStarInfo']['star_list'][$sid]['level'];
		$expAfter = $ret['allStarInfo']['star_list'][$sid]['total_exp'];
		$numAfter = $this->getTypeNum($reward);
		if(isset($reward[StarConf::STAR_TYPE_GOODWILL]))
		{
			$numAfter[StarConf::STAR_TYPE_GOODWILL] = $expAfter;
		}
		
		$this->assertEquals($levelBefore + $addLevel, $levelAfter);

		if (!empty($items)) 
		{
			$ret = $bag->bagInfo();
			$propBagAfter = $ret[BagDef::BAG_PROPS];
			$num = current($items);
			$this->assertEquals(count($propBagBefore) + $num, count($propBagAfter));
		}
	}
	
	public function test_addFavorByGold()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$star = new Star();
		$ret = $star->getAllStarInfo();
		Logger::trace('all star info:%s', $ret);
		$sid = key($ret['allStarInfo']['star_list']);
		$stid = $ret['allStarInfo']['star_list'][$sid]['star_tid'];
		$expBefore = $ret['allStarInfo']['star_list'][$sid]['total_exp'];
		
		$subGold = btstore_get()->STAR_ALL[StarDef::STAR_GOLD_BASE];
		$addFavor = btstore_get()->STAR_ALL[StarDef::STAR_GOLD_FAVOR];
		$user = EnUser::getUserObj();
		$goldBefore = $user->getGold();
		
		$ret = $star->addFavorByGold($sid);
		$this->assertEquals('ok', $ret);
		
		$ret = $star->getAllStarInfo();
		$expAfter = $ret['allStarInfo']['star_list'][$sid]['total_exp'];
		$goldAfter = $user->getGold();
		$this->assertTrue($ret['allStarInfo']['send_num'] == 1);
		$this->assertEquals($expBefore + $addFavor, $expAfter);
		$this->assertEquals($goldBefore - $subGold, $goldAfter);
	}
	
	public function test_addFavorByAct()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$star = new Star();
		$ret = $star->getAllStarInfo();
		Logger::trace('all star info:%s', $ret);
		$sid = key($ret['allStarInfo']['star_list']);
		$stid = $ret['allStarInfo']['star_list'][$sid]['star_tid'];
		$levelBefore = $ret['allStarInfo']['star_list'][$sid]['level'];
		$expBefore = $ret['allStarInfo']['star_list'][$sid]['total_exp'];
		
		$actId = btstore_get()->STAR[$stid][StarDef::STAR_FAVOR_ACT][0];
		$actInfo = btstore_get()->STAR_ACT[$actId]; 
		$needStamina = $actInfo[StarDef::STAR_STAMINA_BASE];
		$type = $actInfo[StarDef::STAR_REWARD_TYPE];
		$rewardNum = $actInfo[StarDef::STAR_REWARD_NUM][$levelBefore];
		$reward = array($type => $rewardNum);
		$numBefore = $this->getTypeNum($reward);
		if(isset($reward[StarConf::STAR_TYPE_GOODWILL]))
		{
			$numBefore[StarConf::STAR_TYPE_GOODWILL] = $expBefore;
		}
		$user = EnUser::getUserObj();
		$staminaBefore = $user->getStamina();
		
		$ret = $star->addFavorByAct($sid, $actId);
		$this->assertEquals('ok', $ret['ret']);
		$this->assertTrue(isset($ret['trigerId']));
		
		$ret = $star->getAllStarInfo();
		$expAfter = $ret['allStarInfo']['star_list'][$sid]['total_exp'];
		$numAfter = $this->getTypeNum($reward);
		if(isset($reward[StarConf::STAR_TYPE_GOODWILL]))
		{
			$numAfter[StarConf::STAR_TYPE_GOODWILL] = $expAfter;
		}
		
	}
	
	public function test_answer()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$star = new Star();
		$ret = $star->getAllStarInfo();
		Logger::trace('all star info:%s', $ret);
		$sid = key($ret['allStarInfo']['star_list']);
		$stid = $ret['allStarInfo']['star_list'][$sid]['star_tid'];
		$levelBefore = $ret['allStarInfo']['star_list'][$sid]['level'];
		$expBefore = $ret['allStarInfo']['star_list'][$sid]['total_exp'];
		
		$trigerId = key(btstore_get()->STAR_TRIGER->toArray());
		$optionId = 1;
		RPCContext::getInstance()->setSession(StarDef::TRIGER_SESSION_KEY, array($sid => $trigerId));
		
		$trigerInfo = btstore_get()->STAR_TRIGER[$trigerId][$optionId];
		$user = EnUser::getUserObj();
		
		if ($trigerInfo[2] == 1)
		{
			$userLevel = $user->getLevel();
			$num = $trigerInfo[1] * $userLevel;
		}
		else
		{
			$num = $trigerInfo[1];
		}
		$reward = array($trigerInfo[0] => $num);
		$numBefore = $this->getTypeNum($reward);
		if(isset($reward[StarConf::STAR_TYPE_GOODWILL]))
		{
			$numBefore[StarConf::STAR_TYPE_GOODWILL] = $expBefore;
		}
		
		$ret = $star->answer($sid, $trigerId, $optionId);
		$this->assertEquals('ok', $ret);
		
		$ret = $star->getAllStarInfo();
		$expAfter = $ret['allStarInfo']['star_list'][$sid]['total_exp'];
		$numAfter = $this->getTypeNum($reward);
		if(isset($reward[StarConf::STAR_TYPE_GOODWILL]))
		{
			$numAfter[StarConf::STAR_TYPE_GOODWILL] = $expAfter;
		}
		
		foreach ($numBefore as $type => $value)
		{
			$this->assertEquals($value + $reward[$type], $numAfter[$type]);
		}
	}

	public function test_swap()
	{
		Logger::debug('======%s======', __METHOD__);
		
		//名将b
		$stida = 10002;
		$stidb = 10003;
		$exp = StarLogic::getExpByLevel($stida, 1);
		self::addHeroStar($stida);
		self::addHeroStar($stidb);
		
		$mystar = MyStar::getInstance(self::$uid);
		$allStid = $mystar->getAllStarTid();
		$sida = array_search($stida, $allStid);
		$sidb = array_search($stidb, $allStid);
		$mystar->setStarLevel($sida, 1);
		$mystar->setStarExp($sida, $exp);
		$mystar->update();
		
		$user = EnUser::getUserObj(self::$uid);
		$aQuality = btstore_get()->STAR[$stida][StarDef::STAR_QUALITY];
		$cost = btstore_get()->STAR_ALL[StarDef::STAR_SWAP_COST][$aQuality];
		$user->addGold($cost, StatisticsDef::ST_FUNCKEY_STAR_SWAP);
		$user->update();
		$goldBefore = $user->getGold();
		
		$star = new Star();
		$star->swap($sida, $sidb);
		
		$this->assertEquals(0, $mystar->getStarLevel($sida));
		$this->assertEquals(0, $mystar->getStarExp($sida));
		$this->assertEquals(1, $mystar->getStarLevel($sidb));
		$this->assertEquals($exp, $mystar->getStarExp($sidb));
		$goldAfter = $user->getGold();
		$this->assertEquals($goldAfter, $goldBefore - $cost);
	}
	
	public function test_draw()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$star = new Star();
		$ret = $star->getAllStarInfo();
		foreach ($ret['allStarInfo']['star_list'] as $sid => $starInfo)
		{
			$feelExp = $starInfo['feel_total_exp'];
		}
		
		$ret = $star->draw($sid);
		$mystar = MyStar::getInstance(self::$uid);
		$this->assertEquals(1, $mystar->getDrawNum());
		$this->assertEquals($ret, $mystar->getStarDraw($sid));
	}
	
	public function test_shuffle()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$star = new Star();
		$ret = $star->getAllStarInfo();
		foreach ($ret['allStarInfo']['star_list'] as $sid => $starInfo)
		{
			$feelExp = $starInfo['feel_total_exp'];
		}
		
		$shuffleCost = btstore_get()->STAR_TEACH[StarDef::STAR_SPECIAL_COST];
		$user = EnUser::getUserObj(self::$uid);
		$user->addGold($shuffleCost, StatisticsDef::ST_FUNCKEY_STAR_SHUFFLE);
		$user->update();
		$goldBefore = $user->getGold();
		$ret = $star->shuffle($sid);
		
		$goldAfter = $user->getGold();
		$mystar = MyStar::getInstance(self::$uid);
		$this->assertEquals($goldAfter, $goldBefore - $shuffleCost);
		$this->assertEquals($ret, $mystar->getStarDraw($sid));
	}
	
	public function test_getReward()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$star = new Star();
		$ret = $star->getAllStarInfo();
		foreach ($ret['allStarInfo']['star_list'] as $sid => $starInfo)
		{
			$feelExp = $starInfo['feel_total_exp'];
		}
		$mystar = MyStar::getInstance(self::$uid);
		$expBefore = $mystar->getStarFeelExp($sid);
		$draw = $mystar->getStarDraw($sid);
		$index = $draw[0];
		$addFeel = btstore_get()->STAR_TEACH[StarDef::STAR_DRAW_COMBINATION][$index]['feel'];
		
		$star->getReward($sid);
		$this->assertEquals(array(), $mystar->getStarDraw($sid));
		$this->assertEquals($expBefore + $addFeel, $mystar->getStarFeelExp($sid));
	}
	
	public function test_changeSkill()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$star = new Star();
		$ret = $star->getAllStarInfo();
		foreach ($ret['allStarInfo']['star_list'] as $sid => $starInfo)
		{
			$feelExp = $starInfo['feel_total_exp'];
		}
		
		$mystar = MyStar::getInstance(self::$uid);
		$mystar->setStarFeelSkill($sid, 1010);
		$mystar->setEquipSkill($sid);
		$mystar->update();
		
		$star = new Star();
		$ret = $star->getAllStarInfo();
		$this->assertEquals($sid, $ret['allStarInfo']['va_act_info']['skill']);
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */