<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ArenaBuy.test.php 188208 2015-07-31 13:16:34Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/arena/test/ArenaBuy.test.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-07-31 13:16:34 +0000 (Fri, 31 Jul 2015) $
 * @version $Revision: 188208 $
 * @brief 
 *  
 **/
class ArenaBuyTest extends PHPUnit_Framework_TestCase
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
		self::clearArena();
	}


	public function test_buy()
	{
		$goodsIdArr = array();
		$console = new Console();
		//先找到配置表中 第一次 需要根据玩家来确定兑换次数的 商品id
		$retArr = btstore_get()->ARENA_GOODS;
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
		$arena = new Arena(self::$uid);
		$buyNum = 1;
		$ret = btstore_get()->ARENA_GOODS[$testGoodsId]->toArray();
		// 1、玩家等级达不到兑换条件的情况
		try
		{
			$userLevel = 1;
			$console->level($userLevel);
			$arena->buy($testGoodsId, $buyNum);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake', $e->getMessage() );
		}

		// 2、玩家等级达到兑换条件的情况
		$console->prestige($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_PRESTIGE]*100);
		
		// (1)玩家等级达到兑换要求的最小等级
		list($userLevel, $limitNum1) = each($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]);
		$console->level($userLevel);
		$this->assertEquals( 'ok', $arena->buy($testGoodsId, $limitNum1) );
		
		// (2)玩家等级在配置的兑换要求的最小等级和最大等级之间, 并且坚持超过兑换次数的情况
		list($userLevel, $limitNum2) = each($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]);
		$console->level($userLevel);
		try 
		{
			$this->assertEquals( 'ok', $arena->buy($testGoodsId, $limitNum2 - $limitNum1) );
			$arena->buy($testGoodsId, $buyNum);
		}
		catch ( Exception $e )
		{
			$this->assertEquals('fake', $e->getMessage());
		}
		
		// (3)玩家等级超过配置的兑换要求的最大等级, 不过对于这种情况的测试,只有在配置的等级情况大于等于2种才有意义
		end($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]);
	    $userLevel = key($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]) + 1;
	    $console->level($userLevel);
	    $limitNum3 = current($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]);
		$this->assertEquals( 'ok', $arena->buy($testGoodsId, $limitNum3 - $limitNum2 - $limitNum1 - $buyNum) );
			

		// 3、检查根据玩家消费等级确定兑换次数第二天后是否会重置

		//这个本来想自己测的，但是没有直接的接口可以调用，自己要测又得自己扩充 console类或者Arena类的接口，为了一个小功能改动太大不好，所以这部分给QA测

	}

	protected static function createUser()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval($pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		$uid = $ret['uid'];

		EnSwitch::getSwitchObj($uid)->addNewSwitch(SwitchDef::ARENA);
		EnSwitch::getSwitchObj($uid)->save();
		return $uid;
	}



	protected static function clearArena()
	{
		//$dbHost = '192.168.1.37';
		if(empty(self::$dbHost))
		{
			//这个取得是dataproxy的ip，在测试环境一般dataproxy和mysql在同一台机器上
			$proxy = new PHPProxy ( 'data' );
			$group = RPCContext::getInstance ()->getFramework ()->getGroup ();
			$arrModule = $proxy->getModuleInfo ( 'data', $group );
			Logger::debug ( "module:data get info:%s", $arrModule );
			self::$dbHost = $arrModule ['host'];
		}
		$dbHost = self::$dbHost;

		$dbName = 'piratetm';
		$ret = system("mysql -upirate -padmin -h $dbHost -D$dbName -e 'delete from t_arena'" );
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */