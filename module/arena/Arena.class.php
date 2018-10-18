<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Arena.class.php 202038 2015-10-14 03:49:38Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/arena/Arena.class.php $
 * @author $Author: MingTian $(lanhongyu@babeltime.com)
 * @date $Date: 2015-10-14 03:49:38 +0000 (Wed, 14 Oct 2015) $
 * @version $Revision: 202038 $
 * @brief 
 *  
 **/

/**********************************************************************************************************************
 * Class       : Arena
 * Description : 竞技场系统对外接口实现类
 * Inherit     : IArena
 **********************************************************************************************************************/

class Arena extends Mall implements IArena
{	
    /**
     * 构造函数
     */
    public function __construct()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	parent::__construct($uid, MallDef::MALL_TYPE_ARENA,
		StatisticsDef::ST_FUNCKEY_MALL_ARENA_COST,
		StatisticsDef::ST_FUNCKEY_MALL_ARENA_GET);
    }

    /* (non-PHPdoc)
     * @see IArena::getArenaInfo()
    */
    public function getArenaInfo()
    {
    	Logger::trace('Arena::getArenaInfo Start.');
    
    	//判断用户是否在竞技场，在的时候用户数据发生变化向用户推数据
    	RPCContext::getInstance()->setSession('global.arenaId', SPECIAL_ARENA_ID::ARENA);
    	$arrRet = ArenaLogic::getArenaInfo($this->uid);
    	$arrRet['res']['reward_time'] = ArenaRound::getRewardTime() - Util::getTime();
    	$arrRet['res']['opponents'] = ArenaLogic::getOpponents($arrRet['res']['va_opponents'], $arrRet['res']['position']);
    	//$arrRet['res']['arena_msg'] = ArenaLogic::getArenaMsg($this->uid);
    	unset($arrRet['res']['va_opponents']);
    	$arrRet['res']['goods'] = $this->getInfo();
    	list($arrRet['res']['activity_begin_time'],
    		$arrRet['res']['activity_end_time'],
    		$arrRet['res']['active_rate']) = array(0, 0, ArenaLogic::getActiveRate());
    
    	Logger::trace('Arena::getArenaInfo End.');
    	return $arrRet;
    }
    
    /* (non-PHPdoc)
     * @see IArena::challenge()
    */
    public function challenge($position, $atkedUid, $num = 1)
    {
    	Logger::trace('Arena::challenge Start.');
    	
    	if ($position <= 0 || $atkedUid <= 0 || $num <= 0)
    	{
    		throw new FakeException('Err para, position:%d, atkedUid:%d, num%d', $position, $atkedUid, $num);
    	}

    	$arrRet = ArenaLogic::challenge($this->uid, $position, $atkedUid, $num);
    
    	Logger::trace('Arena::challenge End.');
    	return $arrRet;
    }  

	/* (non-PHPdoc)
	 * @see IArena::getRankList()
	 */
	public function getRankList()
	{
		Logger::trace('Arena::getRankList Start.');
		
		$ret =  ArenaLogic::getRankList($this->uid, ArenaConf::RANK_LIST_NUM);
		
		Logger::trace('Arena::getRankList End.');
        return $ret;
	}

	/* (non-PHPdoc)
	 * @see IArena::getLuckyList()
	 */
	public function getLuckyList()
	{
		Logger::trace('Arena::getLuckyList Start.');
		
		$arrRet =  ArenaLuckyLogic::getRewardLuckyList($this->uid);
		
		Logger::trace('Arena::getLuckyList End.');
		return $arrRet;
	}

	public function arenaDataRefresh($atkedInfo)
	{
		Logger::trace('Arena::arenaDataRefresh Start.');
		$uid = RPCContext::getInstance()->getUid();
		//已经登录
		if ($uid != null)
		{	
			//并且在竞技场TODO:没有重置session
			if (RPCContext::getInstance()->getSession('global.arenaId') == SPECIAL_ARENA_ID::ARENA)
			{	
				$atkedInfo['opponents'] = ArenaLogic::getOpponents($atkedInfo['va_opponents'], $atkedInfo['position']);
				unset($atkedInfo['va_opponents']);
				Logger::debug('send msg to arenaDataRefresh:%s', $atkedInfo);
				RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::ARENA_USER_DATA_REFRESH, $atkedInfo);
			}
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IArena::buy()
	 */
	public function buy($goodsId, $num)
	{
		Logger::trace('Arena::buy Start.');
	
		if (EnSwitch::isSwitchOpen(SwitchDef::ARENA) == false)
		{
			throw new FakeException('user:%d does not open the shop', $this->uid);
		}
	
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('Err para, goodsId:%d num:%d', $goodsId, $num);
		}
	
		$ret = $this->exchange($goodsId, $num);
	
		Logger::trace('Arena::buy End.');
	
		return $ret['ret'];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::getExchangeConf()
	 */
	public function getExchangeConf($goodsId)
	{
		Logger::trace('Arena::getExchangeConf Start.');
		if (empty(btstore_get()->ARENA_GOODS[$goodsId]))
		{
			Logger::warning('The goods is not existed, goodsId:%d', $goodsId);
			return array();
		}
		
		$ret = btstore_get()->ARENA_GOODS[$goodsId]->toArray(); //必须toArray,否则无法向普通数组那样赋值时自动增加下标
		$user = EnUser::getUserObj($this->uid);
		
		if (empty($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM]))
		{
			if (!empty($ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_LEVEL_NUM]))
			{
				$ret[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = 0;
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
		Logger::trace('Arena::getExchangeConf End.');
	    
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IArena::sendRankReward()
	 */
	public function sendRankReward()
	{
		Logger::trace('Arena::sendRankReward Start.');
	
		$ret =  ArenaLogic::sendRankReward($this->uid);
	
		Logger::trace('Arena::sendRankReward End.');
	
		return $ret;
	} 
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */