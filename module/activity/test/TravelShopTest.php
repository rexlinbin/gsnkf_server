<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TravelShopTest.php 198424 2015-09-14 08:55:38Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/TravelShopTest.php $
 * @author $Author: MingTian $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-09-14 08:55:38 +0000 (Mon, 14 Sep 2015) $
 * @version $Revision: 198424 $
 * @brief 
 *  
 **/
class TravelShopTest extends PHPUnit_Framework_TestCase
{
    private static $uid;

    public static function setUpBeforeClass()
    {
    	self::createUser();
    	RPCContext::getInstance()->setSession('global.uid', self::$uid);
    }
    
    protected function tearDown()
    {
    	TravelShopObj::release();
    	TravelShopUserObj::release(self::$uid);
    	RPCContext::getInstance()->resetSession();
    }
    
    private static function createUser()
    {
    	$pid = IdGenerator::nextId('uid');
    	$uname = strval($pid);
    	$ret = UserLogic::createUser($pid, 1, $uname);
    	self::$uid = $ret['uid'];
    	echo "test user: " . self::$uid . "\n";
    }
    
    public function test_getInfo()
    {
    	Logger::debug('======%s======', __METHOD__);
    	
    	$travelShop = new TravelShop();
    	$ret = $travelShop->getInfo();
    	$this->assertEquals(0, $ret[TravelShopDef::FIELD_SCORE]);
    	$this->assertEquals(0, $ret[TravelShopDef::FIELD_FINISH_TIME]);
    	$this->assertEquals(array(), $ret[TravelShopDef::BUY]);
    	$this->assertEquals(array(), $ret[TravelShopDef::PAYBACK]);
    	$this->assertEquals(array(), $ret[TravelShopDef::REWARD]);
    	$this->assertEquals(0, $ret['topup']);
    }
    
    public function buy($goodsId, $num)
    {
    	$travelShop = new TravelShop();
    	
    	$user = EnUser::getUserObj(self::$uid);
    	$bag = BagManager::getInstance()->getBag(self::$uid);
    	 
    	$goodsConf = TravelShopLogic::getGoodsConf($goodsId);
    	$add = $goodsConf[TravelShopDef::GOODS];
    	$sub = $goodsConf[TravelShopDef::COST];
    	$score = $goodsConf[TravelShopDef::SCORE];
    	 
    	$info = $travelShop->getInfo();
    	$beforeSum = $info[TravelShopDef::FIELD_SUM];
    	$beforeScore = $info[TravelShopDef::FIELD_SCORE];
    	$beforeFinishTime = $info[TravelShopDef::FIELD_FINISH_TIME];
    	$beforeGoodsNum = isset($info[TravelShopDef::BUY][$goodsId]) ? $info[TravelShopDef::BUY][$goodsId] : 0;
    	$beforePayback = $info[TravelShopDef::PAYBACK];
    	$beforeReward = $info[TravelShopDef::REWARD];
    	$beforeTopup = $info['topup'];
    	
    	foreach ($add as $arr)
    	{
    		switch ($arr[0])
    		{
    			case RewardConfType::SILVER:
    				$beforeSilver = $user->getSilver();
    				break;
    			case RewardConfType::ITEM_MULTI:
    				$beforeItemNum = $bag->getItemNumByTemplateID($arr[1]);
    				break;
    			default: echo "invalid type:".$arr[0];
    		}
    	}
    	foreach ($sub as $arr)
    	{
    		switch ($arr[0])
    		{
    			case RewardConfType::GOLD:
    				$user->addGold($arr[2] * $num, 0);
    				$beforeGold = $user->getGold();
    				break;
    			case RewardConfType::SILVER:
    				$user->addSilver($arr[2] * $num);
    				$beforeSilver = $user->getSilver();
    				break;
    			case RewardConfType::PRESTIGE:
    				$user->addPrestige($arr[2] * $num);
    				$beforePrestige = $user->getPrestige();
    				break;
    			case RewardConfType::HORNOR:
    				EnCompete::addHonor(self::$uid, $arr[2] * $num);
    				$beforeHonor = EnCompete::getHonor(self::$uid);
    				break;
    			default: echo "invalid type:".$arr[0];
    		}
    	}
    	 
    	self::tearDown();
    	
    	$travelShop->buy($goodsId, $num);
    	foreach ($add as $arr)
    	{
    		switch ($arr[0])
    		{
    			case RewardConfType::SILVER:
    				$afterSilver = $user->getSilver();
    				$this->assertEquals($beforeSilver + $arr[2] * $num, $afterSilver);
    				break;
    			case RewardConfType::ITEM_MULTI:
    				$afterItemNum = $bag->getItemNumByTemplateID($arr[1]);
    				$this->assertEquals($beforeItemNum + $arr[2] * $num, $afterItemNum);
    				break;
    			default: echo "invalid type:".$arr[0];
    		}
    	}
    	foreach ($sub as $arr)
    	{
    		switch ($arr[0])
    		{
    			case RewardConfType::GOLD:
    				$afterGold = $user->getGold();
    				$this->assertEquals($beforeGold - $arr[2] * $num, $afterGold);
    				break;
    			case RewardConfType::SILVER:
    				$afterSilver = $user->getSilver();
    				$this->assertEquals($beforeSilver - $arr[2] * $num, $afterSilver);
    				break;
    			case RewardConfType::PRESTIGE:
    				$afterPrestige = $user->getPrestige();
    				$this->assertEquals($beforePrestige - $arr[2] * $num, $afterPrestige);
    				break;
    			case RewardConfType::HORNOR:
    				$afterHonor = EnCompete::getHonor(self::$uid);
    				$this->assertEquals($beforeHonor - $arr[2] * $num, $afterHonor);
    				break;
    			default: echo "invalid type:".$arr[0];
    		}
    	}

    	$ret = $travelShop->getInfo();
    	$this->assertEquals($beforeSum + $num, $ret[TravelShopDef::FIELD_SUM]);
    	$this->assertEquals($beforeScore + $num * $score, $ret[TravelShopDef::FIELD_SCORE]);
    	if ($ret[TravelShopDef::FIELD_SCORE] >= TravelShopDef::SCORE_LIMIT) 
    	{
    		$this->assertEquals(Util::getTime(), $ret[TravelShopDef::FIELD_FINISH_TIME]);
    	}
    	else 
    	{
    		$this->assertEquals($beforeFinishTime, $ret[TravelShopDef::FIELD_FINISH_TIME]);
    	}
    	$this->assertEquals($beforeGoodsNum + $num, $ret[TravelShopDef::BUY][$goodsId]);
    	$this->assertEquals($beforePayback, $ret[TravelShopDef::PAYBACK]);
    	$this->assertEquals($beforeReward, $ret[TravelShopDef::REWARD]);
    	$this->assertEquals($beforeTopup, $ret['topup']);
    }
    
    public function test_buy()
    {
    	Logger::debug('======%s======', __METHOD__);
    	
    	//1.买一个商品
    	$goodsId = 1;
    	$num = 1;
    	$this->buy($goodsId, $num);
    	
    	//2.买到上限个商品
    	$goodsConf = TravelShopLogic::getGoodsConf($goodsId);
    	$limit = $goodsConf[TravelShopDef::LIMIT];
    	$num = $limit - $num;
    	$this->buy($goodsId, $num);
    	
    	//3.再买一个商品
    	try 
    	{
	    	$num = 1;
	    	$this->buy($goodsId, $num);
    	}
    	catch (Exception $e)
		{						
			$this->assertEquals('fake',  $e->getMessage());
		}
    }
    
    public function test_getPayback()
    {
    	Logger::debug('======%s======', __METHOD__);
    	
    	$console = new Console();
    	$travelShop = new TravelShop();
    	$user = EnUser::getUserObj(self::$uid);
    	
    	//1.未完成第一档充值
    	$id = 1;
    	list($pay, $back) = TravelShopLogic::getPaybackConf($id);
    	$console->addGoldOrder($pay - 1);
    	$info = $travelShop->getInfo();
    	$this->assertEquals($pay - 1, $info['topup']);
    	
    	self::tearDown();
    	
    	//2.完成第一档充值
    	$console->addGoldOrder(1);
    	$info = $travelShop->getInfo();
    	$this->assertEquals(0, $info[TravelShopDef::FIELD_SCORE]);
    	$this->assertEquals(0, $info[TravelShopDef::FIELD_FINISH_TIME]);
    	$this->assertEquals(TravelShopDef::NOGAIN, $info[TravelShopDef::PAYBACK][$id]);
    	$this->assertEquals($pay, $info['topup']);
    	
    	self::tearDown();
    	
    	//3.领取充值返利
    	$beforeGold = $user->getGold();
    	$travelShop->getPayback($id);
    	$this->assertEquals($beforeGold + $back, $user->getGold());
    	
    	$info = $travelShop->getInfo();
    	$this->assertEquals(0, $info[TravelShopDef::FIELD_SCORE]);
    	$this->assertEquals(0, $info[TravelShopDef::FIELD_FINISH_TIME]);
    	$this->assertEquals(TravelShopDef::GAIN, $info[TravelShopDef::PAYBACK][$id]);
    	$this->assertEquals(0, $info['topup']);
    }
    
    public function test_getReward()
    {
    	Logger::debug('======%s======', __METHOD__);
    	
    	$console = new Console();
    	$travelShop = new TravelShop();
    	$user = EnUser::getUserObj(self::$uid);
    	$bag = BagManager::getInstance()->getBag(self::$uid);
    	
    	//第一档的普天奖励
    	$conf = TravelShopLogic::getConf();
    	$id = key($conf[TravelShopDef::REWARD]);
    	$console->tsSum($id);
    	$reward = $conf[TravelShopDef::REWARD][$id];
    	
    	$info = $travelShop->getInfo();
    	$beforeSum = $info[TravelShopDef::FIELD_SUM];
    	$beforeScore = $info[TravelShopDef::FIELD_SCORE];
    	$beforeFinishTime = $info[TravelShopDef::FIELD_FINISH_TIME];
    	$beforePayback = $info[TravelShopDef::PAYBACK];
    	$beforeReward = $info[TravelShopDef::REWARD];
    	$beforeTopup = $info['topup'];
    	$this->assertTrue(!in_array($id, $beforeReward));
    	 
    	foreach ($reward as $arr)
    	{
    		switch ($arr[0])
    		{
    			case RewardConfType::SILVER:
    				$beforeSilver = $user->getSilver();
    				break;
    			case RewardConfType::ITEM_MULTI:
    				$beforeItemNum = $bag->getItemNumByTemplateID($arr[1]);
    				break;
    			default: echo "invalid type:".$arr[0];
    		}
    	}
    	
    	self::tearDown();
    	 
    	$travelShop->getReward($id);
    	foreach ($reward as $arr)
    	{
    		switch ($arr[0])
    		{
    			case RewardConfType::SILVER:
    				$afterSilver = $user->getSilver();
    				$this->assertEquals($beforeSilver + $arr[2], $afterSilver);
    				break;
    			case RewardConfType::ITEM_MULTI:
    				$afterItemNum = $bag->getItemNumByTemplateID($arr[1]);
    				$this->assertEquals($beforeItemNum + $arr[2], $afterItemNum);
    				break;
    			default: echo "invalid type:".$arr[0];
    		}
    	}
    	
    	$ret = $travelShop->getInfo();
    	$this->assertEquals($beforeSum, $ret[TravelShopDef::FIELD_SUM]);
    	$this->assertEquals($beforeScore, $ret[TravelShopDef::FIELD_SCORE]);
		$this->assertEquals($beforeFinishTime, $ret[TravelShopDef::FIELD_FINISH_TIME]);
    	$this->assertEquals($beforePayback, $ret[TravelShopDef::PAYBACK]);
    	$this->assertEquals(array_merge($beforeReward, array($id)), $ret[TravelShopDef::REWARD]);
    	$this->assertEquals($beforeTopup, $ret['topup']);
    }
    
    public function test_reward()
    {
    	//完成进度
    	$goodsId = 2;
    	$goodsConf = TravelShopLogic::getGoodsConf($goodsId);
    	$limit = $goodsConf[TravelShopDef::LIMIT];
    	$this->buy($goodsId, $limit);
    	
    	//完成充值
    	$id = 1;
    	$console = new Console();
    	list($pay, $back) = TravelShopLogic::getPaybackConf($id);
    	$console->addGoldOrder($pay);
    	
    	//清除旧timer
    	$rewardTime = TravelShopLogic::getRewardTime();
    	$arrTask = EnTimer::getArrTaskByName(TravelShopDef::TASK_NAME, array(TimerStatus::RETRY, TimerStatus::UNDO), $rewardTime);
    	foreach ($arrTask as $task)
    	{
    		$tid = $task['tid'];
    		$set = array('status' => TimerStatus::FINISH);
    		TimerDAO::updateTask($tid, $set);
    	}
    	
    	//改活动结束时间
    	$actConf = ActivityConfDao::getCurConfByName(ActivityName::TRAVELSHOP, ActivityDef::$ARR_CONF_FIELD);
    	$actConf['end_time'] = Util::getTime() + 3600 - TravelShopDef::DELAY + 1;
    	ActivityConfDao::insertOrUpdate($actConf);
    	ActivityConfLogic::updateMem();
    	
    	//触发新timer
    	$travelShop = new TravelShop();
    	$travelShop->getInfo();
    	
    	//检查新timer
    	$rewardTime = TravelShopLogic::getRewardTime();
    	$arrTask = EnTimer::getArrTaskByName(TravelShopDef::TASK_NAME, array(TimerStatus::RETRY, TimerStatus::UNDO), $rewardTime);
    	$this->assertEquals(1, count($arrTask));
    	
    	//
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */