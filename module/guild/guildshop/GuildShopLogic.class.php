<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildShopLogic.class.php 144079 2014-12-03 14:09:16Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/guildshop/GuildShopLogic.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-12-03 14:09:16 +0000 (Wed, 03 Dec 2014) $
 * @version $Revision: 144079 $
 * @brief 
 *  
 **/
class GuildShopLogic
{
	public static function getShopInfo($uid)
	{
		Logger::trace('GuildShopLogic::getShopInfo Start.');
		
		//获得军团共享类(包括普通和珍品)商品列表,刷新(珍品)商品列表和刷新冷却时间
		$guildId = GuildMemberObj::getInstance($uid)->getGuildId();
		$guild = GuildObj::getInstance($guildId);
		//刷新冷却时间已经到了
		if($guild->getRefreshCd() <= Util::getTime())
		{
			//刷新加锁
			try
			{
				$guild = GuildObj::getInstance($guildId, array(GuildDef::VA_INFO));
				$guild->refreshGoods();
				$guild->update(true);
			}
			catch(Exception $e)
			{
				$guild->unlockArrField();
				throw $e;
			}
		}
		$guildGoods = $guild->getGoods();
		$refreshList = $guild->getRefreshList();
		$refreshCd = $guild->getRefreshCd();
		
		//获得个人珍品商品列表，将个人购买次数合并到刷新列表上
		$myGuildShop = new MyGuildShop();
		$specialList = $myGuildShop->getListByType(GuildDef::SPECIAL);
		$specialList = self::combineList($refreshList, $specialList);
		Logger::trace('special list:%s', $specialList);
		
		//获得普通商品列表
		$normalList = $myGuildShop->getListByType(GuildDef::NORMAL);
		Logger::trace('normal list:%s', $normalList);
		
		//将军团共享类商品列表的军团购买次数合并到珍品商品列表和普通商品列表上去
		//合并规则：如果有军团购买次数则一定有个人购买次数
		$specialList = self::mergeList($specialList, $guildGoods, GuildDef::SPECIAL);
		$normalList = self::mergeList($normalList, $guildGoods, GuildDef::NORMAL);
		Logger::trace('special list:%s, normal list:%s after merge list:%s', $specialList, $normalList, $guildGoods);
		
		//补全其他所有的有军团购买次数上限的商品信息
		$specialGoods = self::fixList($specialList, GuildDef::SPECIAL);
		$normalGoods = self::fixList($normalList, GuildDef::NORMAL);
		Logger::trace('special goods:%s, normal goods:%s after fix', $specialGoods, $normalGoods);
		
		Logger::trace('GuildShopLogic::getShopInfo End.');
		
		return array(
				GuildDef::SPECIAL_GOODS => $specialGoods,
				GuildDef::NORMAL_GOODS => $normalGoods,
				GuildDef::REFRESH_CD => $refreshCd,
		);
	}
	
	public static function buy($uid, $goodsId, $num)
	{
		Logger::trace('GuildShopLogic::buy Start.');
		
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		
		try
		{
			$guild = GuildObj::getInstance($guildId, array(GuildDef::VA_INFO));
			if ($guild->canBuy($goodsId, $num) == false) 
			{
				throw new FakeException('goodsId:%d can not buy num:%d', $goodsId, $num);
			}
			$myGuildShop = new MyGuildShop();
			$myGuildShop->exchange($goodsId, $num);
			$guild->addGoodsSum($goodsId, $num);
			$guild->update();
		}
		catch (Exception $e)
		{
			$guild->unlockArrField();
			throw $e;
		}
		
		$member->update();
		$myGuildShop->update();
		
		Logger::trace('GuildShopLogic::buy End.');
		
		return 'ok';
	}
	
	public static function refreshList($uid)
	{
		Logger::trace('GuildShopLogic::refreshList Start.');
		
		$guildId = GuildMemberObj::getInstance($uid)->getGuildId();
		try
		{
			//是否到了刷新时间,刷新加锁
			$guild = GuildObj::getInstance($guildId, array(GuildDef::VA_INFO));
			if ($guild->getRefreshCd() > Util::getTime())
			{
				throw new FakeException('refresh time is not arrive!');
			}
			$guild->refreshGoods();
			$guild->update(true);
		}
		catch(Exception $e)
		{
			$guild->unlockArrField();
			throw $e;
		}
		$guildGoods = $guild->getGoods();
		$refreshList = $guild->getRefreshList();
		$refreshCd = $guild->getRefreshCd();
		
		//获得个人珍品商品列表，将个人购买次数合并到刷新列表上
		$myGuildShop = new MyGuildShop();
		$specialList = $myGuildShop->getListByType(GuildDef::SPECIAL);
		$specialList = self::combineList($refreshList, $specialList);
		Logger::trace('special list:%s', $specialList);
		
		//将军团共享类商品列表的军团购买次数合并到珍品商品列表上去
		//合并规则：如果有军团购买次数则一定有个人购买次数
		$specialList = self::mergeList($specialList, $guildGoods, GuildDef::SPECIAL);
		Logger::trace('special list:%s after merge list:%s', $specialList, $guildGoods);
		//补全其他所有的有军团购买次数上限的商品信息
		$specialGoods = self::fixList($specialList, GuildDef::SPECIAL);
		Logger::trace('special goods:%s after fix', $specialGoods);
		
		Logger::trace('GuildShopLogic::refreshList End.');
		
		return array(
				GuildDef::SPECIAL_GOODS => $specialGoods,
				GuildDef::REFRESH_CD => $refreshCd,
		);
	}
	
	/**
	 * 合并个人购买信息到刷新列表上
	 *
	 * @param array $aList
	 * @param array $bList
	 * @return array $ret
	 */
	private static function combineList($aList, $bList)
	{
		$ret = array();
		foreach ($aList as $goodsId)
		{
			$ret[$goodsId] = array();
			if (isset($bList[$goodsId]))
			{
				$ret[$goodsId] = $bList[$goodsId];
			}
		}
		return $ret;
	}
	
	/**
	 * 根据$aList的类型，将$bList合并到$aList上
	 * 
	 * @param array $aList
	 * @param array $bList
	 * @param int $type
	 */
	public static function mergeList($aList, $bList, $type)
	{
		Logger::trace('GuildShopLogic::mergeList Start.');
		
		$conf = btstore_get()->GUILD_GOODS;
		foreach ($bList as $goodsId => $goodsInfo)
		{
			$goodsType = $conf[$goodsId][GuildDef::GUILD_GOODS_TYPE];
			if ($goodsType == $type) 
			{
				if ($goodsType == GuildDef::SPECIAL && isset($aList[$goodsId])
				|| $goodsType == GuildDef::NORMAL) 
				{
					$aList[$goodsId][GuildDef::SUM] = $goodsInfo[GuildDef::SUM];
					if (!isset($aList[$goodsId][GuildDef::NUM]))
					{
						$aList[$goodsId][GuildDef::NUM] = 0;
					}
				}
			}
		}
		
		Logger::trace('GuildShopLogic::mergeList End.');
		
		return $aList;
	}
	
	public static function fixList($list, $type)
	{
		Logger::trace('GuildShopLogic::fixList Start.');
		
		//遍历一遍所有配置表中的军团共享类商品
		$conf = btstore_get()->GUILD_GOODS;
		foreach ($conf as $goodsId => $goodsConf)
		{
			$exchangeType = $goodsConf[MallDef::MALL_EXCHANGE_TYPE];
			if (GuildDef::REFRESH_EVERYDAY == $exchangeType
			|| GuildDef::REFRESH_NERVER == $exchangeType)
			{
				$goodsType = $goodsConf[GuildDef::GUILD_GOODS_TYPE];
				if ($goodsType == $type) 
				{
					if (isset($list[$goodsId]))
					{
						if (!isset($list[$goodsId][GuildDef::SUM]))
						{
							$list[$goodsId][GuildDef::SUM] = 0;
						}
						if (!isset($list[$goodsId][GuildDef::NUM]))
						{
							$list[$goodsId][GuildDef::NUM] = 0;
						}
					}
					if (!isset($list[$goodsId]) && $type == GuildDef::NORMAL)
					{
						$list[$goodsId][GuildDef::SUM] = 0;
						$list[$goodsId][GuildDef::NUM] = 0;
					}
				}
			}
		}
		
		Logger::trace('GuildShopLogic::fixList End.');
		
		return $list;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */