<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BarnShop.class.php 143314 2014-11-29 13:03:53Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/barnshop/BarnShop.class.php $
 * @author $Author: wuqilin $(tianming@babeltime.com)
 * @date $Date: 2014-11-29 13:03:53 +0000 (Sat, 29 Nov 2014) $
 * @version $Revision: 143314 $
 * @brief 
 *  
 **/
class BarnShop extends Mall implements IBarnShop
{
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		$uid = RPCContext::getInstance()->getUid();
		parent::__construct($uid, MallDef::MALL_TYPE_BARN);

		if (EnSwitch::isSwitchOpen(SwitchDef::GUILD) == false)
		{
			throw new FakeException('user:%d does not open the barn shop', $uid);
		}
		$guildId = GuildMemberObj::getInstance($uid)->getGuildId();
		$guild = GuildObj::getInstance($guildId);
		$conf = btstore_get()->GUILD_BARN;
		if (!$guild->isGuildBarnOpen())
		{
			throw new FakeException('guild:%d info:%s is not reach barn open level:%s.barn not open.', 
			        $guildId, $guild->getInfo(), $conf[GuildDef::GUILD_BARN_OPEN]);
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see IBarnShop::getShopInfo()
	 */
	public function getShopInfo()
	{
		Logger::trace('BarnShop::getShopInfo Start.');

		$ret = $this->getInfo();

		Logger::trace('BarnShop::getShopInfo End.');

		return $ret;
	}

	/**
	 * (non-PHPdoc)
	 * @see IBarnShop::buy()
	 */
	public function buy($goodsId, $num)
	{
		Logger::trace('BarnShop::buy Start.');
		
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('Err para, goodsId:%d num:%d', $goodsId, $num);
		}
		if (empty(btstore_get()->GUILD_BARN_GOODS[$goodsId]))
		{
			throw new FakeException('The goods is not existed, goodsId:%d', $goodsId);
		}
		
		//检查粮仓等级
		$guildId = GuildMemberObj::getInstance($this->uid)->getGuildId();
		$barnLevel = GuildObj::getInstance($guildId)->getBuildLevel(GuildDef::BARN);
		$needLevel = btstore_get()->GUILD_BARN_GOODS[$goodsId][GuildDef::GUILD_BARN_LEVEL];
		if ($needLevel > $barnLevel) 
		{
			throw new FakeException('user:%d can not buy goodsId:%d. level:%d, needLv:%d', $this->uid, $goodsId, $barnLevel, $needLevel);
		}

		$this->exchange($goodsId, $num);
		GuildMemberObj::getInstance($this->uid)->update();

		Logger::trace('BarnShop::buy End.');

		return 'ok';
	}

	/**
	 * (non-PHPdoc)
	 * @see Mall::getExchangeConf()
	 */
	public function getExchangeConf($goodsId)
	{
		Logger::trace('BarnShop::getExchangeConf Start.');

		if (empty(btstore_get()->GUILD_BARN_GOODS[$goodsId]))
		{
			Logger::warning('The goods is not existed, goodsId:%d', $goodsId);
			return array();
		}

		$ret = btstore_get()->GUILD_BARN_GOODS[$goodsId];

		Logger::trace('BarnShop::getExchangeConf End.');

		return $ret;
	}

	/**
	 * (non-PHPdoc)
	 * @see Mall::subExtra()
	 */
	public function subExtra($goodsId, $num)
	{
		Logger::trace('BarnShop::subExtra Start.');

		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('Err para, goodsId:%d num:%d!', $goodsId, $num);
		}
		//检查商品是否存在
		if (empty(btstore_get()->GUILD_BARN_GOODS[$goodsId]))
		{
			throw new FakeException('The goods is not existed, goodsId:%d', $goodsId);
		}
		$goodsConf = btstore_get()->GUILD_BARN_GOODS[$goodsId];
		$extraReq = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA];
		if ( empty($extraReq[GuildDef::GUILD_BARN_SHOP_GRAIN]) 
			&& empty($extraReq[GuildDef::GUILD_BARN_SHOP_MERIT])  )
		{
			throw new ConfigException('no grain and merit in req');
		}
		
		if ( !empty($extraReq[GuildDef::GUILD_BARN_SHOP_GRAIN]) )
		{
			$subGrain = $extraReq[GuildDef::GUILD_BARN_SHOP_GRAIN] * $num;
			$ret = GuildMemberObj::getInstance($this->uid)->subGrainNum($subGrain);
			if ( !$ret )
			{
				throw new FakeException('no enough grain');
			}
		}
		
		if ( !empty($extraReq[GuildDef::GUILD_BARN_SHOP_MERIT]) )
		{
			$subMerit = $extraReq[GuildDef::GUILD_BARN_SHOP_MERIT] * $num;
			$ret = GuildMemberObj::getInstance($this->uid)->subMeritNum($subMerit);
			if ( !$ret )
			{
				throw new FakeException('no enough merit');
			}
		}

		Logger::trace('BarnShop::subExtra End.');

		return true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::addExtra()
	 */
	public function addExtra($goodsId, $num)
	{
		Logger::trace('BarnShop::addExtra Start.');
	
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('Err para, goodsId:%d num:%d!', $goodsId, $num);
		}
		//检查商品是否存在
		if (empty(btstore_get()->GUILD_BARN_GOODS[$goodsId]))
		{
			throw new FakeException('The goods is not existed, goodsId:%d', $goodsId);
		}
		
		$goodsConf = btstore_get()->GUILD_BARN_GOODS[$goodsId];
		$extraAcq = $goodsConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_EXTRA];
		
		if ( isset($extraAcq[GuildDef::GUILD_BARN_SHOP_GRAIN]) )
		{
			$addGrain = $extraAcq[GuildDef::GUILD_BARN_SHOP_GRAIN] * $num;
			GuildMemberObj::getInstance($this->uid)->addGrainNum($addGrain);
		}
	
		Logger::trace('BarnShop::addExtra End.');
	
		return true;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */