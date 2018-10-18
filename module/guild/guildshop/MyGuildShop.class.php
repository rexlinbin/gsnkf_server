<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyGuildShop.class.php 142163 2014-11-25 08:19:24Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/guildshop/MyGuildShop.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-11-25 08:19:24 +0000 (Tue, 25 Nov 2014) $
 * @version $Revision: 142163 $
 * @brief 
 *  
 **/
class MyGuildShop extends Mall
{
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		$uid = RPCContext::getInstance()->getUid();
	
		parent::__construct($uid, MallDef::MALL_TYPE_GUILD,
		StatisticsDef::ST_FUNCKEY_MALL_GUILD_COST,
		StatisticsDef::ST_FUNCKEY_MALL_GUILD_GET);
		
		$this->loadData();
		
		if (empty($this->dataModify))
		{
			$this->initData();
		}
		$this->refreshData();
	}
	
	public function initData()
	{
		$this->setAllList(array());
	}
	
	public function getAllList()
	{
		return $this->dataModify[MallDef::ALL];
	}
	
	public function setAllList($list)
	{
		$this->dataModify[MallDef::ALL] = $list;
	}
	
	/**
	 * 从所有购买的商品列表里获取指定类型的商品列表
	 */
	public function getListByType($type)
	{
		$list = $this->getAllList();
		Logger::trace('all list:%s', $list);
		$conf = btstore_get()->GUILD_GOODS;
		foreach ($list as $goodsId => $goodsInfo)
		{
			$goodsType = $conf[$goodsId][GuildDef::GUILD_GOODS_TYPE];
			unset($list[$goodsId][GuildDef::TIME]);
			if ($goodsType != $type)
			{
				unset($list[$goodsId]);
			}
		}
		Logger::trace('type:%d list:%s', $type, $list);
		 
		return $list;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::getExchangeConf()
	 */
	public function getExchangeConf($goodsId)
	{
		if (empty(btstore_get()->GUILD_GOODS[$goodsId]))
		{
			Logger::warning('not found id:%d', $goodsId);
			return array();
		}

		return btstore_get()->GUILD_GOODS[$goodsId];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::subExtra()
	 */
	public function subExtra($goodsId, $num)
	{
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('Err para, goodsId:%d num:%d!', $goodsId, $num);
		}
		
		if (empty(btstore_get()->GUILD_GOODS[$goodsId]))
		{
			throw new FakeException('The goods is not existed, goodsId:%d', $goodsId);
		}
		
		$goodsConf = btstore_get()->GUILD_GOODS[$goodsId];
		$subPoint = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA] * $num;
		return GuildMemberObj::getInstance($this->uid)->subContriPoint($subPoint);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */