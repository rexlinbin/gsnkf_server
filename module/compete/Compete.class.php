<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Compete.class.php 191949 2015-08-18 02:37:45Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/compete/Compete.class.php $
 * @author $Author: JiexinLin $(tianming@babeltime.com)
 * @date $Date: 2015-08-18 02:37:45 +0000 (Tue, 18 Aug 2015) $
 * @version $Revision: 191949 $
 * @brief 
 *  
 **/
/**********************************************************************************************************************
 * Class       : Compete
 * Description : 比武系统对外接口实现类
 * Inherit     : ICompete
 **********************************************************************************************************************/
class Compete extends Mall implements ICompete
{
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		$uid = RPCContext::getInstance()->getUid();
		parent::__construct($uid, MallDef::MALL_TYPE_COMPETE);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICompete::getCompeteInfo()
	 */
	public function getCompeteInfo()
	{
		Logger::trace('Compete::getCompeteInfo Start.');
		
		$ret = CompeteLogic::getCompeteInfo($this->uid);
		
		Logger::trace('Compete::getCompeteInfo End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICompete::refreshRivalList()
	 */
	public function refreshRivalList()
	{
		Logger::trace('Compete::refreshRivalList Start.');
		
		$ret = CompeteLogic::refreshRivalList($this->uid);
		
		Logger::trace('Compete::refreshRivalList End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICompete::contest()
	 */
	public function contest($atkedUid, $type = 0)
	{
		Logger::trace('Compete::contest Start.');
		
		if (empty($atkedUid) || !in_array($type, CompeteDef::$COMPETE_VALID_TYPES)) 
		{
			throw new FakeException('Err para, rivalUid:%d, type:%d', $atkedUid, $type);
		}
		
		$ret = CompeteLogic::contest($this->uid, $atkedUid, $type);
		
		Logger::trace('Compete::contest End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICompete::getRankList()
	 */
	public function getRankList()
	{
		Logger::trace('Compete::getRankList Start.');
		
		$ret = CompeteLogic::getRankList($this->uid, CompeteConf::COMPETE_TOP_TEN);
		
		Logger::trace('Compete::getRankList End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICompete::buyCompeteNum()
	 */
	public function buyCompeteNum($num)
	{
		Logger::trace('Compete::buyCompeteNum Start.');
		
		if ($num <= 0)
		{
			throw new FakeException('Err para, num:%d', $num);
		}
		
		$ret = CompeteLogic::buyCompeteNum($this->uid, $num);
		
		Logger::trace('Compete::buyCompeteNum End.');
		
		return $ret;
	}
	
	public function competeDataRefresh($uid, $atkUid, $silver, $failPoint, $replayId)
	{
		Logger::trace('Compete::competeDataRefresh Start.');
		
		if ($uid <= 0)
		{
			throw new FakeException('Invalid uid:%d', $uid);
		}
		
		// 如果用户不在线，就设置一下session，伪装自己在当前用户的连接中
		$guid = RPCContext::getInstance()->getSession('global.uid');
		if ($guid == null)
		{
			RPCContext::getInstance()->setSession('global.uid', $uid);
		}
		else if ($uid != $guid)
		{
			Logger::fatal('atkUserByOther, uid:%d, guid:%d', $uid, $guid);
			return;
		}
		
		CompeteLogic::atkUserByOther($uid, $atkUid, $silver, $failPoint, $replayId);
		
		Logger::trace('Compete::competeDataRefresh End.');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICompete::getShopInfo()
	 */
	public function getShopInfo()
	{
		Logger::trace('Compete::getShopInfo Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::ROB) == false)
		{
			throw new FakeException('user:%d does not open the compete shop', $this->uid);
		}
	
		$ret = $this->getInfo();
	
		Logger::trace('Compete::getShopInfo End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICompete::buy()
	 */
	public function buy($goodsId, $num)
	{
		Logger::trace('Compete::buy Start.');
	
		if (EnSwitch::isSwitchOpen(SwitchDef::ROB) == false)
		{
			throw new FakeException('user:%d does not open the compete shop', $this->uid);
		}
	
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('Err para, goodsId:%d num:%d', $goodsId, $num);
		}
		if (empty(btstore_get()->COMPETE_GOODS[$goodsId]))
		{
			throw new FakeException('The goods is not existed, goodsId:%d', $goodsId);
		}
	
		$this->exchange($goodsId, $num);
		$ret = CompeteLogic::subHonor($this->uid, $goodsId, $num);
	
		Logger::trace('Compete::buy End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::getExchangeConf()
	 */
	public function getExchangeConf($goodsId)
	{
		Logger::trace('Compete::getExchangeConf Start.');
		if (empty(btstore_get()->COMPETE_GOODS[$goodsId]))
		{
			Logger::warning('The goods is not existed, goodsId:%d', $goodsId);
			return array();
		}
	
		$ret = btstore_get()->COMPETE_GOODS[$goodsId]->toArray();  //必须toArray,否则无法向普通数组那样赋值时自动增加下标
		
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
		Logger::trace('Compete::getExchangeConf End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mall::subExtra()
	 */
	public function subExtra($goodsId, $num)
	{
		Logger::trace('Compete::subExtra Start.');
		
		if ($goodsId <= 0 || $num <= 0)
		{
			throw new FakeException('Err para, goodsId:%d num:%d!', $goodsId, $num);
		}
		
		
		//检查商品是否存在
		if (empty(btstore_get()->COMPETE_GOODS[$goodsId]))
		{
			throw new FakeException('The goods is not existed, goodsId:%d', $goodsId);
		}
		$ret = CompeteLogic::isHonorEnough($this->uid, $goodsId, $num);
		
		
		
		Logger::trace('Compete::subExtra End.');
		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */