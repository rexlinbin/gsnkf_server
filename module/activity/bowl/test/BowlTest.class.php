<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BowlTest.class.php 156210 2015-01-30 09:34:52Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/bowl/test/BowlTest.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-01-30 09:34:52 +0000 (Fri, 30 Jan 2015) $
 * @version $Revision: 156210 $
 * @brief 
 *  
 **/
class BowlTest extends PHPUnit_Framework_TestCase
{
	private static $uid;
	private static $pid;
	private static $tblName = 't_bowl';
	
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
	
	public function testGetInfo ()
	{
 		$myBowl = BowlObj::getInstance(self::$uid);
 		$bowlInfo = $myBowl->getBowlInfo();
 		
 		$conf = EnActivity::getConfByName(ActivityName::BOWL);
 		
 		$sqlBowlInfo = BowlDao::select(array( array(BowlDef::TBL_FIELD_UID, '=',self::$uid) ), BowlDef::$BOWL_ALL_FIELDS);
 		
 		$recharge = BowlLogic::getChargeDuringBowl(self::$uid);
 		
 		$ret = array();
 		foreach ( BowlType::$ALL_TYPE as $type )
 		{
 			$aTypeInfo = array();
 			
 			$need = intval($conf['data'][$type][BowlDef::BOWL_BUY_NEED]);
 			
 			if ( $recharge < $need )
 			{
 				$aTypeInfo['state'] = BowlState::CAN_NOT_BUY;
 				$aTypeInfo['reward'] = array();
 			}
 			else 
 			{
 				$aTypeInfo['state'] = $this->hasBuy($type) ? BowlState::ALREADY_BUY : BowlState::CAN_BUY;
 				$aTypeInfo['reward'] = $this->getInfo($type);
 			}
 			
 			$ret[$type] = $aTypeInfo;
 		}
 		
 		$this->assertEquals($bowlInfo, $ret);
	}
	
	public function testBowl()
	{
		$sqlBowlInfo = BowlLogic::getBowlInfo(self::$uid);
		
		$canNotBuy = 0;
		foreach ($sqlBowlInfo['type'] as $type => $typeInfo)
		{
			if ( BowlState::CAN_NOT_BUY == $typeInfo['state'] )
			{
				$canNotBuy = $type;
				break;
			}
		}
		
		if ( $canNotBuy != 0 )
		{
			try {
				BowlLogic::buy(self::$uid, $canNotBuy);
				$this->assertTrue(0);
			}
			catch (Exception $e)
			{
				$this->assertEquals('fake', $e->getMessage());
			}
		}
		
		$conf = EnActivity::getConfByName(ActivityName::BOWL);
		$maxNeed = $conf['data'][3][BowlDef::BOWL_BUY_NEED];
		
		$this->addGoldOrder($maxNeed);
		
		$sqlBowlInfo = BowlLogic::getBowlInfo(self::$uid);
		
		$canBuy = 0;
		$hasBuy = 0;
		foreach ($sqlBowlInfo['type'] as $type => $typeInfo)
		{
			if ( BowlState::CAN_BUY == $typeInfo['state'] )
			{
				$canBuy = $type;
				break;
			}
		}
		
		$userObj = EnUser::getUserObj(self::$uid);
		
		if ($canBuy != 0)
		{
			try {
				$gold = $userObj->getGold();
				$userObj->subGold($gold, 0);
				$userObj->update();
				
				BowlLogic::buy(self::$uid, $canBuy);
				
				$this->assertTrue(0);
			}
			catch (Exception $e)
			{
				$this->assertEquals('fake', $e->getMessage());
			}
			
			$buyCost = $conf['data'][$canBuy][BowlDef::BOWL_BUY_COST];
			var_dump($buyCost);
			$ret = $userObj->addGold($buyCost, 0);
			$userObj->update();
			
			$userObj = EnUser::getUserObj(self::$uid);
			$gold = $userObj->getGold();
			var_dump($gold);
			
			BowlLogic::buy(self::$uid, $canBuy);
			$hasBuy = $canBuy;
		}
		
		if ( 0 == $hasBuy )
		{
			foreach ( $sqlBowlInfo['type'] as $type => $typeInfo )
			{
				if ( BowlState::ALREADY_BUY == $typeInfo['state'] )
				{
					$hasBuy = $type;
					break;
				}
			}
		}
		
		if ( $hasBuy != 0 )
		{
			try {
				BowlLogic::buy(self::$uid, $hasBuy);
				$this->assertTrue(0);
			}
			catch (Exception $e)
			{
				$this->assertEquals('fake', $e->getMessage());
			}
		}
		
	}
	
	public function hasBuy($type)
	{
		$sqlBowlInfo = BowlDao::select(array( array(BowlDef::TBL_FIELD_UID, '=',self::$uid) ), BowlDef::$BOWL_ALL_FIELDS);
		if ( isset( $sqlBowlInfo[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type] ) )
		{
			return TRUE;
		}
		return FALSE;
	}
	
	public function getInfo($type)
	{
		$sqlBowlInfo = BowlDao::select(array( array(BowlDef::TBL_FIELD_UID, '=',self::$uid) ), BowlDef::$BOWL_ALL_FIELDS);
		if ( !isset($sqlBowlInfo[BowlDef::TBL_FIELD_VA_EXTRA]['type'])
				|| !isset($sqlBowlInfo[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type]) 
				|| !isset($sqlBowlInfo[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type]['reward']) )
		{
			return array();
		}
		
		if ( !isset( $sqlBowlInfo[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type][BowlDef::TBL_VA_EXTRA_FIELD_BOWLTIME] ) )
		{
			return array();
		}
		
		if ( !isset($sqlBowlInfo[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type][BowlDef::TBL_VA_EXTRA_FIELD_REWARD]) )
		{
			return array();
		}
		
		$bowlTime = $sqlBowlInfo[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type][BowlDef::TBL_VA_EXTRA_FIELD_BOWLTIME];
		$rewardDay = Util::getDaysBetween($bowlTime) + 1;
		
		if ( $rewardDay > BowlConf::REWARD_DAY_NUM )
		{
			$rewardDay = BowlConf::REWARD_DAY_NUM;
		}
		
		$adjustInfo = array();
		for ( $i = 1; $i <= $rewardDay; $i++ )
		{
			if ( !in_array($i, $sqlBowlInfo[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type]['reward']) )
			{
				$adjustInfo[$i] = BowlDef::BOWL_REWARD_STATE_RECEIVED;
				continue;
			}
			if ( in_array($i, $sqlBowlInfo[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type]['reward']) )
			{
				$adjustInfo[$i] = BowlDef::BOWL_REWARD_STATE_HAVE;
				continue;
			}
		}
		
		for ( $i = $rewardDay + 1; $i <= BowlConf::REWARD_DAY_NUM; $i++ )
		{
			$adjustInfo[$i] = BowlDef::BOWL_REWARD_STATE_EMPTY;
		}
		
		return $adjustInfo;
	}
	
	public function addGoldOrder($addGold,$date=0)
	{
		$addGold = intval($addGold);
		$uid = RPCContext::getInstance()->getUid();
		$orderId = 'AAAA_00_' . strftime("%Y%m%d%H%M%S") . rand(10000, 99999);
		$user = new User();
		$user->addGold4BBpay($uid, $orderId, $addGold);
		if(!empty($date))
		{
			$orderData = User4BBpayDao::getByOrderId($orderId, array('mtime','order_id'));
			$date = intval( $date );
			$dateDetail = strval( $date * 1000000 );
			$timeStamp = strtotime( $dateDetail );
			if($timeStamp < Util::getTime() && (Util::isSameDay($timeStamp) == FALSE))
			{
				$orderData['mtime'] = $timeStamp;
				$data = new CData();
				$data->update(User4BBpayDao::tblBBpay)
				->set($orderData)
				->where(array('order_id','LIKE',$orderId))
				->query();
			}
		}
		$orderData = User4BBpayDao::getByOrderId($orderId, array('mtime','order_id'));
		return $orderData;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */