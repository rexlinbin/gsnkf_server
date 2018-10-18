<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(pengnana@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/
class BlackshopManage extends Mall
{
	public function __construct($uid=0)
	{
		if(empty($uid))
		{
			$uid = RPCContext::getInstance()->getUid();
		}
		parent::__construct($uid, MallDef::MALL_TYPE_BLACKSHOP,
				StatisticsDef::ST_FUNKEY_BLACKSHOP_USE,StatisticsDef::ST_FUNKEY_BLACKSHOP_GET);
	}
	public function getExchangeConf($id)//获取黑市配置
	{
		$conf = EnActivity::getConfByName(ActivityName::BLACKSHOP);//获取配置
		if(empty($conf['data'][$id]))
		{
			Logger::warning("blackshop conf id:%d null.",$id);
			return array();
		}
		return $conf['data'][$id];		
	}
	
	public function isInCurRound($time)
	{
		$ret = EnActivity::getConfByName(ActivityName::BLACKSHOP);
		$now = $time;
		if( $now >= $ret['start_time'] && $now <= $ret['end_time'])
		{
			return true;
		}
		return false;
	}
	public function getDay()//获取当前活动第几天
	{
		$day = EnActivity::getActivityDay(ActivityName::BLACKSHOP) + 1;
		return $day;
	}
	public function getValidId()//获取当前活动有效id
	{
		$conf = EnActivity::getConfByName(ActivityName::BLACKSHOP);//获取配置
		$day = intval(self::getDay());//当前日期
		$lastDay = $conf['data']['lastDay'];
		if($day <= $lastDay)
		{
			$ret = $conf['data']['dayInfo'][$day];//当前开启的兑换
			return $ret;
		}
		else 
		{
			throw new FakeException('blackshop conf day is wrong.');
		}
	}
	public function subExtra($exchangeId, $num)//扣除荣誉
	{
		//不要做user->update和bag->update
		$conf = self::getExchangeConf($exchangeId);
		$uid = RPCContext::getInstance()->getUid();
		$req = $conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA];
		if(isset($req))
		{
			$number = (-1) * $req * $num;
			$ret = CompeteLogic::addHonor($uid , $number);
			if($ret =='ok')
			{
				return true;
			}
		}
		return false;
	}
	public function addExtra($exchangeId, $num)//获得荣誉
	{
		$conf = self::getExchangeConf($exchangeId);
		$uid = RPCContext::getInstance()->getUid();
		$acq = $conf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_EXTRA];
		if(isset($acq))
		{
			$number =  $acq * $num;
			$ret = CompeteLogic::addHonor($uid , $number);
			if($ret =='ok')
			{
				return true;
			}
		}
		return false;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */