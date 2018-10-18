<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnDailyfund.class.php 92313 2014-03-05 11:06:24Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/dailyfund/EnDailyfund.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-03-05 11:06:24 +0000 (Wed, 05 Mar 2014) $
 * @version $Revision: 92313 $
 * @brief 
 *  
 **/
class EnDailyfund
{
	/**
	 * 
	 * @param int $type 需要获得的比率所属的类型（ 如：副本经验、资源产量等 ）
	 * @return number 加成的比率（ 无加成则为0 ）
	 */
	public static function DailyfundRate( $type )
	{
		$rate = 0;
		if ( !EnActivity::isOpen( ActivityName::DAILY_FUND ) )
		{
			throw new FakeException( 'activity not on' );
		}
		$conf = EnActivity::getConfByName( ActivityName::DAILY_FUND );
		$confData = $conf[ 'data' ];
		//获取星期几
		$day = date('w' , Util::getTime());
		$arrRate = $confData[ $day ];
		
		//TODO根据每个活动返回加成比率，需要策划给出到底有哪些类别 给各个模块调用
		$key = 'copyRate';
		switch ( $type )
		{/* 
			case DailyFundType::COPY_EXP :
				//$key = '';
				$rate = self::getRate($arrRate, $key);
				break;
			case DailyFundType::COPY_FRAGMENT :
				$rate = self::getRate($arrRate, $key);
				break;
			case DailyFundType::COPY_SILVER :
				$rate = self::getRate($arrRate, $key);
				break;
			case DailyFundType::HORSE_FEED_EXP :
				$rate = self::getRate($arrRate, $key);
				break;
			case DailyFundType::MINERAL_PRODUCTION :
				$rate = self::getRate($arrRate, $key);
				break;
			case DailyFundType::STAR_EXP :
				$rate = self::getRate($arrRate, $key);
				break;
			case DailyFundType::STAR_ROB :
				$rate = self::getRate($arrRate, $key);
				break;
			case DailyFundType::TREE_SILVER :
				$rate = self::getRate($arrRate, $key);
				break;
			default :
				throw new FakeException( 'invalid type: %s' , $type );
		 */}
		
		return $rate;
	}
	
	public static function getRate( $conf , $key )
	{
		if ( !isset( $conf[ $key ] ) )
		{
			return 0;
		}
		if ( !is_numeric( $conf[ $key ] ))
		{
			throw new ConfigException( 'config err: dailyrefund, key: %s' , $key );
		}
		else
		{
			return $conf[ $key ];
		}
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */