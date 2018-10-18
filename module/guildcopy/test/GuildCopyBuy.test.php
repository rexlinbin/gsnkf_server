<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildCopyBuy.test.php 188883 2015-08-04 13:06:03Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildcopy/test/GuildCopyBuy.test.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-08-04 13:06:03 +0000 (Tue, 04 Aug 2015) $
 * @version $Revision: 188883 $
 * @brief 
 *  
 **/
class GuildCopyBuyTest extends PHPUnit_Framework_TestCase
{
	private static $uid = 0;
	private static $guildId = 0;
	private static $arrMemberUid = array();
	
	public static function setUpBeforeClass()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval('mbg' . $pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		if($ret['ret'] != 'ok')
		{
			echo "create user failed\n";
			exit();
		}
		self::$uid = $ret['uid'];
		
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		$console = new Console();
		$console->gold(10000);
		
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$userObj = EnUser::getUserObj(self::$uid);
		$userObj->addExp($expTable[20]);  // 加经验加少些，否则玩家等级太高，测不了后面 “达不到购买等级的条件“
		$userObj->update();
		
		$ret = GuildLogic::createGuild(self::$uid, 'x'.$pid, TRUE, '', '', '');
		if ($ret['ret'] != 'ok') 
		{
			echo "create guild failed\n";
			exit();
		}
		self::$guildId = $ret['info']['guild_id'];
		
		//$console->setGuildLevel(1, 15);
		
		for ($i = 0; $i < 10; ++$i)
		{
			$pid = IdGenerator::nextId('uid');
			$uname = strval('mbg' . $pid);
			$ret = UserLogic::createUser($pid, 1, $uname);
			if($ret['ret'] != 'ok')
			{
				echo "create user failed\n";
				exit();
			}
			self::$arrMemberUid[] = $ret['uid'];
			usleep(10);
		}
		
		foreach (self::$arrMemberUid as $aUid)
		{
			RPCContext::getInstance()->resetSession();
			RPCContext::getInstance()->setSession('global.uid', $aUid);
			$ret = GuildLogic::applyGuild($aUid, self::$guildId);
			if ($ret != 'ok')
			{
				echo "apply join guild failed\n";
				exit();
			}
			
			$console = new Console();
			$console->gold(10000);
		}
		
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		foreach (self::$arrMemberUid as $aUid)
		{
			$ret = GuildLogic::agreeApply(self::$uid, $aUid);
			if ($ret != 'ok')
			{
				echo "agree join guild failed\n";
				exit();
			}
		}
		
		var_dump(self::$uid);
		var_dump(self::$guildId);
		var_dump(self::$arrMemberUid);
		
		self::updateJoinGuildTime(self::$arrMemberUid, self::$guildId, Util::getTime() - 1);
	}
	protected function setUp()
	{
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, self::$uid);
	}

	protected function tearDown()
	{
		parent::tearDown ();
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->unsetSession('global.uid');
	}

	public static function updateJoinGuildTime($arrMember, $guildId, $joinTime)
	{
		$arrCond = array
		(
				array('uid', 'IN', $arrMember ),
				array('guild_id', '=', $guildId),
				array('record_type', '=', GuildRecordType::JOIN_GUILD),
		);
	
		$data = new CData();
		$data->select(array('grid', 'uid'))->from(GuildDef::TABLE_GUILD_RECORD);
		foreach ($arrCond as $aCond)
		{
			$data->where($aCond);
		}
		$arrInfo = $data->query();
		$arrInfo = Util::arrayIndex($arrInfo, 'uid');
	
		foreach ($arrMember as $aMemberUid)
		{
			$data = new CData();
			$data->update(GuildDef::TABLE_GUILD_RECORD)->set(array('record_time' => $joinTime))->where(array('grid', '=', intval($arrInfo[$aMemberUid]['grid'])));
			$data->query();
		}
	}
	
	public function test_buy()
	{
		$goodsIdArr = array();
		$console = new Console();
		//先找到配置表中 第一次 需要根据玩家来确定兑换次数的 商品id
		$retArr = btstore_get()->GUILD_COPY_GOODS;
		foreach ($retArr as $key => $value)
		{
			$goodsIdArr[] = $key;
		}
		foreach ($goodsIdArr as $value)
		{
			if ( empty($retArr[$value][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM]) )
			{
				if ( !empty($retArr[$value][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]) )
				{
					$testGoodsId = $value;
					break;
				}
			}
		}

		//根据选出来的商品id 来做测试 根据玩家等级确定兑换次数的情况
		$ret = btstore_get()->GUILD_COPY_GOODS[$testGoodsId]->toArray();
		$confZg = $ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA]['zg'];
		$guildMenber = GuildMemberObj::getInstance(self::$uid);
		$guildshop = new GuildCopyShop(self::$uid);
		$buyNum = 1;
		// 1、玩家等级达不到兑换条件的情况
		try
		{	
			// 这时候玩家的等级20级, 还没达到配置要求的最低等级条件
			$needZg = $confZg * $buyNum;
			$guildMenber->addZgNum($needZg);
			$guildMenber->update();
			$buy = $guildshop->buy($testGoodsId, $buyNum);
			var_dump($buy);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake', $e->getMessage() );
		}
		
		
		// 2、玩家等级达到兑换条件的情况

		// (1)玩家等级达到兑换要求的最小等级
		reset($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]);
		list($userLevel, $limitNum1) = each($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]);
		$console->level($userLevel);
		$needZg = $confZg * $limitNum1;
		$guildMenber->addZgNum($needZg);
		$guildMenber->update();
		$this->assertEquals( 'ok', $guildshop->buy($testGoodsId, $limitNum1) );


		// (2)玩家等级在配置的兑换要求的最小等级和最大等级之间, 包括超过兑换次数的情况,但是这只有在配置的等级数不小于1的情况才有意义
		list($userLevel, $limitNum2) = each($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]);
		$console->level($userLevel);
		$needZg = $confZg * ($limitNum2 - $limitNum1);
		$guildMenber->addZgNum($needZg);
		$guildMenber->update();
		$this->assertEquals('ok', $guildshop->buy($testGoodsId, $limitNum2 - $limitNum1)); //兑换次数刚好达到上限
		$info = $guildshop->getShopInfo();
// 		var_dump($info[$testGoodsId]['num']);   //对比实际购买商品数量与配置表的限制数量
// 		var_dump($limitNum2); 
		try		//超过兑换次数
		{
			$needZg = $confZg * ($buyNum);
			$guildMenber->addZgNum($needZg);
			$guildMenber->update();
			$guildshop->buy($testGoodsId, $buyNum);

		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake', $e->getMessage() );
		}

		// 大于最大等级时的兑换情况
// 		reset($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]);
// 		list($userLevel, $limitNum3) = end($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]);
// 		var_dump($userLevel);
// 		$console->level($userLevel + 5);
// 		var_dump($limitNum3 - $limitNum2 - $limitNum1);
		//  		$this->assertEquals('ok', $compete->buy($testGoodsId, $limitNum3 - $limitNum2 - $limitNum1)); //兑换次数刚好达到上限


		// 		// 3、 玩家兑换次数超出最大等级限制次数的情况
		// 		try
		// 		{
		// 			$compete->buy($testGoodsId, $buyNum);
		// 		}
		// 		catch ( Exception $e )
		// 		{
		// 			$this->assertEquals( 'fake', $e->getMessage() );
		// 		}

		// 4、检查根据玩家消费等级确定兑换次数第二天后是否会重置

		//这个本来想自己测的，但是没有直接的接口可以调用，自己要测又得自己扩充 console类或者Arena类的接口，为了一个小功能改动太大不好，所以这部分给QA测

	}

	protected static function createUser()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval($pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		$uid = $ret['uid'];

		EnSwitch::getSwitchObj($uid)->addNewSwitch(SwitchDef::GUILD); //开启比武商店
		EnSwitch::getSwitchObj($uid)->save();
		return $uid;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */