<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ForgeTest.php 210194 2015-11-17 08:15:15Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/forge/test/ForgeTest.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-11-17 08:15:15 +0000 (Tue, 17 Nov 2015) $
 * @version $Revision: 210194 $
 * @brief 
 *  
 **/
class ForgeTest extends PHPUnit_Framework_TestCase
{
	protected static $uid = 26591;
	protected static $itemId = 0;
	
	public static function setUpBeforeClass()
	{
		self::createUser();
		RPCContext::getInstance()->setSession('global.uid', self::$uid);
		EnSwitch::getSwitchObj(self::$uid)->addNewSwitch(SwitchDef::FORGE);
		EnSwitch::getSwitchObj(self::$uid)->addNewSwitch(SwitchDef::ITEMENFORCE);
		EnSwitch::getSwitchObj(self::$uid)->addNewSwitch(SwitchDef::TREASUREENFORCE);
		EnSwitch::getSwitchObj(self::$uid)->addNewSwitch(SwitchDef::TREASUREEVOLVE);
		EnSwitch::getSwitchObj(self::$uid)->addNewSwitch(SwitchDef::ARMREFRESH);
		EnSwitch::getSwitchObj(self::$uid)->addNewSwitch(SwitchDef::FIGHTSOUL);
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
	
	public static function getItemTplId($itemType)
	{
		$allItemConf = btstore_get()->ITEMS;
	
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
	
	public function test_reinforce()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = BagManager::getInstance()->getBag(self::$uid);
		$bag->clearBag();
		$bag->update();
		
		//加物品
		$itemTplId = self::getItemTplId(ItemDef::ITEM_TYPE_ARM);
		$itemNum = 1;
		$arrItemId = ItemManager::getInstance()->addItem($itemTplId , $itemNum);
		$itemId = current($arrItemId);
		$bag->addItem($itemId);
		$bag->update();
		//获得装备强化等级
		$item = ItemManager::getInstance()->getItem($itemId);
		$levelBefore = $item->getLevel();
		$user = EnUser::getUserObj(self::$uid);
		$vip = 2;
		$user->setVip($vip);
		$fatalInfo = btstore_get()->VIP[$vip]['fatalWeight'];
		$levelNum = key($fatalInfo);
		$silverBefore = $user->getSilver();
		$level = 1;
		$feeId = ItemAttr::getItemAttr($itemTplId, ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE);
		$subSilver =  btstore_get()->REINFORCE_FEE[$feeId][$level][armdef::ITEM_ATTR_NAME_ARM_REINFORCE_SILVER];
		//装备强化
		$forge = new Forge();
		$ret = $forge->reinforce($itemId, $level);
		$this->assertTrue($ret['fatal_num'] == 1);
		$this->assertTrue($ret['level_num'] >= $levelNum);
		$levelAfter = $item->getLevel();
		$this->assertEquals($levelBefore + $ret['level_num'], $levelAfter);
		$silverAfter = $user->getSilver();
		$this->assertEquals($silverBefore - $subSilver, $silverAfter);
	}
	
	public function test_autoReinforce()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = BagManager::getInstance()->getBag(self::$uid);
		$bag->clearBag();
		$bag->update();
		
		//加物品
		$itemTplId = self::getItemTplId(ItemDef::ITEM_TYPE_ARM);
		$itemNum = 1;
		$arrItemId = ItemManager::getInstance()->addItem($itemTplId , $itemNum);
		$itemId = current($arrItemId);
		$bag->addItem($itemId);
		$bag->update();
		//获得装备强化等级
		$item = ItemManager::getInstance()->getItem($itemId);
		$levelBefore = $item->getLevel();
		$user = EnUser::getUserObj(self::$uid);
		$vip = 2;
		$user->setVip($vip);
		$fatalInfo = btstore_get()->VIP[$vip]['fatalWeight'];
		$silverBefore = $user->getSilver();
		//装备强化
		$forge = new Forge();
		$ret = $forge->autoReinforce($itemId);
		$levelAfter = $item->getLevel();
		$silverAfter = $user->getSilver();
		//比对结果
		$addLevel = 0;
		$subSilver = 0;
		foreach ($ret as $info)
		{
			$subSilver += $info['cost_num'];
			$addLevel += $info['level_num'];
		}
		$this->assertEquals($levelBefore + $addLevel, $levelAfter);
		$this->assertEquals($silverBefore - $subSilver, $silverAfter);
	}
	
	public function test_upgrade()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = BagManager::getInstance()->getBag(self::$uid);
		$bag->clearBag();
		$bag->update();
		
		$itemTplId = self::getItemTplId(ItemDef::ITEM_TYPE_TREASURE);
		$itemNum = 6;
		$itemIds = ItemManager::getInstance()->addItem($itemTplId , $itemNum);
		$bag->addItems($itemIds);
		$bag->update();
		
		$itemId = $itemIds[0];
		unset($itemIds[0]);
		$sumBaseValue = 0;
		foreach ($itemIds as $id)
		{
			$itemOther = ItemManager::getInstance()->getItem($id);
			$sumBaseValue += $itemOther->getBaseValue() + $itemOther->getExp();
		}
		$item = ItemManager::getInstance()->getItem($itemId);
		$expBefore = $item->getExp();
		$levelBefore = $item->getLevel();
		$expend = $item->getUpgradeExpend($levelBefore);
		$upgrade = $item->getUpgradeValue($levelBefore);
		$user = EnUser::getUserObj(self::$uid);
		$silverBefore = $user->getSilver();
		
		$forge = new Forge();
		$forge->upgrade($itemId, $itemIds);
		
		$silverAfter = $user->getSilver();
		$this->assertEquals($silverBefore - $expend * $sumBaseValue, $silverAfter);
		$expAfter = $item->getExp();
		$this->assertEquals($expBefore + $sumBaseValue, $expAfter);
		$levelAfter = $item->getLevel();
		if ($expBefore + $sumBaseValue >= $sumBaseValue) 
		{
			$this->assertTrue($levelAfter > $levelBefore);
		}
		
		$ret = ItemManager::getInstance()->getItems($itemIds);
		foreach ($ret as $id => $value)
		{
			$this->assertEquals(NULL, $value);
		}
	}
	
	public function test_evolve()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = BagManager::getInstance()->getBag(self::$uid);
		$bag->clearBag();
		$bag->update();
		
		$itemTplId = 501401;
		$itemNum = 2;
		$itemIds = ItemManager::getInstance()->addItem($itemTplId , $itemNum);
		$bag->addItems($itemIds);
		$bag->update();
		
		$itemId = $itemIds[0];
		$otherItemId = $itemIds[1];
		
		$itemTplId = self::getItemTplId(ItemDef::ITEM_TYPE_TREASURE);
		$itemNum = 5;
		$itemIds = ItemManager::getInstance()->addItem($itemTplId , $itemNum);
		$bag->addItems($itemIds);
		$bag->update();

		$forge = new Forge();
		$forge->upgrade($itemId, $itemIds);
		
		$item = ItemManager::getInstance()->getItem($itemId);
		$evolveBefore = $item->getEvolve();
		$evolveExpend = $item->getEvolveExpend($evolveBefore);
		$silver = $evolveExpend['silver'];
		$user = EnUser::getUserObj(self::$uid);
		$user->addSilver($silver);
		$user->update();
		$silverBefore = $user->getSilver();
		
		$forge->evolve($itemId, array($otherItemId));
		
		$evolveAfter = $item->getEvolve();
		$this->assertEquals($evolveBefore+1, $evolveAfter);
		$silverAfter = $user->getSilver();
		$this->assertEquals($silverBefore - $silver, $silverAfter);
		
		$itemIds[] = $otherItemId;
		$ret = ItemManager::getInstance()->getItems($itemIds);
		foreach ($ret as $id => $value)
		{
			$this->assertEquals(NULL, $value);
		}
	}
	
	public function test_promote()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = BagManager::getInstance()->getBag(self::$uid);
		$bag->clearBag();
		$bag->update();
		
		$itemTplId = self::getItemTplId(ItemDef::ITEM_TYPE_FIGHTSOUL);
		$itemNum = 6;
		$itemIds = ItemManager::getInstance()->addItem($itemTplId , $itemNum);
		$bag->addItems($itemIds);
		$bag->update();
		
		$itemId = $itemIds[0];
		unset($itemIds[0]);
		$sumBaseValue = 0;
		foreach ($itemIds as $id)
		{
			$itemOther = ItemManager::getInstance()->getItem($id);
			$sumBaseValue += $itemOther->getValue() + $itemOther->getExp();
		}
		$item = ItemManager::getInstance()->getItem($itemId);
		$expBefore = $item->getExp();
		$levelBefore = $item->getLevel();
		$upgrade = $item->getUpgradeValue($levelBefore+1);
		
		$forge = new Forge();
		$ret = $forge->promote($itemId, $itemIds);
		
		$expAfter = $item->getExp();
		$this->assertEquals($expBefore + $sumBaseValue, $expAfter);
		$levelAfter = $item->getLevel();
		if ($expBefore + $sumBaseValue >= $sumBaseValue)
		{
			$this->assertTrue($levelAfter > $levelBefore);
		}
		
		$ret = ItemManager::getInstance()->getItems($itemIds);
		foreach ($ret as $id => $value)
		{
			$this->assertEquals(NULL, $value);
		}
	}
	
	public function test_upgradeDress()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$bag = BagManager::getInstance()->getBag(self::$uid);
		$bag->clearBag();
		$bag->update();
	
		$itemTplId = self::getItemTplId(ItemDef::ITEM_TYPE_DRESS);
		$itemNum = 1;
		$itemIds = ItemManager::getInstance()->addItem($itemTplId , $itemNum);
		$itemId = $itemIds[0];
		$bag->addItem($itemId);
		$bag->update();
	
		$item = ItemManager::getInstance()->getItem($itemId);
		$levelBefore = $item->getLevel();
		$cost = $item->getCost($levelBefore + 1);
		$user = EnUser::getUserObj(self::$uid);
		if ($user->getLevel() < $cost['level'])
		{
			$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
			$user->addExp($expTable[$cost['level']]);
			$user->update();
		}
		$silverBefore = $user->getSilver();
		if ($silverBefore < $cost['silver']) 
		{
			$user->addSilver($cost['silver']);
			$user->update();
			$silverBefore = $user->getSilver();
		}
		$items = $cost['item'];
		$itemIds = ItemManager::getInstance()->addItems($items);
		$bag->addItems($itemIds);
		$bag->update();

		$forge = new Forge();
		$ret = $forge->upgradeDress($itemId);
	
		$levelAfter = $item->getLevel();
		$this->assertEquals($levelBefore + 1, $levelAfter);
		$silverAfter = $user->getSilver();
		$this->assertEquals($silverBefore - $cost['silver'], $silverAfter);
	
		$ret = ItemManager::getInstance()->getItems($itemIds);
		foreach ($ret as $id => $value)
		{
			$this->assertEquals(NULL, $value);
		}
	}
	
	public function test_fixedRefresh()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = BagManager::getInstance()->getBag(self::$uid);
		$bag->clearBag();
		$bag->update();
		
		//加物品
		$itemTplId = 101301;
		$itemNum = 1;
		$arrItemId = ItemManager::getInstance()->addItem($itemTplId , $itemNum);
		$itemId = current($arrItemId);
		self::$itemId = $itemId;
		$bag->addItem($itemId);
		$item = ItemManager::getInstance()->getItem($itemId);
		$cost = $item->getFixedRefreshCost(2);
		$items = $cost[0];
		$bag->addItemsByTemplateID($cost[0]);
		$bag->update();
		$itemText = $item->getItemText();
		if (empty($itemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE])) 
		{
			$itemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE] = array();
		}
		$potenceBefore = $itemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE];
		$user = EnUser::getUserObj(self::$uid);
		$user->setVip(10);
		$user->addSilver($cost[1]);
		$user->addGold($cost[2], StatisticsDef::ST_FUNCKEY_FORGE_FIXED_REFRESH);
		$user->update();
		$silverBefore = $user->getSilver();
		$goldBefore = $user->getGold();
	
		$forge = new Forge();
		$ret = $forge->fixedRefresh($itemId, 2);
		$this->assertTrue(!empty($ret));
		$item = ItemManager::getInstance()->getItem($itemId);
		$itemText = $item->getItemText();
		$potenceAfter = $itemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE];
		foreach ($itemText[ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE] as $attrId => $attrValue)
		{
			$potenceId = $item->getFixedPotenceId();
			$itemText[ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE][$attrId] = $item->getPotenceValue($potenceId, $attrId, $attrValue);
		}
		$this->assertEquals($ret['potence'], $itemText[ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE]);
		$silverAfter = $user->getSilver();
		$goldAfter = $user->getGold();
		$this->assertEquals($silverBefore - $cost[1], $silverAfter);
		$this->assertEquals($goldBefore - $cost[2], $goldAfter);
	}
	
	public function test_fixedRefreshAffirm()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$item = ItemManager::getInstance()->getItem(self::$itemId);
		$itemText = $item->getItemText();
		$potenceBefore = $itemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE];
		$forge = new Forge();
		$ret = $forge->fixedRefreshAffirm(self::$itemId);
		$itemText = $item->getItemText();
		$potenceAfter = $itemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE];
	}
	
	public function test_compose()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$method = 1;
		$conf = btstore_get()->FOUNDRY[$method];
		$itemTplId = $conf[ForgeDef::FOUNDRY_BASE];
		$arrItemId = ItemManager::getInstance()->addItem($itemTplId);
		$itemId = current($arrItemId);
		$bag = BagManager::getInstance()->getBag(self::$uid);
		$bag->addItem($itemId);
		$items = $conf[ForgeDef::FOUNDRY_ITEM];
		$bag->addItemsByTemplateID($items);
		$bag->update();
		
		$user = EnUser::getUserObj(self::$uid);
		foreach ($conf[ForgeDef::FOUNDRY_COST] as $key => $value)
		{
			switch ($key)
			{
				case 1:$jewelBefore = $user->getJewel();$user->addJewel($value);break;
				case 2:$goldBefore = $user->getGold();$user->addGold($value, StatisticsDef::ST_FUNCKEY_FORGE_COMPOSE_COST);break;
				case 3:$silverBefore = $user->getSilver();$user->addSilver($value);break;
				default:
					throw new ConfigException('invalid cost type:%d', $key);
			}
		}
		$user->update();
		
		$forge = new Forge();
		$ret = $forge->compose($method, $itemId);
		$this->assertEquals('ok', $ret);
		$item = ItemManager::getInstance()->getItem($itemId);
		$this->assertEquals(null, $item);
		$num = $bag->getItemNumByTemplateID($conf[ForgeDef::FOUNDRY_FORM]);
		$this->assertEquals(1, $num);
		foreach ($conf[ForgeDef::FOUNDRY_COST] as $key => $value)
		{
			switch ($key)
			{
				case 1:$jewelAfter = $user->getJewel();$this->assertEquals($jewelBefore, $jewelAfter);break;
				case 2:$goldAfter = $user->getGold();$this->assertEquals($goldBefore, $goldAfter);break;
				case 3:$silverAfter = $user->getSilver();$this->assertEquals($silverBefore, $silverAfter);break;
				default:
					throw new ConfigException('invalid cost type:%d', $key);
			}
		}
	}
	
	public function test_treasReborn()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = BagManager::getInstance()->getBag(self::$uid);
		$bag->clearBag();
		$bag->update();
		
		$itemTplId = 502501;
		$itemNum = 1;
		$itemIds = ItemManager::getInstance()->addItem($itemTplId , $itemNum);
		$bag->addItems($itemIds);
		$bag->update();
		
		//加经验值
		$itemId = $itemIds[0];
		$item = ItemManager::getInstance()->getItem($itemId);
		$resolveItemTplId = $item->getResolveItem();
		$resolveItemValue = ItemAttr::getItemAttr($resolveItemTplId, TreasureDef::ITEM_ATTR_NAME_TREASURE_VALUE_BASE);
		$addExp = ItemDef::UPPER_LIMIT_NUM_FOR_EXP_ITEM * ItemDef::UPPER_LIMIT_EXP_FOR_TREASURE + $resolveItemValue;
		$item->addExp($addExp);
		$bag->update();
		
		$mys = new MysteryShop();
		$mys->rebornTreasure(array($itemId));
		$item = ItemManager::getInstance()->getItem($itemId);
		$this->assertEquals(0, $item->getExp());
		$arrItemId = $bag->getItemIdsByItemType(ItemDef::ITEM_TYPE_TREASURE);
		$num = 0;
		$sumExp = 0;
		foreach ($arrItemId as $id)
		{
			if ($id == $itemId) 
			{
				continue;
			}
			$num++;
			$itemObj = ItemManager::getInstance()->getItem($id);
			$sumExp += $itemObj->getExp() + $itemObj->getBaseValue();
			$bag->deleteItem($id);
		}
		$this->assertEquals($sumExp, $addExp);
		$this->assertEquals($num, ItemDef::UPPER_LIMIT_NUM_FOR_EXP_ITEM);
		
		$addExp = (ItemDef::UPPER_LIMIT_NUM_FOR_EXP_ITEM - 1) * ItemDef::UPPER_LIMIT_EXP_FOR_TREASURE + $resolveItemValue - 1;
		$item->addExp($addExp);
		$bag->update();
		
		$mys = new MysteryShop();
		$mys->rebornTreasure(array($itemId));
		$item = ItemManager::getInstance()->getItem($itemId);
		$this->assertEquals(0, $item->getExp());
		$arrItemId = $bag->getItemIdsByItemType(ItemDef::ITEM_TYPE_TREASURE);
		$num = 0;
		$sumExp = 0;
		foreach ($arrItemId as $id)
		{
			if ($id == $itemId)
			{
				continue;
			}
			$num++;
			$itemObj = ItemManager::getInstance()->getItem($id);
			$sumExp += $itemObj->getExp() + $itemObj->getBaseValue();
			$bag->deleteItem($id);
		}
		$this->assertEquals($sumExp, $addExp);
		$this->assertEquals($num, ItemDef::UPPER_LIMIT_NUM_FOR_EXP_ITEM - 1);
	}
	
	public function test_pocketReborn()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$bag = BagManager::getInstance()->getBag(self::$uid);
		$bag->clearBag();
		$bag->update();
	
		$itemTplId = 840001;
		$itemNum = 1;
		$itemIds = ItemManager::getInstance()->addItem($itemTplId , $itemNum);
		$bag->addItems($itemIds);
		$bag->update();
	
		//加经验值
		$itemId = $itemIds[0];
		$item = ItemManager::getInstance()->getItem($itemId);
		$rebornItemValue = ItemAttr::getItemAttr(PocketDef::REBORN_ITEM, PocketDef::ITEM_ATTR_NAME_POCKET_VALUE);
		$addExp = ItemDef::UPPER_LIMIT_NUM_FOR_EXP_ITEM * ItemDef::UPPER_LIMIT_EXP_FOR_POCKET + $rebornItemValue;
		$item->addExp($addExp);
		$bag->update();
	
		$rebornCost = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_POCKET_REBORN][$item->getItemQuality()];
		EnUser::getUserObj(self::$uid)->addGold($rebornCost, StatisticsDef::ST_FUNCKEY_MYSTERYSHOP_REBORN_POCKET);
		EnUser::getUserObj(self::$uid)->update();
		$mys = new MysteryShop();
		$mys->rebornPocket(array($itemId));
		$item = ItemManager::getInstance()->getItem($itemId);
		$this->assertEquals(0, $item->getExp());
		$arrItemId = $bag->getItemIdsByItemType(ItemDef::ITEM_TYPE_POCKET);
		$num = 0;
		$sumExp = 0;
		foreach ($arrItemId as $id)
		{
			if ($id == $itemId)
			{
				continue;
			}
			$num++;
			$itemObj = ItemManager::getInstance()->getItem($id);
			$sumExp += $itemObj->getExp() + $itemObj->getValue();
			$bag->deleteItem($id);
		}
		$this->assertEquals($sumExp, $addExp);
		$this->assertEquals($num, ItemDef::UPPER_LIMIT_NUM_FOR_EXP_ITEM);
	
		$addExp = (ItemDef::UPPER_LIMIT_NUM_FOR_EXP_ITEM - 1) * ItemDef::UPPER_LIMIT_EXP_FOR_POCKET + $rebornItemValue - 1;
		$item->addExp($addExp);
		$bag->update();
	
		EnUser::getUserObj(self::$uid)->addGold($rebornCost, StatisticsDef::ST_FUNCKEY_MYSTERYSHOP_REBORN_POCKET);
		EnUser::getUserObj(self::$uid)->update();
		$mys = new MysteryShop();
		$mys->rebornPocket(array($itemId));
		$item = ItemManager::getInstance()->getItem($itemId);
		$this->assertEquals(0, $item->getExp());
		$arrItemId = $bag->getItemIdsByItemType(ItemDef::ITEM_TYPE_POCKET);
		$num = 0;
		$sumExp = 0;
		foreach ($arrItemId as $id)
		{
			if ($id == $itemId)
			{
				continue;
			}
			$num++;
			$itemObj = ItemManager::getInstance()->getItem($id);
			$sumExp += $itemObj->getExp() + $itemObj->getValue();
			$bag->deleteItem($id);
		}
		$this->assertEquals($sumExp, $addExp);
		$this->assertEquals($num, ItemDef::UPPER_LIMIT_NUM_FOR_EXP_ITEM - 1);
		
		
	}
	
	public function test_getExtraAttr()
	{
		$arrItemId = array(114416267,114416307,114416282,114416270);
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		$arrItemInfo = array();
		foreach ($arrItem as $item)
		{
			$arrItemInfo[] = $item->itemInfo();
		}
		
		ArmItem::getExtraAttr(array(), $arrItemInfo);
	}
	
/*
	public function test_randRefresh()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = BagManager::getInstance()->getBag($this->uid);
		$bag->clearBag();
		$bag->update();
		
		$itemTplId = self::getItemTplId();
		$itemNum = 1;
		$arrItemId = ItemManager::getInstance()->addItem($itemTplId , $itemNum);
		$itemId = current($arrItemId);
		$bag->addItem($itemId);
		$bag->update();
		
		$item = ItemManager::getInstance()->getItem($itemId);
		$itemText = $item->getItemText();
		$potenceBefore = $itemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE];
		Logger::debug("itemId:%d before rand refresh potenceId:%d num:%d", $this->itemId, key($potenceBefore), current($potenceBefore));
		$user = EnUser::getUserObj($this->uid);
		$user->addSilver(20000);
		$user->update();
		
		$ret = $this->forge->randRefresh($this->itemId, true);
		$itemText = $item->getItemText();
		$potenceAfter = $itemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE];
		Logger::debug("itemId:%d after rand refresh potenceId:%d num:%d", $this->itemId, key($potenceAfter), current($potenceAfter));
		$this->assertTrue($potenceBefore != $potenceAfter);
	}
	
	public function test_randRefreshAffirm()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$bag = BagManager::getInstance()->getBag($this->uid);
		$bag->clearBag();
		$bag->update();
		
		$itemTplId = self::getItemTplId();
		$itemNum = 1;
		$arrItemId = ItemManager::getInstance()->addItem($itemTplId , $itemNum);
		$itemId = current($arrItemId);
		$bag->addItem($itemId);
		$bag->update();
		
		$item = ItemManager::getInstance()->getItem($itemId);
		$itemText = $item->getItemText();
		$potenceBefore = $itemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE];
		Logger::debug("itemId:%d before rand refresh affirm potenceId:%d num:%d", $this->itemId, key($potenceBefore), current($potenceBefore));
		$ret = $this->forge->randRefreshAffirm($itemId);
		$itemText = $item->getItemText();
		$potenceAfter = $itemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE];
		Logger::debug("itemId:%d after rand refresh affirm potenceId:%d num:%d", $this->itemId, key($potenceAfter), current($potenceAfter));
		$this->assertTrue($potenceBefore != $potenceAfter);
	}
	
	public function test_potenceTransfer()
	{
		$srcItemId = 100029;
		$desItemId = 100030;
		$srcItemId = ItemManager::getInstance()->addItem($srcItemId, 1);
		$srcItemId = current($srcItemId);
		$desItemId = ItemManager::getInstance()->addItem($desItemId, 1);
		$desItemId = current($desItemId);
		$bag = new Bag();
		$bag->addItem($srcItemId);
		$bag->addItem($desItemId);
		$bag->update();
		$srcItem = ItemManager::getInstance()->getItem($srcItemId);
		$srcItemText = $srcItem->getItemText();
		$srcPotence = $srcItemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE];
		Logger::debug("srcItemId:%d before potence transfer potenceId:%d num:%d", $srcItemId, key($srcPotence), current($srcPotence));
		$desItem = ItemManager::getInstance()->getItem($desItemId);
		$desItemText = $desItem->getItemText();
		$desPotence = $desItemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE];
		Logger::debug("desItemId:%d before potence transfer potenceId:%d num:%d", $desItemId, key($desPotence), current($desPotence));
		$ret = $this->forge->potenceTransfer($srcItemId, $desItemId, 1);
		$srcItemText = $srcItem->getItemText();
		$srcPotence = $srcItemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE];
		Logger::debug("srcItemId:%d after potence transfer potenceId:%d num:%d", $srcItemId, key($srcPotence), current($srcPotence));
		$desItemText = $desItem->getItemText();
		$desPotence = $desItemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE];
		Logger::debug("desItemId:%d after potence transfer potenceId:%d num:%d", $desItemId, key($desPotence), current($desPotence));
		
		print_r($ret);
	}
*/
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */