<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ShopLogic.class.php 242002 2016-05-11 03:32:11Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/shop/ShopLogic.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-05-11 03:32:11 +0000 (Wed, 11 May 2016) $
 * @version $Revision: 242002 $
 * @brief 
 *  
 **/
class ShopLogic
{
	/**
	 * 用户数据初始化
	 * 
	 * @param int $uid									用户id
	 * @return array $arrField							用户信息
	 */
	public static function initShop($uid)
	{
		Logger::trace('ShopLogic::initShop Start.');
		
		$user = EnUser::getUserObj($uid);
		$createTime = $user->getCreateTime();
		$time = Util::getTime();
		//银将延迟时间
		$silverRecruitTime = $time;
// 		if ($time - $createTime <= ShopDef::SILVER_RECRUIT_DELAY)
// 		{
// 			$silverRecruitTime = $createTime + ShopDef::SILVER_RECRUIT_DELAY;
// 		}
		//金将延迟时间
		$goldRecruitTime = $time;
		if ($time - $createTime <= ShopDef::GOLD_RECRUIT_DELAY) 
		{
			$goldRecruitTime = $createTime + ShopDef::GOLD_RECRUIT_DELAY;
		}
		
		$arrField = array(
				ShopDef::USER_ID => $uid,
				ShopDef::POINT => 0,
				ShopDef::BRONZE_RECRUIT_NUM => 0,
			  	ShopDef::SILVER_RECRUIT_NUM => 0,
			  	ShopDef::SILVER_RECRUIT_TIME => $silverRecruitTime,
			  	ShopDef::SILVER_RECRUIT_STATUS => ShopDef::NO_FREE_GOLD,
			  	ShopDef::GOLD_RECRUIT_NUM => 0,
			  	ShopDef::GOLD_RECRUIT_TIME => $goldRecruitTime,
			  	ShopDef::GOLD_RECRUIT_STATUS => ShopDef::NO_FREE_GOLD,
			  	ShopDef::VA_SHOP => array()			
		);
		ShopDao::insert($arrField);
		
		Logger::trace('ShopLogic::initShop End.');
		return $arrField;
	}
	
	/**
	 * 获取用户的vip领奖信息
	 *
	 * @param int $uid									用户id
	 * @throws FakeException
	 * @return array $vipInfo 							领奖信息
	 */
	public static function getShopInfo($uid)
	{
		Logger::trace('ShopLogic::getShopInfo Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::SHOP) == false)
		{
			throw new FakeException('user:%d does not open the shop', $uid);
		}
	
		//读数据库：获取用户信息
		$info = ShopDao::select($uid);
		if ($info == false)
		{
			//初始化用户数据
			$info = self::initShop($uid);
		}
		
		$time = Util::getTime();
		
		//根据冷却时间是否到达，重置招将冷却时间
		$info[ShopDef::SILVER_RECRUIT_NUM] = 0;
		if ($info[ShopDef::SILVER_RECRUIT_TIME] <= $time)
		{
			$info[ShopDef::SILVER_RECRUIT_TIME] = $time;
			$info[ShopDef::SILVER_RECRUIT_NUM] = 1;
		}
		$info[ShopDef::SILVER_RECRUIT_TIME] -= $time;
		
		$info['gold_recruit_sum'] = $info[ShopDef::GOLD_RECRUIT_NUM];
		$info[ShopDef::GOLD_RECRUIT_NUM] = 0;
		if ($info[ShopDef::GOLD_RECRUIT_TIME] <= $time)
		{
			$info[ShopDef::GOLD_RECRUIT_TIME] = $time;
			$info[ShopDef::GOLD_RECRUIT_NUM] = 1;
		}
		$info[ShopDef::GOLD_RECRUIT_TIME] -= $time;
		
		//处理用户购买vip礼包信息
		$vipGiftInfo = array();
		if (!empty($info[ShopDef::VA_SHOP][ShopDef::VIP_GIFT])) 
		{
			//获取用户vip等级
			$userVip = EnUser::getUserObj($uid)->getVip();
			for ($i = 0; $i <= $userVip; $i++)
			{
				if (in_array($i, $info[ShopDef::VA_SHOP][ShopDef::VIP_GIFT]) == true)
				{
					$vipGiftInfo[$i] = 1;
				}
				else
				{
					$vipGiftInfo[$i] = 0;
				}
			}
		}
		$info[ShopDef::VA_SHOP][ShopDef::VIP_GIFT] = $vipGiftInfo;
	
		Logger::trace('ShopLogic::getShopInfo End.');
		return $info;
	}
	
	/**
	 * 青铜招将
	 * 只能用物品招将
	 * 无免费次数和金币招将
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @throws ConfigException
	 * @return array 
	 * <code>
	 * {
	 * 		'hero'
	 * 		{
	 * 			$hid => $htid				掉落的武将信息		
	 * 		}
	 * 		'star'
	 * 		{	
	 * 			$sid => $stid				掉落的名将信息
	 * 		}
	 * }
	 * </code>
	 */
	public static function bronzeRecruit($uid)
	{
		Logger::trace('ShopLogic::bronzeRecruit Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::SHOP) == false)
		{
			throw new FakeException('user:%d does not open the shop', $uid);
		}
		//读青铜配置
		$conf = btstore_get()->SHOP[ShopDef::RECRUIT_TYPE_BRONZE]->toArray();
		
		//检查用户是否有青铜招将所需物品
		$bag = BagManager::getInstance()->getBag();
		$items = $conf[ShopDef::RECRUIT_COST_ITEM];
		if ($bag->deleteItemsbyTemplateID($items) == false) 
		{
			throw new FakeException('user:%d does not have the items:%s', $uid, $items);
		}
		
		//读数据库：获取用户信息
		$info = ShopDao::select($uid);
		if ($info == false)
		{
			//初始化用户数据
			$info = self::initShop($uid);
		}
		
		$useNum = $info[ShopDef::BRONZE_RECRUIT_NUM] + 1;
		$arrDropId = $conf[ShopDef::RECRUIT_DEFAULT_GOLD];
		$arrSpecialNum = $conf[ShopDef::RECRUIT_SPECIAL_NUM];
		$arrSpecialDropId = $conf[ShopDef::RECRUIT_SPECIAL_DROP];
		$arrSpecialSerial = $conf[ShopDef::RECRUIT_SPECIAL_SERIAL];
		if (!empty($arrSpecialNum))
		{
			if(self::inSpecialSerial($useNum, $arrSpecialNum) == true)
			{
				$arrDropId = $arrSpecialDropId;
			}
		}
		if (!empty($arrSpecialSerial))
		{
			if(self::inSpecialSerial($useNum, $arrSpecialSerial) == true)
			{
				$arrDropId = $arrSpecialDropId;
			}
		}
		$htid = self::randFiveStarHero($uid, $arrDropId);
		
		//给用户发武将
		$user = EnUser::getUserObj($uid);		
		$ret = $user->getHeroManager()->addNewHeroWithStar($htid);	
		$hid = key($ret['hero']);
		$starLevel = $user->getHeroManager()->getHeroObj($hid)->getHeroConf($htid, CreatureAttr::STAR_LEVEL);
		Logger::info('uid:%d, type:bronze_recruit, level:%d, htid:%d', $uid, $starLevel, $htid);
		ChatTemplate::sendRecruitHero($user->getTemplateUserInfo(), array($htid => 1), ShopDef::RECRUIT_TYPE_BRONZE, false);
		
		//给用户加武魂, 武魂背包没有限制
		$items = array();
		$extraDropId = $conf[ShopDef::RECRUIT_EXTRA_DROP];
		if (!empty($extraDropId)) 
		{
			$items = Drop::dropItem($extraDropId);
		}
		
		//活动期间额外掉落
		$extraDropArr = EnActExchange::getDropForSdcj();
		if(!empty($extraDropArr[ShopDef::RECRUIT_TYPE_BRONZE - 1]))
		{
			$newItems = Drop::dropMixed($extraDropArr[ShopDef::RECRUIT_TYPE_BRONZE - 1]);
			$items = Util::arrayAdd2V(array($items, $newItems[DropDef::DROP_TYPE_ITEM]));
		}
		if ($bag->addItemsByTemplateID($items, true) == false) 
		{
			throw new FakeException('bag is full, items:%s', $items);
		}
		$ret['item'] = $items;
		
		//更新到数据库
		$arrField = array();
		$basePoint = $conf[ShopDef::RECRUIT_POINT_BASE];
		if (!empty($basePoint)) 
		{
			$arrField[ShopDef::POINT] = $info[ShopDef::POINT] + $basePoint;
		}
		$arrField[ShopDef::BRONZE_RECRUIT_NUM] = $useNum;
		ShopDao::update($uid, $arrField);

		$user->update();
		$bag->update();
		
		EnActive::addTask(ActiveDef::RECRUIT);
		
		Logger::trace('ShopLogic::bronzeRecruit End.');

		return $ret;
	}
	
	
	/**
	 * 酒馆招将
	 * 白银和黄金招将类型
	 * 有免费次数和金币招将
	 *
	 * @param int $uid						用户id
	 * @param string $type					招将类型
	 * @param int $isCost					是否使用金币招将
	 * @param int $num						招将次数,1或10
	 * @throws FakeException
	 * @return array 
	 * <code>
	 * {
	 * 		'hero'
	 * 		{
	 * 			$hid => $htid				掉落的武将信息		
	 * 		}
	 * 		'star'
	 * 		{	
	 * 			$sid => $stid				掉落的名将信息
	 * 		}
	 * }
	 * </code>
	 */
	public static function recruit($uid, $type, $isCost, $num)
	{
		Logger::trace('ShopLogic::recruit Start, type:%d.', $type);
		
		if (EnSwitch::isSwitchOpen(SwitchDef::SHOP) == false)
		{
			throw new FakeException('user:%d does not open the shop', $uid);
		}
		
		//读数据库：获取用户信息
		$info = ShopDao::select($uid);
		if ($info == false)
		{
			//初始化用户数据
			$info = self::initShop($uid);
		}
		
		//根据招将类型, 获取用户可以使用的招将次数和使用时间。
		$dataType = ShopDef::$RECRUIT_VALID_TYPES[$type];
		$usePoint = $info[ShopDef::POINT];
		$useNum = $info[$dataType . ShopDef::NUM];
		$useTime = $info[$dataType . ShopDef::TIME];
		$useStatus = $info[$dataType . ShopDef::STATUS];
		
		//读配置表
		$conf = btstore_get()->SHOP[$type]->toArray();
		$cdTime = $conf[ShopDef::RECRUIT_CD_TIME];
		$basePoint = $conf[ShopDef::RECRUIT_POINT_BASE];
		$arrGoldDropId = $conf[ShopDef::RECRUIT_GOLD_DROP];
		$arrFreeDropId = $conf[ShopDef::RECRUIT_FREE_DROP];
		$arrDefaultGoldDropId = $conf[ShopDef::RECRUIT_DEFAULT_GOLD];
		$arrDefaultFreeDropId = $conf[ShopDef::RECRUIT_DEFAULT_FREE];
		$arrSpecialNum = $conf[ShopDef::RECRUIT_SPECIAL_NUM];
		$arrSpecialDropId = $conf[ShopDef::RECRUIT_SPECIAL_DROP];
		$arrSpecialSerial = $conf[ShopDef::RECRUIT_SPECIAL_SERIAL];
		$cost = $conf[ShopDef::RECRUIT_COST_GOLD] * $num;
		$costType = StatisticsDef::ST_FUNCKEY_SHOP_GOLD_RECRUIT_ONE;
		if (isset($conf[ShopDef::RECRUIT_MULTI_COST][$num]))
		{
			$cost = $conf[ShopDef::RECRUIT_MULTI_COST][$num];
			$costType = StatisticsDef::ST_FUNCKEY_SHOP_GOLD_RECRUIT_TEN;
		}
		
		if (ShopDef::RECRUIT_TYPE_SILVER == $type) 
		{
			$cdTime -= EnUnion::getAddFuncByUnion($uid, UnionDef::TYPE_SHOP_SRECRUIT_SUBCD) * 60;
		}
		if (ShopDef::RECRUIT_TYPE_GOLD == $type) 
		{
			$cdTime -= EnUnion::getAddFuncByUnion($uid, UnionDef::TYPE_SHOP_GRECRUIT_SUBCD) * 60;
		}
		
		$arrField = array();
		$arrDropId = array();
		$time = Util::getTime();
		$user = EnUser::getUserObj($uid);
		Logger::trace('now time is :%d', $time);
		//满足条件：冷却时间已结束并且招将次数等于1, 优先使用免费招将
		if ($useTime <= $time && $num == 1)
		{
			//如果使用金币招将则抛异常
			if ($isCost == true)
			{
				throw new FakeException('Recruit free num is not over, wrong to use gold recruit!');
			}
			switch ($useStatus)
			{
				case ShopDef::NO_FREE_GOLD:
				case ShopDef::GOLD_NO_FREE:
					//免费首刷
					$arrDropId[0] = $arrFreeDropId;
					$useStatus += 1;
					break;
				case ShopDef::FREE_NO_GOLD:
				case ShopDef::FREE_GOLD_NO:
					$arrDropId[0] = $arrDefaultFreeDropId;
					break;
				default:
					throw new InterException('wrong recruit status:%d!', $useStatus);
			}
			$useNum++;
			if (!empty($arrSpecialNum))
			{
				if(self::inSpecialSerial($useNum, $arrSpecialNum) == true)
				{
					$arrDropId[0] = $arrSpecialDropId;
				}
			}
			if (!empty($arrSpecialSerial)) 
			{
				if(self::inSpecialSerial($useNum, $arrSpecialSerial) == true)
				{
					$arrDropId[0] = $arrSpecialDropId;
				}
			}
			$arrField[$dataType . ShopDef::TIME] = $time + $cdTime;
		}
		else //1.还有免费次数，但使用十连抽;2.没有免费次数，使用1次金币招将或十连抽, 1和2都是金币招将
		{
			//如果未用金币招将，则抛出异常
			if (!$isCost)
			{
				throw new FakeException('Recruit is wrong, without using gold recruit!');
			}
			//扣金币
			if ($user->subGold($cost, $costType) == false)
			{
				throw new FakeException('user:%d has not enough gold:%d for recuit!', $uid, $cost);
			}
			for ($i = 0; $i < $num; $i++)
			{
				switch ($useStatus)
				{
					case ShopDef::NO_FREE_GOLD:
					case ShopDef::FREE_NO_GOLD:
						//金币首刷
						$arrDropId[$i] = $arrGoldDropId;
						$useStatus += 2;
						break;
					case ShopDef::GOLD_NO_FREE:
					case ShopDef::FREE_GOLD_NO:
						$arrDropId[$i] = $arrDefaultGoldDropId;
						break;
					default:
						throw new InterException('wrong recruit status:%d!', $useStatus);
				}
				$useNum++;
				if ($arrDropId[$i] != $arrGoldDropId 
				&& !empty($arrSpecialNum))
				{
					if(self::inSpecialSerial($useNum, $arrSpecialNum) == true)
					{
						$arrDropId[$i] = $arrSpecialDropId;
					}
				}
				if ($arrDropId[$i] != $arrGoldDropId 
				&& !empty($arrSpecialSerial))
				{
					if(self::inSpecialSerial($useNum, $arrSpecialSerial) == true)
					{
						$arrDropId[$i] = $arrSpecialDropId;
					}
				}
			}
		}
		Logger::trace('arr drop id is %s', $arrDropId);
		
		//开始循环掉落
		$arrRet = array('hero' => array(), 'star' => array(), 'item' => array());
		foreach ($arrDropId as $dropId)
		{
			$htid = self::randFiveStarHero($uid, $dropId);
			$ret = $user->getHeroManager()->addNewHeroWithStar($htid);
			$hid = key($ret['hero']);
			$starLevel = $user->getHeroManager()->getHeroObj($hid)->getHeroConf($htid, CreatureAttr::STAR_LEVEL);
			Logger::info('uid:%d, type:%s, level:%d, htid:%d', $uid, $dataType, $starLevel, $htid);
			//活动期间额外掉落
			$items = array();
			$extraDropArr = EnActExchange::getDropForSdcj();
			if(!empty($extraDropArr[$type - 1]))
			{
				$items = Drop::dropMixed($extraDropArr[$type - 1]);
				$items = $items[DropDef::DROP_TYPE_ITEM];
			}
			$hero = Util::arrayMerge(array($arrRet['hero'], $ret['hero']));
			$star = Util::arrayMerge(array($arrRet['star'], $ret['star']));
			$item = Util::arrayAdd2V(array($arrRet['item'], $items));
			$arrRet = array('hero' => $hero, 'star' => $star, 'item' => $item);
		}
		//这样日志有两个功能：1.统计招将获得的名将，2.统计10连抽
		Logger::info('uid:%d, type:%s, level:%d, htid:%d, num:%d', $uid, $dataType, $starLevel, $htid, $num);
		
		//加到背包
		$bag = BagManager::getInstance()->getBag($uid);
		if ($bag->addItemsByTemplateID($arrRet['item'], true) == false)
		{
			throw new FakeException('bag is full, items:%s', $arrRet['item']);
		}
		
		$heroArr = array_count_values($arrRet['hero']);
		$isTenrecruit = ($num == 10 ? true : false);
		ChatTemplate::sendRecruitHero($user->getTemplateUserInfo(), $heroArr, $type, $isTenrecruit);
		
		//更新用户数据
		$arrField[ShopDef::POINT] = $usePoint + $basePoint * $num;
		$arrField[$dataType . ShopDef::NUM] = $useNum;
		$arrField[$dataType . ShopDef::STATUS] = $useStatus;
		
		//临时活动：霸气惊喜十连抽
		$eventBeginTime = 1388505600;
		$eventEndTime = 1388851200;
		//记录十连抽的时间
		if ($isTenrecruit && $time >= $eventBeginTime && $time <= $eventEndTime) 
		{
			$arrField[ShopDef::VA_SHOP] = $info[ShopDef::VA_SHOP];
			$arrField[ShopDef::VA_SHOP][ShopDef::TEN] = $time;
		}
		ShopDao::update($uid, $arrField);
		$user->update();
		$bag->update();
		
		EnActive::addTask(ActiveDef::RECRUIT, $num);
		if (ShopDef::RECRUIT_TYPE_GOLD == $type)
		{
			EnNewServerActivity::updateGoldRecruit($uid, $useNum);
		}
		
		
		Logger::trace('ShopLogic::recruit End, type:%d.', $type);

		return $arrRet;
	}
	
	/**
	 * 购买vip礼包
	 *
	 * @param int $uid									用户id
	 * @param int $vip									vip等级的礼包
	 * @throws FakeException
	 * @return ok
	 */
	public static function buyVipGift($uid, $vip)
	{
		Logger::trace('ShopLogic::buyVipGift Start.');
	
		if (EnSwitch::isSwitchOpen(SwitchDef::SHOP) == false)
		{
			throw new FakeException('user:%d does not open the shop', $uid);
		}
	
		//获取用户vip等级
		$user = EnUser::getUserObj($uid);
		$userVip= $user->getVip();
	
		//vip等级不够
		if ($userVip < $vip)
		{
			throw new FakeException('The vip level:%d of user:%d is not enough to buy gift of vip:%d', $userVip, $uid, $vip);
		}
	
		//是否有相应的vip礼包
		if (empty(btstore_get()->VIP[$vip]['vipGift']))
		{
			throw new FakeException('There is no gift of vip:%d', $vip);
		}
	
		//读数据库：获取用户信息
		$info = ShopDao::select($uid);
		if ($info == false)
		{
			//初始化用户数据
			$info = self::initShop($uid);
		}
	
		$vipGiftInfo = array();
		if (!empty($info[ShopDef::VA_SHOP][ShopDef::VIP_GIFT]))
		{
			$vipGiftInfo = $info[ShopDef::VA_SHOP][ShopDef::VIP_GIFT];
		}
	
		if (in_array($vip, $vipGiftInfo))
		{
			throw new FakeException('The user:%d has taken the gift of vip:%d already', $uid, $vip);
		}
	
		//获取相应礼包的物品id和花费金币
		$giftInfo = btstore_get()->VIP[$vip]['vipGift'];
		$itemTplId = $giftInfo[0];
		if (!isset(btstore_get()->ITEMS[$itemTplId]))
		{
			throw new ConfigException('the vip:%d gift item template id:%d is not exist!', $vip, $itemTplId);
		}
	
		$cost = $giftInfo[1];
		if ($user->subGold($cost, StatisticsDef::ST_FUNCKEY_SHOP_VIP_GIFT) == false)
		{
			throw new FakeException('The user:%d has not enough gold:%d to buy the gift of vip:%d', $uid, $cost, $vip);
		}
	
		$bag = BagManager::getInstance()->getBag();
		if ($bag->addItemByTemplateID($itemTplId, 1) == false)
		{
			//如果背包满了
			throw new FakeException('Bag is full, item tpl id:%d!', $itemTplId);
		}
	
		$user->update();
		$bag->update();
	
		//更新用户数据
		$vipGiftInfo[] = $vip;
		$info[ShopDef::VA_SHOP][ShopDef::VIP_GIFT] = $vipGiftInfo;
		//更新到数据库
		ShopDao::update($uid, array(ShopDef::VA_SHOP => $info[ShopDef::VA_SHOP]));
	
		Logger::trace('ShopLogic::buyVipGift End.');
	
		return 'ok';
	}
	
	/**
	 * 判断用户积分是否足够
	 * 
	 * @param unknown $uid
	 * @param unknown $goodsId
	 * @param unknown $num
	 * @throws FakeException
	 * @return boolean
	 */
	public static function isPointEnough($uid, $goodsId, $num)
	{
		Logger::trace('ShopLogic::isPointEnough Start.');
			
		//检查商品是否存在
		if (!isset(btstore_get()->SHOP_EXCHANGE[$goodsId]))
		{
			throw new FakeException('The goods is not existed, goodsId:%d', $goodsId);
		}
	
		$goodsConf = btstore_get()->SHOP_EXCHANGE[$goodsId];
		$costPoint = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA];
	
		//读数据库：获取用户信息
		$info = ShopDao::select($uid);
		if ($info == false)
		{
			return false;
		}
		if ($info[ShopDef::POINT] < $costPoint * $num)
		{
			return false;
		}
		return true;
	}
	
	/**
	 * 减积分
	 * 
	 * @param unknown $uid
	 * @param unknown $goodsId
	 * @param unknown $num
	 * @throws FakeException
	 * @return string
	 */
	public static function subUserPoint($uid, $goodsId, $num)
	{
		Logger::trace('ShopLogic::subUserPoint Start.');
			
		//检查商品是否存在
		if (!isset(btstore_get()->SHOP_EXCHANGE[$goodsId]))
		{
			throw new FakeException('The goods is not existed, goodsId:%d', $goodsId);
		}
		
		$goodsConf = btstore_get()->SHOP_EXCHANGE[$goodsId];
		$costPoint = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA];
		
		//读数据库：获取用户信息
		$info = ShopDao::select($uid);
		if ($info == false)
		{
			throw new InterException('user:%d data is not exists in db!', $uid);
		}
		
		if ($info[ShopDef::POINT] < $costPoint * $num)
		{
			throw new FakeException('user:%d point is not enough for buy goodsId:%d', $uid, $goodsId);
		}
		
		//更新积分
		$arrField = array(
				ShopDef::POINT => $info[ShopDef::POINT] - $costPoint * $num,
		);
		ShopDao::update($uid, $arrField);
		
		Logger::trace('ShopLogic::subUserPoint End.');
		return 'ok';
	}
	
	/**
	 * 随机五星武将
	 * 
	 * @param unknown $uid
	 * @param unknown $arrDropId
	 * @throws ConfigException
	 * @return Ambigous <number, unknown, mixed>
	 */
	public static function randFiveStarHero($uid, $arrDropId)
	{
		Logger::trace('ShopLogic::randFiveStarHero Start.');
		
		$ret = 0;
		
		//如果不是掉落一个武将就抛异常
		$hero = Drop::dropMixed($arrDropId[0]);
		if (count($hero[DropDef::DROP_TYPE_HERO]) != 1 || current($hero[DropDef::DROP_TYPE_HERO]) != 1)
		{
			throw new ConfigException('dropId:%d drop hero num is not one', $arrDropId[0]);
		}
		$htid = key($hero[DropDef::DROP_TYPE_HERO]);
		
		//用户是否掉落五星武将
		if (HeroUtil::isFiveStarHero($htid) == false) 
		{
			$ret =  $htid;
		}
		else
		{
			//获取用户的五星武将
			$arrHero = HeroUtil::getHeroesWithFiveStar($uid, ShopDef::INIT_DISTINCT_HERO_NUM);
			//判断是否和用户前3个五星武将重复:
			//1.用户无五星武将
			//2.超过3个五星武将
			//3.和用户的五星武将不同
			if (empty($arrHero)
			|| count($arrHero) >= ShopDef::INIT_DISTINCT_HERO_NUM
			|| !in_array($htid, $arrHero)) 
			{
				$ret =  $htid;
			}
			else 
			{
				//否则，继续掉落，直到掉落不同的五星武将
				if (!isset($arrDropId[1])) 
				{
					throw new ConfigException('arrdropId:%s num is less than 2', $arrDropId);
				}
				$hero = Drop::dropMixed($arrDropId[1], $arrHero);
				if (empty($hero[DropDef::DROP_TYPE_HERO]))
				{
					throw new ConfigException('dropId:%d drop hero is the same with user hero array:%s', $arrDropId[1], $arrHero);
				}
				if (count($hero[DropDef::DROP_TYPE_HERO]) != 1 || current($hero[DropDef::DROP_TYPE_HERO]) != 1)
				{
					throw new ConfigException('dropId:%d drop hero num is not one', $arrDropId[1]);
				}
				if (HeroUtil::isFiveStarHero(key($hero[DropDef::DROP_TYPE_HERO])) == false)
				{
					throw new ConfigException('dropId:%d drop hero:%d is not five star hero', $arrDropId[1], key($hero[DropDef::DROP_TYPE_HERO]));
				}
				$htid = key($hero[DropDef::DROP_TYPE_HERO]);
				$ret =  $htid;
			}
		}
		
		Logger::trace('ShopLogic::randFiveStarHero End.');
		return $ret;
	}
	
	/**
	 * 判断一个数是否在一个特殊序列里
	 * 
	 * @param int $num
	 * @param array $serial
	 * @return boolean
	 */
	public static function inSpecialSerial($num, $serial)
	{
		Logger::trace('ShopLogic::inSpecialSerial Start.');
		Logger::trace('num:%d, serial:%s', $num, $serial);
		
		if (empty($num) || empty($serial)) 
		{
			return false;
		}
		
		if (in_array($num, $serial)) 
		{
			return true;
		}
		
		$count = count($serial);
		$sum = $serial[$count - 1];
		
		if ($num < $sum) 
		{
			return false;
		}
		
		if ($count < 2) 
		{
			$final = $sum;
		}
		else 
		{
			$final = $sum - $serial[$count - 2];
		}
		
		if (($num - $sum) % $final == 0)
		{
			return true;
		}
		
		Logger::trace('ShopLogic::inSpecialSerial End.');
		
		return false;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */