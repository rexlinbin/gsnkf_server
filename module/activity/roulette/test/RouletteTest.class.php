<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RouletteTest.class.php 156209 2015-01-30 09:34:34Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/roulette/test/RouletteTest.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-01-30 09:34:34 +0000 (Fri, 30 Jan 2015) $
 * @version $Revision: 156209 $
 * @brief 
 *  
 **/
class roulettetest extends PHPUnit_Framework_TestCase
{
	private static $uid;
	private static $pid;
	private static $tblName = 't_roulette';
	private static $tblUser = 't_user';
	
	public static function setUpBeforeClass()
	{
		self::$pid = IdGenerator::nextId('uid');
		$utid = 1;
		$uname = strval(self::$pid);
		$ret = UserLogic::createUser(self::$pid, $utid, $uname);
		
		if ($ret['ret'] != 'ok')
		{
			echo "create user failed \n";
			exit();
		}
		
		self::$uid = $ret['uid'];
	}
	
	protected function setUp()
	{
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
	}
	
	protected function tearDown()
	{
		
	}
	
	//测试拉取数据
	public function test_rouletteInfo()
	{
		$ret = RouletteLogic::getRouletteInfo(self::$uid);
	
		$res = RouletteDao::getRouletteInfo(self::$uid, RouletteDef::$ALL_TABLE_FIELD);
		
		if(empty($res))
		{
			$res[RouletteDef::SQL_FIELD_UID] = self::$uid;
			$res[RouletteDef::SQL_TODAY_FREE_NUM] = 0;
			$res[RouletteDef::SQL_ACCUM_FREE_NUM] = 0;
			$res[RouletteDef::SQL_ACCUM_GOLD_NUM] = 0;
			$res[RouletteDef::SQL_ACHIEVE_INTEGERAL] = 0;
			$res[RouletteDef::SQL_VA_BOX_REWARD] = array('arrRewarded' => array());
			$res[RouletteDef::SQL_LAST_RFR_TIME] = Util::getTime();
		}
	
		$arrRewarded = $res[RouletteDef::SQL_VA_BOX_REWARD]['arrRewarded'];
		$maxRewardIndex = RouletteLogic::getBoxNum($res['integeral']);
	
		$arrBoxStatus = array();
	
		$actConf = EnActivity::getConfByName(ActivityName::ROULETTE);
		$integeralBox = $actConf['data']['box_integeral'];
	
		$sumBoxNum = count($integeralBox);
		for ($i = 0; $i < $sumBoxNum; $i++)
		{
			$index = $i + 1;
			$status = 1;
			if ( in_array($index, $arrRewarded) )
			{
				$status = 3;
			}
			else if ( $index <= $maxRewardIndex )
			{
				$status = 2;
			}
			$arrBoxStatus[$i] = array('status' => $status);
		}
		$res[RouletteDef::SQL_VA_BOX_REWARD] = $arrBoxStatus;
		
		unset($res[RouletteDef::SQL_ACCUM_FREE_NUM]);
		unset($res[RouletteDef::SQL_LAST_RFR_TIME]);
	
		$this->assertEquals($res, $ret);
	}
	
	public function test_receiveBoxReward()
	{
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		
		self::initRouletteInfo(self::$uid);
		
		try {
			RouletteLogic::receiveBoxReward(0, self::$uid);
			$this->assertTrue(0);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		$actConf = EnActivity::getConfByName(ActivityName::ROULETTE);
		$integeralBox = $actConf['data']['box_integeral'];
		$sumBoxNum = count($integeralBox);
		
		try {
			RouletteLogic::receiveBoxReward($sumBoxNum, self::$uid);
			$this->assertTrue(0);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		$integeralEachTime = $actConf['data'][RouletteDef::BTSTORE_ROULETTE_INTEGERAL];
		$minNeedIntegeral = $integeralBox[1];
		
		self::addUserGoldNum(20000, self::$uid);
		
		$rollNum = intval(ceil($minNeedIntegeral / $integeralEachTime));
		RouletteLogic::rollRoulette($rollNum, self::$uid);
		RouletteLogic::receiveBoxReward(1, self::$uid);
		
		try {
			RouletteLogic::receiveBoxReward(1, self::$uid);
			$this->assertTrue(0);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
	}
	
	public function test_rollRoulette()
	{
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		
		self::initRouletteInfo(self::$uid);
		
		$bag = new Bag();
		$bag->clearBag();
		$bag->update();
		
		$actConf = EnActivity::getConfByName(ActivityName::ROULETTE);
		$goldEachTime = $actConf['data'][RouletteDef::BTSTORE_ROULETTE_NEED_GOLD];
		
		$vip = EnUser::getUserObj(self::$uid)->getVip();
		$dayFreeNum = btstore_get()->VIP[$vip]['rouletteFreeNum'];
		$totalGoldNum = btstore_get()->VIP[$vip]['rouletteTotalNum'];
		
		$totalNeedGold = $goldEachTime * $totalGoldNum;
		self::addUserGoldNum($totalNeedGold, self::$uid);
		
		RouletteLogic::rollRoulette($dayFreeNum, self::$uid);
		
		$rouletteInfo = RouletteDao::getRouletteInfo(self::$uid, RouletteDef::$ALL_TABLE_FIELD);
		
		$this->assertEquals($dayFreeNum, $rouletteInfo[RouletteDef::SQL_ACCUM_FREE_NUM]);
		$this->assertEquals(0, $rouletteInfo[RouletteDef::SQL_ACCUM_GOLD_NUM]);
		
		RouletteLogic::rollRoulette(1, self::$uid);
		
		$rouletteInfo = RouletteDao::getRouletteInfo(self::$uid, RouletteDef::$ALL_TABLE_FIELD);
		
		$this->assertEquals($dayFreeNum, $rouletteInfo[RouletteDef::SQL_ACCUM_FREE_NUM]);
		$this->assertEquals(1, $rouletteInfo[RouletteDef::SQL_ACCUM_GOLD_NUM]);
		
		//免费次数出现错误
		try {
			self::initRouletteInfo(self::$uid);
			
			$myRoulette = MyRoulette::getInstance(self::$uid);
			
			for ($i = 0; $i <= $dayFreeNum; $i++)
			{
				$myRoulette->rouletteFreeOnce();
			}
			
			$myRoulette->save();
			
			RouletteLogic::rouletteOnce(self::$uid);
			
			$this->assertTrue(0);
		}
		catch (Exception $e)
		{
			$this->assertEquals('inter', $e->getMessage());
		}
		
		//金币次数出现错误
		try {
			self::initRouletteInfo(self::$uid);
			
			RouletteLogic::rollRoulette($dayFreeNum + $totalGoldNum, self::$uid);
			
			$myRoulette = MyRoulette::getInstance(self::$uid);
			
			$myRoulette->rouletteGoldOnce();
			$myRoulette->save();
			
			RouletteLogic::rouletteOnce(self::$uid);
			
			$this->assertTrue(0);
		}
		catch (Exception $e)
		{
			$this->assertEquals('inter', $e->getMessage());
		}
		
		//超过次数
		try {
			self::initRouletteInfo(self::$uid);
			self::addUserGoldNum($totalGoldNum + $goldEachTime, self::$uid);
			
			RouletteLogic::rollRoulette($dayFreeNum + $totalGoldNum +1, self::$uid);
			$this->assertTrue(0);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		//金币不足
		try {
			self::setUserGoldNumZero(self::$uid);
			RouletteLogic::rollRoulette($dayFreeNum + 1, self::$uid);
			$this->assertTrue(0);
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		//背包不足
		try {
			$bag = new Bag();
			$bag->clearBag();
			$bag->update();
			
			$itemTplId = self::getItemTpl(ItemDef::ITEM_TYPE_DIRECT);
			$itemNum = $bag->getItemNumByTemplateID($itemTplId);
			$stackNum = ItemManager::getInstance()->getItemStackable($itemTplId);
			self::addItemByTpl($bag, $itemTplId, BagConf::INIT_GRID_NUM_PROPS*$stackNum-$itemNum);
			
			RouletteLogic::rollRoulette(1, self::$uid);
			
			$this->assertTrue(0);
			
		}
		catch (Exception $e)
		{
			$this->assertEquals('fake', $e->getMessage());
		}
	}
	
	public function test_secondDay()
	{
		$uid = RPCContext::getInstance()->getUid();
		$rouletteInfo = MyRoulette::getInstance($uid)->getRouletteInfo();
		$rouletteInfo[RouletteDef::SQL_LAST_RFR_TIME] = Util::getTime() - SECONDS_OF_DAY - 1;
		RouletteDao::updateRouletteInfo($uid, $rouletteInfo);
		MyRoulette::getInstance()->release();
		
		$rouletteInfo = RouletteLogic::getRouletteInfo($uid);
		$todayFreeNum = $rouletteInfo[RouletteDef::SQL_TODAY_FREE_NUM];
		
		$this->assertEquals(0, $todayFreeNum);
	}
	
	//TODO 将init改为release
	private static function initRouletteInfo($uid)
	{
		$myRoulette = MyRoulette::getInstance($uid);
		$myRoulette->initRouletteInfo();
		$myRoulette->save();

// 		MyRoulette::release();
// 		$myRoulette = MyRoulette::getInstance($uid);
// 		RouletteLogic::getRouletteInfo($uid);

// 		$myRoulette = MyRoulette::getInstance($uid);
// 		$myRoulette->release();
// 		RouletteLogic::getRouletteInfo($uid);

// 		MyRoulette::getInstance(self::$uid)->release();
// 		$myRoulette = MyRoulette::getInstance(self::$uid);
	}
	
	private static function addUserGoldNum($num,$uid)
	{
		$userObj = EnUser::getUserObj($uid);
		$userObj->addGold($num, 0);
	}
	
	private static function setUserGoldNumZero($uid)
	{
		$userObj = EnUser::getUserObj($uid);
		$curGoldNum = $userObj->getGold();
		$userObj->subGold($curGoldNum, 'test_roulette');
	}
	
	public static function getItemTpl($itemType)
	{
		$allItemConf = btstore_get()->ITEMS->toArray();
	
		$itemTplId = 0;
		foreach($allItemConf as $id => $itemConf)
		{
			if($itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] == $itemType)
			{
				$itemTplId = $id;
				break;
			}
		}
		return $itemTplId;
	}
	
	public static function addItemByTpl(&$bagObj, $itemTplId, $itemNum)
	{
		self::assertTrue($itemTplId > 0);
		$arrItemId = ItemManager::getInstance()->addItem($itemTplId , $itemNum);
		$ret = $bagObj->addItems($arrItemId, true);
		self::assertTrue($ret);
		return $arrItemId;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */