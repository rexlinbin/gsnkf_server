<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildCopyShop.class.php 191950 2015-08-18 02:38:32Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildcopy/GuildCopyShop.class.php $
 * @author $Author: JiexinLin $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-08-18 02:38:32 +0000 (Tue, 18 Aug 2015) $
 * @version $Revision: 191950 $
 * @brief 
 *  
 **/
 
class GuildCopyShop extends Mall
{
	public function __construct($uid = 0)
	{
		if(empty($uid))
		{
			$uid = RPCContext::getInstance()->getUid();
		}
		parent::__construct($uid, MallDef::MALL_TYPE_ZGSHOP, StatisticsDef::ST_FUNCKEY_GUILD_COPY_SHOP_COST, StatisticsDef::ST_FUNCKEY_GUILD_COPY_SHOP_GET);
	
		if (!EnSwitch::isSwitchOpen(SwitchDef::GUILD))
		{
			throw new FakeException('user[%d] guild switch not open', $uid);
		}
	}
	
	public function getShopInfo()
	{
		return $this->getInfo();
	}
	
	public function buy($goodsId, $num)
	{		
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('invalid param, goodsId[%d], num[%d]', $goodsId, $num);
		}
		
		if (empty(btstore_get()->GUILD_COPY_GOODS[$goodsId]))
		{
			throw new FakeException('goods is not existed, goodsId[%d]', $goodsId);
		}
		
		// 检查是否在一个军团
		$guildMemberObj = GuildMemberObj::getInstance($this->uid);
		$guildId = $guildMemberObj->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('not in any guild of user[%d]', $this->uid);
		}
		
		// 检查军团副本等级
		$goodsConf = btstore_get()->GUILD_COPY_GOODS[$goodsId]->toArray();
		$needPassCopy = intval($goodsConf['copy']);
		$guildCopyObj = GuildCopyObj::getInstance($guildId);
		$currPassCopy = $guildCopyObj->getMaxPassCopy();
		if ($currPassCopy < $needPassCopy) 
		{
			throw new FakeException('goods[%d] need guild copy[%d], curr guild copy[%d]', $goodsId, $needPassCopy, $currPassCopy);
		}
		
		$this->exchange($goodsId, $num);
		
		return 'ok';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::update()
	 */
	public function update()
	{
		$this->updateData();
		GuildMemberObj::getInstance($this->uid)->update();   // 买商品获得的粮草或者减去的战功，都需要update这里
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::getExchangeConf()
	 */
	public function getExchangeConf($goodsId)
	{		
		if (empty(btstore_get()->GUILD_COPY_GOODS[$goodsId]))
		{
			Logger::warning('goods is not existed, goodsId[%d]', $goodsId);
			return array();
		}
		
		$ret = btstore_get()->GUILD_COPY_GOODS[$goodsId]->toArray();

		if (empty($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM]))
		{
			if (isset($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]))
			{
				$ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = 0;
				$user = EnUser::getUserObj($this->uid);
				foreach ($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM] as $key => $value)
				{
					if ($user->getLevel() >= $key)
					{
						$ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = $value;
					}
				}
				unset($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]);
			}
		}
		Logger::trace('GuildCopyShop::getExchangeConf End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::subExtra()
	 */
	public function subExtra($goodsId, $num)
	{		
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('invalid param, goodsId[%d], num[%d]', $goodsId, $num);
		}
		
		if (empty(btstore_get()->GUILD_COPY_GOODS[$goodsId]))
		{
			throw new FakeException('goods is not existed, goodsId[%d]', $goodsId);
		}
		
		$goodsConf = btstore_get()->GUILD_COPY_GOODS[$goodsId];
		$extraReq = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA];
		if (empty($extraReq['zg']))
		{
			throw new ConfigException('no zg in req');
		}
		
		$need = $extraReq['zg'] * $num;
		$guildMemberObj = GuildMemberObj::getInstance($this->uid);
		if (!$guildMemberObj->subZgNum($need))
		{
			throw new FakeException('no enough zg, need[%d], curr[%d]', $need, $guildMemberObj->getZgNum());
		}
		
		return TRUE;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::addExtra()
	 */
	public function addExtra($goodsId, $num)
	{	
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('invalid param, goodsId[%d] num[%d]', $goodsId, $num);
		}

		if (empty(btstore_get()->GUILD_COPY_GOODS[$goodsId]))
		{
			throw new FakeException('goods is not existed, goodsId[%d]', $goodsId);
		}
		
		$goodsConf = btstore_get()->GUILD_COPY_GOODS[$goodsId];
		$extraAcq = $goodsConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_EXTRA];
		if (isset($extraAcq[GuildDef::GUILD_BARN_SHOP_GRAIN]))
		{
			$addGrain = $extraAcq[GuildDef::GUILD_BARN_SHOP_GRAIN] * $num;
			GuildMemberObj::getInstance($this->uid)->addGrainNum($addGrain);
		}
	
		return TRUE;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */