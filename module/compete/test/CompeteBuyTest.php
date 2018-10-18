<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CompeteBuyTest.php 188880 2015-08-04 13:04:48Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/compete/test/CompeteBuyTest.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-08-04 13:04:48 +0000 (Tue, 04 Aug 2015) $
 * @version $Revision: 188880 $
 * @brief 
 *  
 **/
class CompeteBuyTest extends PHPUnit_Framework_TestCase
{
	protected static $uid = 0;
	protected static $dbHost = '';

	public static function setUpBeforeClass()
	{
		self::$uid = self::createUser();
	}

	protected function setUp()
	{
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, self::$uid);
	}

	protected function tearDown()
	{
	}

	public function test_buy()
	{
		$goodsIdArr = array();
		$console = new Console();
		//先找到配置表中 第一次 需要根据玩家来确定兑换次数的 商品id
		$retArr = btstore_get()->COMPETE_GOODS;
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
		$compete = new Compete(self::$uid);
		$buyNum = 1;
		$ret = btstore_get()->COMPETE_GOODS[$testGoodsId]->toArray();
		CompeteLogic::init(self::$uid);  //初始化数据库信息
		// 1、玩家等级达不到兑换条件的情况
		try
		{
			$userLevel = 1;
			$honor = 0;
			$console->level($userLevel);
			CompeteLogic::addHonor(self::$uid, $honor);
			$compete->buy($testGoodsId, $buyNum);
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
		$honor = 10000;
		
		if ('ok' == CompeteLogic::addHonor(self::$uid, $honor) )
		{
			$this->assertEquals( 'ok', $compete->buy($testGoodsId, $limitNum1) );
		}

		// (2)玩家等级在配置的兑换要求的最小等级和最大等级之间, 包括超过兑换次数的情况,但是这只有在配置的等级数不小于1的情况才有意义
		list($userLevel, $limitNum2) = each($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]);
		$console->level($userLevel);
		$this->assertEquals('ok', $compete->buy($testGoodsId, $limitNum2 - $limitNum1)); //兑换次数刚好达到上限
		try		//超过兑换次数
		{
			$compete->buy($testGoodsId, $buyNum);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake', $e->getMessage() );
		}
		
		// 大于最大等级时的兑换情况
		reset($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]);
		list($userLevel, $limitNum3) = end($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]);
		var_dump($userLevel);
		$console->level($userLevel + 5); 
		var_dump($limitNum3 - $limitNum2 - $limitNum1);
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

		EnSwitch::getSwitchObj($uid)->addNewSwitch(SwitchDef::ROB); //开启比武商店
		EnSwitch::getSwitchObj($uid)->save();
		return $uid;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */