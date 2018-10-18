<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RewardTest.php 251037 2016-07-11 10:37:27Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/test/RewardTest.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2016-07-11 10:37:27 +0000 (Mon, 11 Jul 2016) $
 * @version $Revision: 251037 $
 * @brief 
 *  
 **/

class RewardTest extends PHPUnit_Framework_TestCase
{

	public static $uid = 0;
	
	public static function setUpBeforeClass()
	{
		$data = new CData();
		$ret = $data->select(array('uid'))->from('t_user')
				->where(array('status', '!=', UserDef::STATUS_DELETED))
				->orderBy('last_login_time', false)->limit(0, 1)->query();
		if(empty($ret))
		{
			echo "no user for test\n";
			exit();
		}
		self::$uid = $ret[0]['uid'];
		Logger::debug('the uid is: %s', self::$uid );
		RPCContext::getInstance ()->setSession ( 'global.uid', self::$uid );
	}


	protected function setUp()
	{
		
	}

	protected function tearDown()
	{
	}
	
	public function testSendReward()
	{
		Logger::debug('======%s======', __METHOD__ );
		$arrItemTpl = array(
						self::getItemTpl(ItemDef::ITEM_TYPE_DIRECT) => 2,
						self::getItemTpl(ItemDef::ITEM_TYPE_ARM) => 2,
						);
		$arrHeroTpl = array(
				//两组是一样的
				self::getHeroTpl() => 2,
				self::getHeroTpl() => 2,
		);
		$reward = array(
				RewardType::GOLD => 10,
				RewardType::SILVER => 20,
				RewardType::SOUL => 30,
				RewardType::JEWEL => 35,
				RewardType::EXE =>5,
				RewardType::STAMINA => 6,
				RewardType::PRESTIGE => 40,
				RewardType::HORNOR => 55,
				RewardType::GUILD_CONTRI => 60,
				RewardType::GUILD_EXP => 70,
				RewardType::CROSS_HONOR => 88,
				RewardType::ARR_ITEM_ID => ItemManager::getInstance()->addItems($arrItemTpl),
				RewardType::ARR_ITEM_TPL => $arrItemTpl,
				RewardType::ARR_HERO_TPL => $arrHeroTpl,
				);
	
		$rid = RewardLogic::sendReward(self::$uid, RewardSource::DIVI_REMAIN, $reward);
		
		$this->assertTrue($rid > 0);
		
		$arrField = array(
				RewardDef::SQL_RID,
				RewardDef::SQL_SOURCE,
				RewardDef::SQL_SEND_TIME,
				RewardDef::SQL_RECV_TIME,
				RewardDef::SQL_DELETE_TIME,
				RewardDef::SQL_VA_REWARD
				);
		$ret = RewardDao::getByUidRid(self::$uid, $rid, $arrField);
		$this->assertTrue(!empty($ret));
		$this->assertEquals($reward, $ret[RewardDef::SQL_VA_REWARD]);
		
		$ret = RewardLogic::getRewardList(self::$uid, 0, -1);
		var_dump( $ret );
	}
	
	public function testReceiveReward()
	{
		Logger::debug('======%s======', __METHOD__ );
		$arrItemTpl = array(
						self::getItemTpl(ItemDef::ITEM_TYPE_DIRECT) => 2,
						self::getItemTpl(ItemDef::ITEM_TYPE_ARM) => 2,
						);
		$arrHeroTpl = array(
				//两组是一样的
				self::getHeroTpl() => 2,
				self::getHeroTpl() => 2,
		);
		
		$heroAddNum =0;
		foreach ( $arrHeroTpl as $htid => $num )
		{
			$heroAddNum += $num;
		}
		$reward = array(
				RewardType::GOLD => 10,
				RewardType::SILVER => 20,
				RewardType::SOUL => 30,
				RewardType::JEWEL => 35,
				RewardType::EXE => 5,
				RewardType::STAMINA => 6,
				RewardType::PRESTIGE => 40,
				RewardType::HORNOR => 50,
				RewardType::GUILD_CONTRI => 60,
				RewardType::GUILD_EXP => 70,
				RewardType::CROSS_HONOR => 88,
				RewardType::ARR_ITEM_ID => ItemManager::getInstance()->addItems($arrItemTpl),
				RewardType::ARR_ITEM_TPL => $arrItemTpl,
				RewardType::ARR_HERO_TPL => $arrHeroTpl,
				);
		$rid = RewardLogic::sendReward(self::$uid, RewardSource::DIVI_REMAIN, $reward);
		
		$userObj = EnUser::getUserObj(self::$uid);
		$bag = BagManager::getInstance ()->getBag();
		$bag->clearBag();
		
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid(self::$uid,true);
		$worldCompeteInnerObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, self::$uid);
		$goldBefore = $userObj->getGold();
		$silverBefore = $userObj->getSilver();
		$soulBefore = $userObj->getSoul();
		$exeBefore = $userObj->getCurExecution();
		$staminaBefore = $userObj->getStamina();
		$jewelBefore = $userObj->getJewel();
		$prestigeBefore = $userObj->getPrestige();
		$unusedHeroBefore = $userObj->getUnusedHeroNum();
		$crossHonorBefore = $worldCompeteInnerObj->getCrossHonor();
		
		RewardLogic::receiveByArrRid(self::$uid, array($rid) );
		
		$this->assertEquals($goldBefore + $reward[RewardType::GOLD], $userObj->getGold());
		$this->assertEquals($silverBefore + $reward[RewardType::SILVER], $userObj->getSilver());
		$this->assertEquals($soulBefore + $reward[RewardType::SOUL], $userObj->getSoul());
		$this->assertEquals($jewelBefore + $reward[RewardType::JEWEL] , $userObj->getJewel());
		$this->assertEquals($exeBefore + $reward[RewardType::EXE] , $userObj->getCurExecution());
		$this->assertEquals($staminaBefore + $reward[RewardType::STAMINA] , $userObj->getStamina());
		$this->assertEquals($prestigeBefore + $reward[RewardType::PRESTIGE], $userObj->getPrestige());
		$this->assertEquals($unusedHeroBefore + $heroAddNum , $userObj->getUnusedHeroNum());
		$this->assertEquals($crossHonorBefore + $reward[RewardType::CROSS_HONOR] , $worldCompeteInnerObj->getCrossHonor());
		
		$ret = $bag->bagInfo();
		$allItem = $ret[BagDef::BAG_ARM] + $ret[BagDef::BAG_PROPS];
/* 		$arrItemTplInBag = array();
		foreach($allItem as $item)
		{
			if(isset($arrItemTplInBag[$item[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]]))
			{
				$arrItemTplInBag[$item[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]] += $item[ItemDef::ITEM_SQL_ITEM_NUM];
			}
			else
			{
				$arrItemTplInBag[$item[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]] = $item[ItemDef::ITEM_SQL_ITEM_NUM];
			}
		}
		
		$arrItemTplTrue = $arrItemTpl;
		foreach($arrItemTpl as $tplId => $num)
		{
			$arrItemTplTrue[$tplId] = 2*$num;
		}
		
		$this->assertEquals($arrItemTplTrue, $arrItemTplInBag);		 */
	}
	
 	public function testGetRewardList()
	{
		Logger::debug('======%s======', __METHOD__ );
		$reward = array(
				RewardType::GOLD => 10,
				RewardType::SILVER => 20,
				RewardType::SOUL => 30,
				RewardType::HORNOR => 50,
				RewardType::GUILD_CONTRI => 60,
				RewardType::GUILD_EXP=> 70,
				RewardType::CROSS_HONOR => 88,
		);
		self::clearData();
		$ridList = array();
		for($i = 0; $i < 10; $i++)
		{
			$rid = RewardLogic::sendReward(self::$uid, RewardSource::DIVI_REMAIN, $reward);
			$ridList[] = $rid;
		}
		
		$ret = RewardLogic::getRewardList(self::$uid, 1, 3);

		$this->assertEquals(array_slice($ridList, 1,3), Util::arrayExtract($ret, 'rid'));
	
		RewardLogic::receiveByArrRid(self::$uid, array($ridList[3]));
				
		$ret = RewardLogic::getRewardList(self::$uid, 1, 3);
		$this->assertEquals(array($ridList[1],$ridList[2],$ridList[4]), Util::arrayExtract($ret, 'rid'));
		
	}
	

	public function testWithPayback()
	{
		Logger::debug('======%s======', __METHOD__ );
		self::clearData();
		
		$userObj = EnUser::getUserObj(self::$uid);
		$bag = BagManager::getInstance ()->getBag();
		$bag->clearBag();
		
		$goldBefore = $userObj->getGold();
		$silverBefore = $userObj->getSilver();
		$soulBefore = $userObj->getSoul();
		
		//给用户发点奖励
		$arrItemTpl = array(
				self::getItemTpl(ItemDef::ITEM_TYPE_DIRECT) => 2,
				self::getItemTpl(ItemDef::ITEM_TYPE_ARM) => 2,
		);
		$reward = array(
				RewardType::GOLD => 10,
				RewardType::SILVER => 20,
				RewardType::SOUL => 30,
				RewardType::HORNOR => 40,
				RewardType::GUILD_CONTRI => 50,
				RewardType::GUILD_EXP => 60,
				RewardType::CROSS_HONOR => 88,
				RewardType::ARR_ITEM_ID => ItemManager::getInstance()->addItems($arrItemTpl),
				RewardType::ARR_ITEM_TPL => $arrItemTpl,
		);
	
		$rid_1 = RewardLogic::sendReward(self::$uid, RewardSource::DIVI_REMAIN, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		$rid_2 = RewardLogic::sendReward(self::$uid, RewardSource::DIVI_REMAIN, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		$rid_3 = RewardLogic::sendReward(self::$uid, RewardSource::DIVI_REMAIN, $reward);
				
		$packbackInfo = array(
				'silver' => 100,
				'gold' => 10,
				'soul' => 1000,
				'arrItemTpl'=> $arrItemTpl,
				);
		$ret = self::addPayback( Util::getTime()-100, Util::getTime()+100, $packbackInfo);
		$ret = self::addPayback( Util::getTime()-200, Util::getTime()+100, $packbackInfo);
		$ret = self::addPayback( Util::getTime()-200, Util::getTime()-100, $packbackInfo);
		
		$arrRet = PaybackLogic::getAvailablePayBack(self::$uid);
		$this->assertEquals(2, count($arrRet));
		$paybackId_1 = $arrRet[0][RewardDef::SQL_RID];
		$paybackId_2 = $arrRet[1][RewardDef::SQL_RID];
				
		RewardLogic::receiveByArrRid( self::$uid, array($rid_1, $rid_2, $paybackId_1 ) );
		
		//应该还剩下一个奖励一个补偿
		$arrRet = RewardLogic::getRewardList(self::$uid, 0, -1);
		$remain = Util::arrayIndex($arrRet, RewardDef::SQL_RID);
		$this->assertEquals(2, count($remain));
		$this->assertTrue( isset($remain[$rid_3]) );
		$this->assertTrue( isset($remain[$paybackId_2]) );
		
		
		$this->assertEquals($goldBefore + $reward[RewardType::GOLD]*2+$packbackInfo['gold'], $userObj->getGold());
		$this->assertEquals($silverBefore + $reward[RewardType::SILVER]*2+$packbackInfo['silver'], $userObj->getSilver());
		$this->assertEquals($soulBefore + $reward[RewardType::SOUL]*2+$packbackInfo['soul'], $userObj->getSoul());
				
		
		$ret = $bag->bagInfo();
		$allItem = $ret[BagDef::BAG_ARM] + $ret[BagDef::BAG_PROPS];
		$arrItemTplInBag = array();
/* 		foreach($allItem as $item)
		{
			if(isset($arrItemTplInBag[$item[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]]))
			{
				$arrItemTplInBag[$item[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]] += $item[ItemDef::ITEM_SQL_ITEM_NUM];
			}
			else
			{
				$arrItemTplInBag[$item[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]] = $item[ItemDef::ITEM_SQL_ITEM_NUM];
			}
		}
		
		$arrItemTplTrue = $arrItemTpl;
		foreach($arrItemTpl as $tplId => $num)
		{
			$arrItemTplTrue[$tplId] = 5*$num;
		}

		$this->assertEquals($arrItemTplTrue, $arrItemTplInBag); */
	}
	
	public static function test_sendConsole_0()
	{
		$console = new Console();
		$console->sendReward();
		$ret = RewardLogic::getRewardList(self::$uid, 0, 20);
		self::clearData();
	}
	
	
	public function test_sendRewardByPlatform_0()
	{
		Logger::debug('======%s======', __METHOD__ );
		self::clearData();
		$bag = BagManager::getInstance ()->getBag();
		$bag->clearBag();
		
		$arrItemTpl = array(
				self::getItemTpl(ItemDef::ITEM_TYPE_DIRECT) => 2,
				self::getItemTpl(ItemDef::ITEM_TYPE_ARM) => 2,
		);
		$arrHeroTpl = array(
				//两组是一样的
				self::getHeroTpl() => 2,
				self::getHeroTpl() => 2,
		);
		
		$heroAddNum =0;
		foreach ( $arrHeroTpl as $htid => $num )
		{
			$heroAddNum += $num;
		}
		$reward = array(
				RewardType::GOLD => 10,
				RewardType::SILVER => 20,
				RewardType::SOUL => 30,
				RewardType::JEWEL => 35,
				RewardType::EXE => 5,
				RewardType::STAMINA => 6,
				RewardType::PRESTIGE => 40,
				RewardType::HORNOR => 50,
				RewardType::GUILD_CONTRI => 60,
				RewardType::GUILD_EXP => 70,
				RewardType::CROSS_HONOR => 88,
				RewardType::ARR_ITEM_TPL => $arrItemTpl,
				RewardType::ARR_HERO_TPL => $arrHeroTpl,
		);
		
		$proxy = new ServerProxy();
		$proxy->sendSystemReward( self::$uid , $reward, 'platformReward', 'sendByTest 123  你好');

	}
	
	public function test_sendRewardByPlatform_limit()
	{
		EnUser::setExtraInfo(UserExtraDef::SYS_REWARD_INFO, array(), self::$uid);
		$limit = intval(btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_MAX_SYS_REWARD_EVERYDAY]);
		
		$title1 = "标题1";
		$msg1 = "消息1";
		$title2 = "标题2";
		$msg2 = "消息2";
		
		for ($i = 0; $i < $limit; ++$i)
		{
			$arrReward = array(RewardType::GOLD => 10);
			$reward = new Reward();
			$ret = $reward->sendSystemReward(self::$uid, $arrReward, $title1, $msg1);
			$this->assertEquals('ok', $ret);
		}
		
		$ret = $reward->sendSystemReward(self::$uid, $arrReward, $title1, $msg1);
		$this->assertEquals('limit', $ret);
		
		$ret = $reward->sendSystemReward(self::$uid, $arrReward, $title2, $msg2);
		$this->assertEquals('ok', $ret);
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
	
	public static function getHeroTpl()
	{
		$allHero =  btstore_get()->HEROES->toArray();
		foreach ( $allHero as $htid => $hero )
		{
			if ( !HeroUtil::isMasterHtid( $htid ) )
			{
				return $htid;
			}
		}
	}
	
	
	public static function addPayback($timeStart, $timeEnd, $arrInfo)
	{
		//检查补偿数据是否合法
	
		$arrField = array(
				PayBackDef::PAYBACK_SQL_TIME_START => $timeStart,
				PayBackDef::PAYBACK_SQL_TIME_END => $timeEnd,
				PayBackDef::PAYBACK_SQL_IS_OPEN =>1, 
				PayBackDef::PAYBACK_SQL_ARRY_INFO => $arrInfo );
		
		return PayBackDAO::insertIntoPayBackInfoTable($arrField);
	}
	
	public static function clearData()
	{
		$data = new CData();
		
		$data->update(RewardDef::SQL_TABLE)->set(array(RewardDef::SQL_DELETE_TIME=>Util::getTime()))
					->where(RewardDef::SQL_UID, '=', self::$uid)->query();
		
		$data->update(PayBackDef::PAYBACK_SQL_INFO_TABLE)->set(array(PayBackDef::PAYBACK_SQL_IS_OPEN =>0))
			->where(PayBackDef::PAYBACK_SQL_IS_OPEN , '=', 1)->query();
		
	}
	

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */