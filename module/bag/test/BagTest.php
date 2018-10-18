<?php
/************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BagTest.php 202669 2015-10-16 05:18:36Z BaoguoMeng $
 * 
 ***********************************************************************

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/bag/test/BagTest.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2015-10-16 05:18:36 +0000 (Fri, 16 Oct 2015) $
 * @version $Revision: 202669 $
 * @brief 
 *  
 **/

class BagTest extends PHPUnit_Framework_TestCase
{
	protected static $pid = 0;
	protected static $uid = 27680;

	public static function setUpBeforeClass()
	{
		list(self::$pid, self::$uid) = self::createUser();
		RPCContext::getInstance ()->setSession ( 'global.uid', self::$uid );	
	}

	protected function setUp()
	{	
	}

	protected function tearDown()
	{
	}
	
	public static function test_init()
	{
		Logger::debug('======%s======', __METHOD__);
		echo "test with uid:".self::$uid."\n";
		
		$bag = new Bag();		
		$ret = $bag->bagInfo();
		self::assertTrue(!empty($ret));

		$arrBagNeedInit = array(
			BagDef::BAG_ARM => BagConf::INIT_GRID_NUM_ARM,
			BagDef::BAG_PROPS => BagConf::INIT_GRID_NUM_PROPS,
			BagDef::BAG_TREAS => BagConf::INIT_GRID_NUM_TREAS,
			BagDef::BAG_ARM_FRAG => BagConf::INIT_GRID_NUM_ARM_FRAG,
			BagDef::BAG_DRESS => BagConf::INIT_GRID_NUM_DRESS,
			BagDef::BAG_FIGHT_SOUL => BagConf::INIT_GRID_NUM_FIGHT_SOUL,
		);
		
		$ret = self::getUserBagData();
		
		//初始背包中，只有初始物品所占据的格子
		$arrInitBagInfo = array();
		foreach($arrBagNeedInit as $bagName => $initNum)
		{
			$arrInitBagInfo[$bagName] = 0;
		}
		foreach(BagConf::$INIT_ARR_ITEM as $itemTplId => $num)
		{
			$itemType = ItemManager::getInstance()->getItemType($itemTplId);
			$stackNum = ItemManager::getInstance()->getItemStackable($itemTplId);
			$bagName = Bag::getBagNameByItemType($itemType);
			
			$arrInitBagInfo[$bagName] += ceil( $num / $stackNum );
		}
		
		Logger::debug('bagInfo:%s, initNum:%s', $ret, $arrInitBagInfo);
		foreach ( $arrBagNeedInit as $bagName => $initNum)
		{
			self::assertEquals($arrInitBagInfo[$bagName], count( $ret[$bagName]) );
		}
		
		foreach ( $arrBagNeedInit as $bagName => $initNum)
		{
			$itemType = 0;
			foreach( ItemDef::$MAP_ITEM_TYPE_BAG_NAME as $type => $name )
			{
				if($bagName == $name)
				{
					$itemType = $type;
					break;
				}
			}
			$itemTplId = self::getItemTpl($itemType);
			if( $itemTplId == 0 )
			{
				Logger::fatal('not found item for bag:%s', $bagName);
				continue;
			}
			$stackNum = ItemManager::getInstance()->getItemStackable($itemTplId);

			$arrItemId = array();
			for ($i = $arrInitBagInfo[$bagName]; $i < $initNum; $i++)
			{
				$ret = self::addItemByTpl($bag, $itemTplId, $stackNum);
				$arrItemId[] = current($ret);
				$bag->update();
				
				$ret = self::getUserBagData();
				self::assertEquals($i + 1, count($ret[$bagName]) );
			}
		}
	}
	
	/**
	 * 4.10修改bag，addItemByTemplateID时先尝试往已有的item上叠加
	 */
	public function testAddItemTpl()
	{
		Logger::debug('======%s======', __METHOD__);
		$bag = new Bag();
		$bag->clearBag();
		$bag->update();
		
		$ret = self::getUserBagData();
		$propBagBefore = $ret[BagDef::BAG_PROPS];
		
		$preItemId = IdGenerator::showId ( 'item_id' );
		
		Logger::debug('pre item_id:%d', $preItemId);
		
		$itemTplId = self::getStackItemTpl(ItemDef::ITEM_TYPE_DIRECT);
		$stackNum = ItemManager::getInstance()->getItemStackable($itemTplId);
		$this->assertTrue($stackNum > 3);//找一个叠加上限大于3的物品
		
		//先加，不要加满
		$addNum = $stackNum-3;
		$bag->addItemByTemplateID($itemTplId,$addNum);
		$bag->update();
		
		$ret = self::getUserBagData();
		$propBagAfter_1 = $ret[BagDef::BAG_PROPS];
		
		$ret = array_diff($propBagAfter_1, $propBagBefore);
		
		$firstItemId = $preItemId;
		$this->assertEquals(1, count($ret));
		$this->assertEquals($firstItemId, current($ret));
		
		$info = ItemStore::getItem($firstItemId);
		$this->assertEquals($addNum, $info['item_num']);
		
		$curItemId = IdGenerator::showId ( 'item_id' );
		
		$this->assertEquals($preItemId+1, $curItemId);
		
		//再加，不要加满
		$preItemId = $curItemId;
		$preNum = $addNum;
		$addNum = 2;
		$bag->addItemByTemplateID($itemTplId, $addNum);
		$bag->update();
		
		$ret = self::getUserBagData();
		$propBagAfter_2 = $ret[BagDef::BAG_PROPS];
		$this->assertEquals($propBagAfter_1, $propBagAfter_2); 
		
		$info = ItemStore::getItem($firstItemId);
		$this->assertEquals($preNum+$addNum, $info['item_num']);
		
		$curItemId = IdGenerator::showId ( 'item_id' );
		$this->assertEquals($preItemId, $curItemId);
		
		//再加，加满
		$preItemId = $curItemId;
		$preNum = $preNum+$addNum;
		$addNum = 2;
		$bag->addItemByTemplateID($itemTplId, $addNum);
		$bag->update();
		
		$ret = self::getUserBagData();
		$propBagAfter_3 = $ret[BagDef::BAG_PROPS];
		$ret = array_diff($propBagAfter_3, $propBagAfter_2);
		$this->assertEquals(1, count($ret));
		$this->assertEquals($preItemId, current($ret));
		
		$info = ItemStore::getItem($firstItemId);
		$this->assertEquals($stackNum, $info['item_num']);
		
		$info = ItemStore::getItem($preItemId);
		$this->assertEquals($preNum+$addNum - $stackNum, $info['item_num']);
		
		
		$curItemId = IdGenerator::showId ( 'item_id' );
		$this->assertEquals($preItemId+1, $curItemId);
		
		
	}
	
	public function testBagInfo()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = new Bag();
		$bag->clearBag();
		$bag->update();
		
		//情景1：数据是散列存放的:00110011
		//给装备背包加4个装备
		$bagName = BagDef::BAG_ARM;
		$initNum = BagConf::INIT_GRID_NUM_ARM;
		$itemType = ItemDef::ITEM_TYPE_ARM;
		$itemTplId = self::getItemTpl($itemType);
		if($itemTplId == 0)
		{
			Logger::fatal('not found item for bag:%s', $bagName);
		}
		$stackNum = ItemManager::getInstance()->getItemStackable($itemTplId);
		if ($stackNum != 1) 
		{
			Logger::fatal('arm is not stackable!');
		}
		
		$addNum = 4;
		$arrItemId = array();
		for ($i = 0; $i < $addNum; $i++)
		{
			$ret = self::addItemByTpl($bag, $itemTplId, $stackNum);
			$arrItemId[] = current($ret);
		}
		
		$ret = $bag->bagInfo();
		$armBag = $ret[$bagName];
		$armBag = Util::arrayIndex($armBag, ItemDef::ITEM_SQL_ITEM_ID);
		foreach ($arrItemId as $itemId)
		{
			self::assertTrue(key_exists($itemId, $armBag));
		}
		
		//删除头2个物品
		$bag->deleteItem($arrItemId[0]);
		$bag->deleteItem($arrItemId[1]);
		$bag->update();
		unset($arrItemId[0]);
		unset($arrItemId[1]);
		
		$bag = new Bag();
		$ret = $bag->bagInfo();
		$armBag = $ret[$bagName];
		$armBag = Util::arrayIndex($armBag, ItemDef::ITEM_SQL_ITEM_ID);
		foreach ($arrItemId as $itemId)
		{
			self::assertTrue(key_exists($itemId, $armBag));
		}
		
		//情景2：用户还没有使用背包就开了格子
		//开宝物背包的格子,开10个格子
		$openNum = 5;
		$bag = new Bag();
		$bag->openGridByGold($openNum, 3);
		RPCContext::getInstance()->resetSession();
		$bagName = BagDef::BAG_TREAS;
		$initNum = BagConf::INIT_GRID_NUM_TREAS;
		$itemType = ItemDef::ITEM_TYPE_TREASURE;
		$itemTplId = self::getItemTpl($itemType);
		if($itemTplId == 0)
		{
			Logger::fatal('not found item for bag:%s', $bagName);
		}
		$stackNum = ItemManager::getInstance()->getItemStackable($itemTplId);
		if ($stackNum != 1)
		{
			Logger::fatal('treas is not stackable!');
		}
		//先加$initNum个物品
		$bag = new Bag();
		$ret = $bag->addItemByTemplateID($itemTplId, $initNum);
		$bag->update();
		self::assertEquals(true, $ret);
		RPCContext::getInstance()->resetSession();
		//再加$openNum个物品
		$bag = new Bag();
		$ret = $bag->addItemByTemplateID($itemTplId, $openNum);
		self::assertEquals(true, $ret);
		$bag->update();
		
		//测试删除重复数据
		$ret = self::getUserBagData();
		$armBagBefore = $ret[BagDef::BAG_ARM];
		foreach ($armBagBefore as $gid => $itemId)
		{}
		$values = array(
				BagDef::SQL_ITEM_ID => $itemId,
				BagDef::SQL_UID => self::$uid,
				BagDef::SQL_GID => BagDef::GRID_START_ARM + BagConf::INIT_GRID_NUM_ARM - 1,
		);
		BagDAO::insertOrupdateBag($values);
		$bag = new Bag();
		$bag->bagInfo();
		$ret = self::getUserBagData();
		$armBagAfter = $ret[BagDef::BAG_ARM];
		$arrItemId = array();
		foreach ($armBagAfter as $gid => $itemId)
		{
			self::assertTrue(!in_array($itemId, $arrItemId));
		}
	}
	
	public function testAddItems()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = new Bag();
		$bag->clearBag();
		$bag->update();
		
		//step1-1
		//获取当前用户背包的数据
		$ret = self::getUserBagData();
		$armBagBefore = $ret[BagDef::BAG_ARM];
		
		//加N-1个不可叠加物品(如装备)
		$itemNum = BagConf::INIT_GRID_NUM_ARM - 1;
		$arrItemId = self::addItemByType($bag, ItemDef::ITEM_TYPE_ARM, $itemNum);
		$bag->update();
		
		//获取当前用户背包的数据
		$ret = self::getUserBagData();
		$armBagAfter = $ret[BagDef::BAG_ARM];
		
		//比较前后背包的变化
		$ret = array_diff($armBagAfter, $armBagBefore);
		$this->assertEquals($itemNum, count($ret));
		
		//step1-2
		//加N个不可叠加物品(如装备)
		$armBagBefore = $armBagAfter;
		$itemNum = BagConf::INIT_GRID_NUM_ARM;
		$arrItemId = self::addItemByType($bag, ItemDef::ITEM_TYPE_ARM, $itemNum);
		$bag->update();
		
		//获取当前用户背包的数据
		$ret = self::getUserBagData();
		$armBagAfter = $ret[BagDef::BAG_ARM];
		$tmpBagAfter = $ret[BagDef::BAG_TMP];
		
		//比较前后背包的变化
		$ret = array_diff($armBagAfter, $armBagBefore);
		$this->assertEquals(1, count($ret));
		$this->assertEquals(BagConf::INIT_GRID_NUM_PROPS - 1, count($tmpBagAfter));	
		
		//step2-1
		$ret = self::getUserBagData();
		$propBagBefore = $ret[BagDef::BAG_PROPS];
		
		//加N个可叠加物品(碎片，叠加上限是N)至背包
		Logger::debug('test add stackable item');
		$itemTplId = self::getItemTpl(ItemDef::ITEM_TYPE_DIRECT);
		$stackNum = ItemManager::getInstance()->getItemStackable($itemTplId);
		$this->assertTrue($stackNum > 1);
		$itemNum = BagConf::INIT_GRID_NUM_PROPS;
		$arrItemId = array();
		for ($i = 0; $i < $itemNum; $i++)
		{
			$ret = self::addItemByTpl($bag, $itemTplId, $stackNum);
			$arrItemId[] = current($ret);
		}
		$bag->update();
		
		//获取当前用户道具背包的数据
		$ret = self::getUserBagData();
		$propBagAfter = $ret[BagDef::BAG_PROPS];
		
		//比较前后背包的变化
		Logger::debug('propBagAfter:%s, propBagBefore:%s', $propBagAfter, $propBagBefore);
		$ret = array_diff($propBagAfter, $propBagBefore);
		$this->assertEquals($itemNum, count($ret));
		
		//step2-2
		//每个物品减一
		$propBagBefore = $propBagAfter;
		foreach ($arrItemId as $itemId)
		{
			$ret = $bag->decreaseItem($itemId, 1);
			$this->assertTrue($ret);
		}
		$bag->update();
		
		//获取当前用户道具背包的数据
		$ret = self::getUserBagData();
		$propBagAfter = $ret[BagDef::BAG_PROPS];
		
		//比较前后背包的变化
		$ret = array_diff($propBagAfter, $propBagBefore);
		$this->assertEquals(0, count($ret));
		
		//判定每个物品的数量
		foreach ($arrItemId as $itemId)
		{
			$item = ItemManager::getInstance()->getItem($itemId);
			$num = $item->getItemNum();
			$this->assertEquals($stackNum - 1, $num);
		}
		
		//step2-3
		//加N个可叠加物品(碎片，叠加上限是N)
		$propBagBefore = $propBagAfter;
		self::addItemByTpl($bag, $itemTplId, $itemNum);
		$bag->update();
		
		//获取当前用户道具背包的数据
		$ret = self::getUserBagData();
		$propBagAfter = $ret[BagDef::BAG_PROPS];
		
		//比较前后背包的变化
		$ret = array_diff($propBagAfter, $propBagBefore);
		$this->assertEquals(0, count($ret));
		
		//判定每个物品的数量
		foreach ($arrItemId as $itemId)
		{
			$item = ItemManager::getInstance()->getItem($itemId);
			$num = $item->getItemNum();
			$this->assertEquals($stackNum, $num);
		}
		
		//step2-4
		//加N个可叠加物品(碎片，叠加上限是N)
		$arrItemId = self::addItemByTpl($bag, $itemTplId, $stackNum);
		$bag->update();
		
		//获取当前用户道具背包的数据
		$ret = self::getUserBagData();
		$tmpBagAfter = $ret[BagDef::BAG_TMP];
		$this->assertEquals($itemNum, count($tmpBagAfter));
		$num = ItemManager::getInstance()->getItem($arrItemId[0])->getItemNum();
		$this->assertEquals($stackNum, $num);
	}
	
	public function testUseItemAfterFull()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = new Bag();
		$bag->clearBag();
		$bag->update();
		
		//加N个物品到道具背包
		$arrItemId = array();
		$itemNum = BagConf::INIT_GRID_NUM_PROPS;
		foreach(btstore_get()->ITEMS as $itemTplId => $itemConf)
		{
			if($itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] == ItemDef::ITEM_TYPE_DIRECT)
			{
				$arrItemId = self::addItemByTpl($bag, $itemTplId, 2);
				$bag->update();
				$itemNum--;
			}
			if ($itemNum == 0) 
			{
				break;
			}
		}
		$arrItemId = self::addItemByTpl($bag, 30101, 60);
		$bag->update();
		$this->assertTrue($bag->isFull(BagDef::BAG_PROPS));
		
		$itemId = $arrItemId[0];
		$gid = $bag->getGidByItemId($itemId);
		try {
			$ret = $bag->useItem($gid, $itemId, 5, 1);
			$this->assertEquals('bagfull', $ret['ret']);
		}
		catch ( Exception $e )
		{
			$this->assertEquals( 'fake',  $e->getMessage());
		}
		
		for ($i = 0; $i < 9; $i++)
		{
			$ret = $bag->useItem($gid, $itemId, 5);
			$this->assertEquals('ok', $ret['ret']);
		}
		$ret = $bag->useItem($gid, $itemId, 5);
		$this->assertEquals('bagfull', $ret['ret']);
	}
	
	public function testDeleteItembyTemplateID()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$bag = new Bag();
		$bag->clearBag();
		$bag->update();
	
		$itemTplId = $this->getStackItemTpl(ItemDef::ITEM_TYPE_DIRECT);
		$this->assertTrue($itemTplId > 0);
	
		$statckNum = ItemManager::getInstance()->getItemStackable($itemTplId);
		Logger::debug('test with itemTplId:%d, stackNum:%d', $itemTplId, $statckNum);
	
		$itemNumBefore = intval($statckNum*2.5);
		$arrItemId = ItemManager::getInstance()->addItem($itemTplId,  $itemNumBefore);
		$ret = $bag->addItems($arrItemId);
		$this->assertTrue($ret);
		$bag->update();
	
		$ret = $bag->bagInfo();
		$arrItemBefore = $ret[BagDef::BAG_PROPS];
		$this->assertEquals(3, count($arrItemBefore));
	
		$itemNumDel =  intval($statckNum + 1);
		$ret = $bag->deleteItembyTemplateID($itemTplId, $itemNumDel);
		$bag->update();
	
		$ret = $bag->bagInfo();
		$arrItemAfter = $ret[BagDef::BAG_PROPS];
		$this->assertEquals(2, count($arrItemAfter));
	
		$itemNumAfter = 0;
		foreach($arrItemAfter as $itemId => $itemInfo)
		{
			$itemNumAfter += $itemInfo[ItemDef::ITEM_SQL_ITEM_NUM];
		}
		$this->assertEquals($itemNumBefore-$itemNumDel, $itemNumAfter);
	
		$ret = array_diff_key($arrItemBefore, $arrItemAfter);
		$removeItemInfo = current($ret);
	
		$removeItem = $removeItemInfo[ItemDef::ITEM_SQL_ITEM_ID];
		$ret = ItemManager::getInstance()->getItem($removeItem);
		$this->assertEquals(NULL, $ret);
	}
	
	public function testGetItemNumByTemplateID()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = new Bag();
		$bag->clearBag();
		$bag->update();

		$itemTplId = self::getStackItemTpl(ItemDef::ITEM_TYPE_DIRECT);
		$this->assertTrue($itemTplId > 0);
		
		$statckNum = ItemManager::getInstance()->getItemStackable($itemTplId);
		Logger::debug('test with itemTplId:%d, stackNum:%d', $itemTplId, $statckNum);
		
		$itemNum = intval($statckNum*2.5);
		$arrItemId = ItemManager::getInstance()->addItem($itemTplId,  $itemNum);
		$ret = $bag->addItems($arrItemId);
		$this->assertTrue($ret);
		$bag->update();
		
		$ret = $bag->getItemNumByTemplateID($itemTplId);
		$this->assertEquals( $itemNum, $ret);
	}
	
	public function testGetItemIdsByItemType()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = new Bag();
		$bag->clearBag();
		$bag->update();
		
		$itemNum = 10;
		self::addItemByType($bag, ItemDef::ITEM_TYPE_DIRECT, $itemNum);
				
		$ret = $bag->getItemIdsByItemType(ItemDef::ITEM_TYPE_DIRECT);
		$this->assertTrue(!empty($ret));
	}
	
	public function testOpenGridByGold()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$userObj = EnUser::getUserObj(self::$uid);
		$ret = $userObj->addGold(10000, StatisticsDef::ST_FUNCKEY_BAG_OPENGRID);
		$this->assertTrue($ret);
		$goldNum = $userObj->getGold();
	
		$bag = new Bag();
		$ret = $bag->bagInfo();
		self::assertTrue(!empty($ret));
				
		$ret = self::getUserBagData();
		$gridNumBefore = count($ret[BagDef::BAG_ARM]);
	
		$ret = $bag->openGridByGold(5, 1);
		$this->assertEquals('ok', $ret);
	
		$ret = self::getUserBagData();
		$gridNumAfter = count($ret[BagDef::BAG_ARM]);
		$this->assertEquals($gridNumAfter, $gridNumBefore + 5);
		$this->assertEquals($userObj->getGold(), $goldNum - 25);
	}
	
	public function testUseItem()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$useAcq = array(
				ItemDef::ITEM_ATTR_NAME_USE_ACQ_SILVER,
				ItemDef::ITEM_ATTR_NAME_USE_ACQ_GOLD,
				ItemDef::ITEM_ATTR_NAME_USE_ACQ_EXECUTION,
				ItemDef::ITEM_ATTR_NAME_USE_ACQ_SOUL,
				ItemDef::ITEM_ATTR_NAME_USE_ACQ_STAMINA,
				ItemDef::ITEM_ATTR_NAME_USE_ACQ_ITEMS,
				ItemDef::ITEM_ATTR_NAME_USE_ACQ_HERO,
				ItemDef::ITEM_ATTR_NAME_USE_ACQ_CHALLENGE,
		);
		
		$bag = new Bag();
		$user = EnUser::getUserObj();
		$allItemConf = btstore_get()->ITEMS->toArray();
		
		foreach ($useAcq as $acq)
		{
			$itemTplId = 0;
			$itemNum = 1;
			$bag->clearBag();
			$bag->update();
			
			foreach($allItemConf as $id => $itemConf)
			{
				if(!empty($itemConf[ItemDef::ITEM_ATTR_NAME_USE_ACQ][$acq]) 
						&& empty($itemConf[ItemDef::ITEM_ATTR_NAME_USE_REQ][ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS]))
				{
					$add = $itemConf[ItemDef::ITEM_ATTR_NAME_USE_ACQ][$acq];
					$itemTplId = $id;
					break;
				}
			}
			
			$itemType = ItemManager::getInstance()->getItemType($itemTplId);
			if ( $itemType == ItemDef::ITEM_TYPE_FRAGMENT || $itemType == ItemDef::ITEM_TYPE_HEROFRAG)
			{
				$itemNum = ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_FRAGMENT_NUM);
			}
			$arrItemId = self::addItemByTpl($bag, $itemTplId, $itemNum);
			$itemId = current($arrItemId);
			$bag->update();
				
			Logger::trace('test with itemTplId:%d, itemId:%d', $itemTplId, $itemId);
			$gid = $bag->getGidByItemId($itemId);

			$addNum = 0;
			$numBefore = 0;
			switch ($acq)
			{
				case ItemDef::ITEM_ATTR_NAME_USE_ACQ_SILVER:
					$numBefore = $user->getSilver();
					$addNum = $add;
					break;
				case ItemDef::ITEM_ATTR_NAME_USE_ACQ_GOLD:
					$numBefore = $user->getGold();
					$addNum = $add;
					break;
				case ItemDef::ITEM_ATTR_NAME_USE_ACQ_EXECUTION:
					$numBefore = $user->getCurExecution();
					$addNum = $add;
					break;
				case ItemDef::ITEM_ATTR_NAME_USE_ACQ_SOUL:
					$numBefore = $user->getSoul();
					$addNum = $add;
					break;
				case ItemDef::ITEM_ATTR_NAME_USE_ACQ_STAMINA:
					$numBefore = $user->getStamina();
					$addNum = $add;
					break;
				case ItemDef::ITEM_ATTR_NAME_USE_ACQ_ITEMS:
					$numBefore = 0;
					$tmp = array();
					foreach ($add as $itemTplId => $num)
					{
						$stackNum = ItemManager::getInstance()->getItemStackable($itemTplId);
						if ($stackNum > 1)
						{
							if (isset($tmp[$itemTplId])) 
							{
								$tmp[$itemTplId] += $num;
								if ($stackNum >= $tmp[$itemTplId])
								{
									$num = 0;
								}
								else 
								{
									$num = 1 + $num / $stackNum;
								}
							}
							else 
							{
								$tmp[$itemTplId] = $num;
								$num = 1;
							}
						}
						$addNum += $num;
					}
					break;
				case ItemDef::ITEM_ATTR_NAME_USE_ACQ_HERO:
					$numBefore = $user->getHeroManager()->getHeroNum();
					foreach ($add as $htid => $num)
					{
						$addNum += $num;
					}
					break;
				case ItemDef::ITEM_ATTR_NAME_USE_ACQ_CHALLENGE:
					$arena = new Arena();
					$ret = $arena->getArenaInfo();
					$numBefore = $ret['res']['challenge_num'];
					$addNum = $add;	
					break;
				default:
					Logger::fatal('not support acq:%s', $acq);
					break;
			}
			
			$ret = $bag->useItem($gid, $itemId, $itemNum);
			$this->assertEquals('ok', $ret['ret']);
			$info = $bag->bagInfo();
			
			$numAfter = 0;
			switch ($acq)
			{
				case ItemDef::ITEM_ATTR_NAME_USE_ACQ_SILVER:
					$numAfter = $user->getSilver();
					break;
				case ItemDef::ITEM_ATTR_NAME_USE_ACQ_GOLD:
					$numAfter = $user->getGold();
					break;
				case ItemDef::ITEM_ATTR_NAME_USE_ACQ_EXECUTION:
					$numAfter = $user->getCurExecution();
					break;
				case ItemDef::ITEM_ATTR_NAME_USE_ACQ_SOUL:
					$numAfter = $user->getSoul();
					break;
				case ItemDef::ITEM_ATTR_NAME_USE_ACQ_STAMINA:
					$numAfter = $user->getStamina();
					break;
				case ItemDef::ITEM_ATTR_NAME_USE_ACQ_ITEMS:
					$numAfter = 0;
					$tmp = array();
					foreach ($add as $itemTplId => $num)
					{
						$itemType = ItemManager::getInstance()->getItemType($itemTplId);
						$bagName = $bag->getBagNameByItemType($itemType);
						if (isset($tmp[$bagName])) 
						{
							continue;
						}
						$tmp[$bagName] = 1;
						$numAfter += count($info[$bagName]);
					}
					break;
				case ItemDef::ITEM_ATTR_NAME_USE_ACQ_HERO:
					$numAfter = $user->getHeroManager()->getHeroNum();
					break;
				case ItemDef::ITEM_ATTR_NAME_USE_ACQ_CHALLENGE:
					$arena = ArenaDao::get(self::$uid, array('challenge_num'));
					$numAfter = $arena['challenge_num'];
					break;
			}

			if ($acq != ItemDef::ITEM_ATTR_NAME_USE_ACQ_DROP) 
			{
				if ($numBefore + $addNum != $numAfter) 
				{
					echo "type: ".$acq." ";
					echo "item template id: ".$itemTplId." ";
					echo "item num: ".$itemNum." ";
				}
				$this->assertEquals($numBefore + $addNum, $numAfter);
				continue;
			}
			
			$drop = $ret['drop'];
			if (!empty($drop['item'])) 
			{
				$items = $drop['item'];
				$addNum = 0;
				$numAfter = 0;
				$tmp = array();
				foreach ($items as $itemTplId => $num)
				{
					$stackNum = ItemManager::getInstance()->getItemStackable($itemTplId);
					if ($stackNum > 1)
					{
						if (isset($tmp[$itemTplId]))
						{
							$tmp[$itemTplId] += $num;
							if ($stackNum >= $tmp[$itemTplId])
							{
								$num = 0;
							}
							else
							{
								$num = 1 + $num / $stackNum;
							}
						}
						else
						{
							$tmp[$itemTplId] = $num;
							$num = 1;
						}
					}
					$addNum += $num;
				}
				$tmp = array();
				foreach ($items as $itemTplId => $num)
				{
					$itemType = ItemManager::getInstance()->getItemType($itemTplId);
					$bagName = $bag->getBagNameByItemType($itemType);
					if (isset($tmp[$bagName]))
					{
						continue;
					}
					$tmp[$bagName] = 1;
					$numAfter += count($info[$bagName]);
				}
				$this->assertEquals($numBefore+$addNum, $numAfter);
			}
			if (!empty($drop['hero']))
			{
				$heroes = $drop['hero'];
				$addNum = 0;
				$numAfter = 0;
				$numBefore = 5;
				$numAfter = $user->getHeroManager()->getHeroNum();
				foreach ($add as $htid => $num)
				{
					$addNum += $num;
				}
				$this->assertEquals($numBefore+$addNum, $numAfter);

			}
			if (!empty($drop['silver'])) 
			{
				$silver = $drop['silver'];
				$addNum = $silver;
				$numBefore = UserConf::INIT_SILVER;
				$numAfter = $user->getSilver();
				$this->assertEquals($numBefore+$addNum, $numAfter);
			}
		}
	}
	
	public function testRemoveItem()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = new Bag();
		$bag->clearBag();
		$bag->update();
		
		//确保包里面有个东西
		$arrItemId = self::addItemByType($bag, ItemDef::ITEM_TYPE_DIRECT, 2);		
		$bag->update();

		$ret = self::getUserBagData();
		$propBag = $ret[BagDef::BAG_PROPS];		
		$itemId = current($propBag);
				
		$ret = $bag->removeItem($itemId);
		$this->assertTrue($ret);
		$bag->update();		
		
		//remove之后不在背包但是在系统中
		$ret = self::getUserBagData();
		$propBag = $ret[BagDef::BAG_PROPS];
		$this->assertTrue( !in_array($itemId, $propBag) );
		
		$ret = ItemManager::getInstance()->getItem($itemId);				
		$this->assertTrue( !empty($ret) );
	}
	
	public function testDeleteItem()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = new Bag();
		$bag->clearBag();
		$bag->update();
		
		//确保包里面有个东西
		$arrItemId = self::addItemByType($bag, ItemDef::ITEM_TYPE_DIRECT, 2);
		$bag->update();
		
		$ret = self::getUserBagData();
		$propBag = $ret[BagDef::BAG_PROPS];
		$itemId = current($propBag);
		
		$ret = $bag->deleteItem($itemId);
		$this->assertTrue($ret);
		$bag->update();
		
		//delete之后不在背包，也不在系统中
		$ret = self::getUserBagData();
		$propBag = $ret[BagDef::BAG_PROPS];
		$this->assertTrue( !in_array($itemId, $propBag) );
		
		$ret = ItemManager::getInstance()->getItem($itemId);
		$this->assertEquals(NULL, $ret);
	}

	public function testDecreaseItem()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = new Bag();
		$bag->clearBag();
		$bag->update();
		
		//确保包里面有个东西
		$arrItemId = self::addItemByType($bag, ItemDef::ITEM_TYPE_DIRECT, 2);
		$bag->update();
		
		$ret = self::getUserBagData();
		$propBag = $ret[BagDef::BAG_PROPS];
		//从里面找一个数量>1的物品
		$itemId = 0;
		foreach ($propBag as $gid => $id)
		{
			$item = ItemManager::getInstance()->getItem($id);
			if( $item->getItemNum() > 1)
			{
				$itemId = $id;
				$itemNum = $item->getItemNum();
				break;
			}
		}
		$this->assertTrue($itemId > 0);
		
		$ret = $bag->decreaseItem($itemId, 1 );
		$this->assertTrue($ret);
		$bag->update();
		
		//减了一下数目，东西应该还在
		$ret = self::getUserBagData();
		$propBag = $ret[BagDef::BAG_PROPS];
		$this->assertTrue( in_array($itemId, $propBag) );		
		$item = ItemManager::getInstance()->getItem($itemId);
		$this->assertTrue( ! empty($item) );
		$this->assertEquals( $item->getItemNum(), $itemNum - 1);
		
		//彻底减完，东西就没有了
		$ret = $bag->decreaseItem($itemId, $itemNum - 1 );
		$this->assertTrue($ret);
		$bag->update();
		
		$ret = self::getUserBagData();
		$propBag = $ret[BagDef::BAG_PROPS];
		$this->assertTrue( ! in_array($itemId, $propBag) );
		$item = ItemManager::getInstance()->getItem($itemId);
		$this->assertTrue( empty($item) );
	}
	
	public function testSellItem()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = new Bag();
		$bag->clearBag();
		$bag->update();
		
		//确保包里面有个东西
		$itemNum = 1;
		$arrItemId = self::addItemByType($bag, ItemDef::ITEM_TYPE_ARM, $itemNum);
		$bag->update();
		$itemId = current($arrItemId);
		$gid = $bag->getGidByItemId($itemId);
		$item = ItemManager::getInstance()->getItem($itemId);
		$sellInfo = $item->sellInfo();
		$sellPrice = $sellInfo[ItemDef::ITEM_ATTR_NAME_SELL_PRICE];
		$ret = $bag->bagInfo();
		$armBagBefore = $ret[BagDef::BAG_ARM];
		$silverBefore = EnUser::getUserObj()->getSilver();

		$bag->sellItems(array(array($gid, $itemId, $itemNum)));
		$ret = $bag->bagInfo();
		$armBagAfter = $ret[BagDef::BAG_ARM];
		$silverAfter = EnUser::getUserObj()->getSilver();
		
		$this->assertEquals($silverBefore + $sellPrice, $silverAfter);
		$this->assertEquals(count($armBagBefore)-1, count($armBagAfter));
		$item = ItemManager::getInstance()->getItem($itemId);
		$this->assertTrue( empty($item) );
	}
	
	public function testDropItems()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = new Bag();
		$bag->clearBag();
		$bag->update();
		
		$dropConf = btstore_get()->DROP_ITEM;
		
		$dropId = 0;
		foreach ($dropConf as $id => $conf)
		{
			if ($conf[DropDef::DROP_TYPE] == DropDef::DROP_TYPE_ITEM) 
			{
				$dropId = $id;
				break;
			}
		}
		$ret = $bag->dropItems(array($dropId));
		$this->assertTrue($ret);
	}

	public function testCheckTmpBag()
	{
		Logger::debug('======%s======', __METHOD__);
		
		//先把道具背包放满
		$bag = new Bag();
		$bag->clearBag();
				
		$itemTplId = self::getItemTpl(ItemDef::ITEM_TYPE_DIRECT);		
		$itemNum = $bag->getItemNumByTemplateID($itemTplId);
		$stackNum = ItemManager::getInstance()->getItemStackable($itemTplId);
		
		self::addItemByTpl($bag, $itemTplId, BagConf::INIT_GRID_NUM_PROPS*$stackNum-$itemNum);
		
		$this->assertTrue($bag->isFull());
		
		$arrItemIdInTmp = self::addItemByTpl($bag, $itemTplId, 2*$stackNum + 1);	
		
		$bagInfo = $bag->bagInfo();
		$propBag = $bagInfo[BagDef::BAG_PROPS];
		
		$bag->update();
		
		
		//这个时候多出来的物品在临时背包中
		$arrItemId = array();
		$itemNotInTmp = 0;
		foreach($propBag as $gid => $itemInfo)
		{
			if(Bag::getBagNameByGid($gid) == BagDef::BAG_TMP)
			{
				$arrItemId[] = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
			}
			else
			{
				$itemIdNotInTmp = array($gid, $itemInfo[ItemDef::ITEM_SQL_ITEM_ID], $itemInfo[ItemDef::ITEM_SQL_ITEM_NUM]);
			}
		}
		sort($arrItemIdInTmp);
		sort($arrItemId);
		$this->assertEquals($arrItemIdInTmp, $arrItemId);
		
		$bag->sellItems(array($itemIdNotInTmp));
		
		$bag->update();
						
		$bagInfo = $bag->bagInfo();
		$propBag = $bagInfo[BagDef::BAG_PROPS];
		
		$arrItemId = array();
		foreach($propBag as $gid => $itemInfo)
		{
			if(Bag::getBagNameByGid($gid) == BagDef::BAG_TMP)
			{
				$arrItemId[] = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
			}
		}
		
		$this->assertEquals(count($arrItemIdInTmp)-1, count($arrItemId));
	}
	
	public function testFull()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = new Bag();
		
		$ret = $bag->isFull();
	}
	
	public function testMergeItem()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = new Bag();
		
		// 测试不可叠加物品，无法合并
		$bag->clearBag();
		$bag->update();
		$itemTemplateId = self::getUnStackItemTpl(ItemDef::ITEM_TYPE_ARM);
		$ret = $bag->mergeItem($itemTemplateId);
		$this->assertFalse($ret);
		
		// 测试合并的物品只有一个，没必要合并
		$bag->clearBag();
		$bag->update();
		$itemTemplateId = self::getStackItemTpl(ItemDef::ITEM_TYPE_HEROFRAG);
		$maxStackSize = ItemAttr::getItemAttr($itemTemplateId, ItemDef::ITEM_ATTR_NAME_STACKABLE);
		$arrItemId = $this->addItemByTpl($bag, $itemTemplateId, $maxStackSize - 1);
		$this->assertEquals(1, count($arrItemId));
		$this->assertEquals($bag->getItemNumByTemplateID($itemTemplateId), $maxStackSize - 1);
		$ret = $bag->mergeItem($itemTemplateId);
		$this->assertFalse($ret);
		
		// 测试如果没有达到堆叠上限的物品数量个数小于等于1，也没必要合并
		$bag->clearBag();
		$bag->update();
		$itemTemplateId = self::getStackItemTpl(ItemDef::ITEM_TYPE_HEROFRAG);
		$maxStackSize = ItemAttr::getItemAttr($itemTemplateId, ItemDef::ITEM_ATTR_NAME_STACKABLE);
		$arrItemId = $this->addItemByTpl($bag, $itemTemplateId, 2 * $maxStackSize);
		$this->assertEquals(2, count($arrItemId));
		$this->assertEquals($bag->getItemNumByTemplateID($itemTemplateId), 2 * $maxStackSize);
		$ret = $bag->mergeItem($itemTemplateId);
		$this->assertFalse($ret);
		$bag->update();
		
		// 测试正常合并的情况 TODO
	}

	public static function addItemByType(&$bagObj, $itemType, $itemNum)
	{
		$itemTplId = self::getItemTpl($itemType);
		return self::addItemByTpl($bagObj, $itemTplId, $itemNum);
	}
	
	public static function addItemByTpl(&$bagObj, $itemTplId, $itemNum)
	{
		self::assertTrue($itemTplId > 0);
		$arrItemId = ItemManager::getInstance()->addItem($itemTplId , $itemNum);
		$ret = $bagObj->addItems($arrItemId, true);
		self::assertTrue($ret);
		return $arrItemId;
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
	
	public static function getStackItemTpl($itemType)
	{
		$allItemConf = btstore_get()->ITEMS->toArray();
		
		$itemTplId = 0;
		foreach($allItemConf as $id => $itemConf)
		{
			if( $itemConf[ItemDef::ITEM_ATTR_NAME_STACKABLE] > 1 
					&& $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] == $itemType)
			{
				$itemTplId = $id;
				break;
			}
		}
		return $itemTplId;
	}	
	
	public static function getUnStackItemTpl($itemType)
	{
		$allItemConf = btstore_get()->ITEMS->toArray();
	
		$itemTplId = 0;
		foreach($allItemConf as $id => $itemConf)
		{
			if( $itemConf[ItemDef::ITEM_ATTR_NAME_STACKABLE] == 1
					&& $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] == $itemType)
			{
				$itemTplId = $id;
				break;
			}
		}
		return $itemTplId;
	}
	
	public static function createUser()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval($pid);
		$ret = UserLogic::createUser($pid , 1, $uname);
		$uid = $ret['uid'];
		
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$console = new Console();
		$console->open();
		$console->gold(10000);
		
		return array($pid, $uid);
	}
	
	public static function getUserBagData()
	{
		$arrBagData = array();
		
		$select = array(BagDef::SQL_ITEM_ID, BagDef::SQL_GID);
		$where = array(BagDef::SQL_UID, '=', self::$uid);
		$return = BagDAO::selectBag($select, $where);
		$arrBagData[BagDef::BAG_TMP] = array();
		$arrBagData[BagDef::BAG_PROPS] = array();
		$arrBagData[BagDef::BAG_ARM] = array();
		$arrBagData[BagDef::BAG_HERO_FRAG] = array();
		$arrBagData[BagDef::BAG_TREAS] = array();
		$arrBagData[BagDef::BAG_ARM_FRAG] = array();
		$arrBagData[BagDef::BAG_DRESS] = array();
		$arrBagData[BagDef::BAG_FIGHT_SOUL] = array();

		foreach ($return as $value)
		{
			$gid = intval($value[BagDef::SQL_GID]);
			$itemId = intval($value[BagDef::SQL_ITEM_ID]);
			
			$bagName = Bag::getBagNameByGid($gid);
			$arrBagData[$bagName][$gid] = $itemId;					
		}
		
		return $arrBagData;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */