<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UnionTest.php 183285 2015-07-09 08:36:34Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/union/test/UnionTest.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-07-09 08:36:34 +0000 (Thu, 09 Jul 2015) $
 * @version $Revision: 183285 $
 * @brief 
 *  
 **/
class UnionTest extends PHPUnit_Framework_TestCase
{
	protected static $uid = 22828;

	public static function setUpBeforeClass()
	{
		self::createUser();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		EnSwitch::getSwitchObj(self::$uid)->addNewSwitch(SwitchDef::UNION);
		EnSwitch::getSwitchObj(self::$uid)->save();
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

	public function test_getInfoByLogin()
	{
		Logger::debug('======%s======', __METHOD__);

		$union = new Union();
		$ret = $union->getInfoByLogin();
		$this->assertEquals(array(), $ret['union']);
		$this->assertEquals(array(), $ret['attr']);
		$this->assertEquals(array(), $ret['func']);
	}

	public function test_getInfo()
	{
		Logger::debug('======%s======', __METHOD__);

		$union = new Union();
		$ret = $union->getInfo();
		$this->assertEquals(self::$uid, $ret[UnionDef::FIELD_UID]);
		$this->assertEquals(array(), $ret[UnionDef::FIELD_VA_FATE]);
		$this->assertEquals(array(), $ret[UnionDef::FIELD_VA_LOYAL]);
	}
	
	public function test_fill()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$hconf = btstore_get()->HEROES;
		$iconf = btstore_get()->ITEMS;
		$fconf = btstore_get()->UNION_FATE;
		$lconf = btstore_get()->UNION_LOYAL;
		$econf = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		$user = EnUser::getUserObj(self::$uid);
		$bag = BagManager::getInstance()->getBag(self::$uid);
		
		//缘分堂镶嵌一个武将
		$id = 2;
		$needLevel = $fconf[$id][UnionDef::NEED_LEVEL];
		$user->addExp($econf[$needLevel]);
		$user->update();
		$arrNeed = $fconf[$id][UnionDef::NEED_ARR];
		$arrId = array();
		$htid = 0;
		$itemTid = 0;
		foreach ($arrNeed as $tid)
		{
			if (isset($hconf[$tid])) 
			{
				$htid = $tid;
				$arrId[$tid] = $user->getHeroManager()->addNewHero($tid);
			}
			else
			{
				$itemTid = $tid;
				$ret = ItemManager::getInstance()->addItem($tid);
				$arrId[$tid] = $ret[0];
				$bag->addItem($ret[0]);
			}
		}
		$qualification = Creature::getHeroConf($htid, CreatureAttr::QUALIFICATION);
		$itemTplId = $fconf[$id][UnionDef::ITEM_TPLID];
		$goldNum = $fconf[$id][UnionDef::GOLD_NUM];
		$needNum = $fconf[$id][UnionDef::ITEM_NUM_ARR][$qualification];
		$user->addGold($goldNum*$needNum, StatisticsDef::ST_FUNCKEY_UNION_FILL_COST);
		$user->update();
		$bag->addItemByTemplateID($itemTplId, 1);
		$bag->update();
		$goldBefore = $user->getGold();
		$union = new Union();
		$ret = $union->fill($id, $arrId[$htid], 1, 0);
		$this->assertEquals('ok', $ret);
		$goldAfter = $user->getGold();
		$this->assertEquals($goldAfter + $goldNum*($needNum-1), $goldBefore);
		$itemNum = $bag->getItemNumByTemplateID($itemTplId);
		$this->assertEquals(0, $itemNum);
		$info = $union->getInfo();
		$this->assertEquals(array($htid), $info[UnionDef::FIELD_VA_FATE][UnionDef::LISTS][$id]);
		$this->assertEquals(0, $user->getHeroManager()->getHeroNumByHtid($htid));
		
		//缘分堂镶嵌一个物品
		$itemType = ItemManager::getInstance()->getItemType($itemTid);
		if (ItemDef::ITEM_TYPE_TREASURE == $itemType) 
		{
			$qualification = ItemAttr::getItemAttr($tid, TreasureDef::ITEM_ATTR_NAME_TREASURE_SCORE_BASE);
		}
		if (ItemDef::ITEM_TYPE_GODWEAPON == $itemType) 
		{
			$qualification = ItemAttr::getItemAttr($tid, GodWeaponDef::ITEM_ATTR_NAME_SCORE);
		}
		$needNum = $fconf[$id][UnionDef::ITEM_NUM_ARR][$qualification];
		$user->addGold($goldNum*$needNum, StatisticsDef::ST_FUNCKEY_UNION_FILL_COST);
		$user->update();
		$bag->addItemByTemplateID($itemTplId, 1);
		$bag->update();
		$goldBefore = $user->getGold();
		$ret = $union->fill($id, $arrId[$itemTid], 0, 0);
		$this->assertEquals('ok', $ret);
		$goldAfter = $user->getGold();
		$this->assertEquals($goldAfter + $goldNum*($needNum-1), $goldBefore);
		$itemNum = $bag->getItemNumByTemplateID($itemTplId);
		$this->assertEquals(0, $itemNum);
		$info = $union->getInfo();
		$this->assertEquals(array($htid, $itemTid), $info[UnionDef::FIELD_VA_FATE][UnionDef::LISTS][$id]);
		$this->assertEquals(0, $bag->getItemNumByTemplateID($itemTid));
		$loginInfo = $union->getInfoByLogin();
		$this->assertEquals(array($fconf[$id][UnionDef::UNION_ID]), $loginInfo['union']);
		$addAttr = array();
		$addAttr[] = $hconf[$htid][CreatureAttr::FATE_ATTR];
		$addAttr[] = $iconf[$itemTid][UnionDef::FATE_ATTR];
		$addInfo = Util::arrayAdd2V($addAttr);
		$this->assertEquals($addInfo, $loginInfo['attr']);
		
		//激活忠义堂的一个id
		$id2 = 1;
		$needLevel = $lconf[$id2][UnionDef::NEED_LEVEL];
		$user->addExp($econf[$needLevel]);
		$user->update();
		$arrNeed = $lconf[$id2][UnionDef::NEED_ARR];
		$arrId = array();
		$sumGold = 0;
		foreach ($arrNeed as $tid)
		{
			$this->assertTrue(isset($hconf[$tid]));
			$arrId[$tid] = $user->getHeroManager()->addNewHero($tid);
			$qualification = Creature::getHeroConf($tid, CreatureAttr::QUALIFICATION);
			$needNum = $lconf[$id2][UnionDef::ITEM_NUM_ARR][$qualification];
			$goldNum = $lconf[$id2][UnionDef::GOLD_NUM];
			$sumGold += $goldNum*$needNum;
		}
		$user->addGold($sumGold, StatisticsDef::ST_FUNCKEY_UNION_FILL_COST);
		$user->update();
		$goldBefore = $user->getGold();
		$union = new Union();
		foreach ($arrId as $tid => $aimId)
		{
			$ret = $union->fill($id2, $aimId, 1, 1);
			$this->assertEquals('ok', $ret);
		}
		$goldAfter = $user->getGold();
		$this->assertEquals($goldAfter + $sumGold, $goldBefore);
		foreach ($arrId as $tid => $aimId)
		{
			$this->assertEquals(0, $user->getHeroManager()->getHeroNumByHtid($tid));
			$addAttr[] = $hconf[$tid][CreatureAttr::FATE_ATTR];
		}
		$loginInfo = $union->getInfoByLogin();
		$this->assertEquals(array($fconf[$id][UnionDef::UNION_ID]), $loginInfo['union']);
		$this->assertEquals(array($lconf[$id2][UnionDef::TYPE] => array($id2)), $loginInfo['func']);
		$addInfo = Util::arrayAdd2V($addAttr);
		$this->assertEquals($addInfo, $loginInfo['attr']);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */