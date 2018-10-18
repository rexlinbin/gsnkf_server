<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: OnlineLogic.class.php 61430 2013-08-27 05:33:34Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/online/OnlineLogic.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-08-27 05:33:34 +0000 (Tue, 27 Aug 2013) $
 * @version $Revision: 61430 $
 * @brief 
 *  
 **/

class OnlineLogic
{
	public static function getOnlineInfo()
	{
		$onlineInfo = RPCContext::getInstance()->getSession( OnlineDef::SESSIONKEY );
		if ( empty( $onlineInfo ) )
		{
			$uid = RPCContext::getInstance()->getUid();
			if ( empty( $uid ) )
			{
				throw new SysException( 'failed to get uid' );
			}
			$onlineInfo = OnlineDao::getOnlineInfo($uid , OnlineDef::$arrField);
			
			if ( empty( $onlineInfo ) )
			{
				$onlineInfo = self::initOnlineInfo($uid );
			}
			RPCContext::getInstance()->setSession( OnlineDef::SESSIONKEY , $onlineInfo);
		}
		return $onlineInfo;
	}

	public static function initOnlineInfo($uid )
	{
		$iniArr = array(
				'uid'				=> $uid,
				'step'				=> OnlineCfg::INI_STEP,
				'begin_time'		=> Util::getTime(),
				'end_time'			=> OnlineCfg::INI_ENDTIME,
				'accumulate_time'	=> OnlineCfg::INI_ACCTIME ,
		);
		OnlineDao::insert($uid , $iniArr);
		return $iniArr;
	}
	
	//用户登录时开始
	public static function login()
	{
		$onlineInfo = self::getOnlineInfo();
		$onlineInfo ['begin_time'] = Util::getTime();
		OnlineDao::update( $onlineInfo[ 'uid' ],  $onlineInfo );
		RPCContext::getInstance()->setSession( OnlineDef::SESSIONKEY , $onlineInfo);
	}
	
	//用户登出时停止
	public static function logoff()
	{
		$onlineInfo = self::getOnlineInfo();
		$onlineInfo['end_time'] = Util::getTime();
		$onlineInfo['accumulate_time'] += ( $onlineInfo['end_time'] - $onlineInfo['begin_time'] );
		$onlineInfo['end_time'] = $gift['begin_time'] = 0;
		OnlineDao::update( $onlineInfo['uid'], $onlineInfo );
	}
	

	public static function gainGift( $step )
	{
		$onlineInfo = self::getOnlineInfo();
		$onlineConf = btstore_get()->ONLINE_GIFT;
		$onlineMaxStep = count( $onlineConf );
		if ( ( $onlineInfo [ 'step' ] + 1 ) > $onlineMaxStep )
		{
			throw new FakeException( 'gain all gifts' );
		}
		if ( $step!= ( $onlineInfo['step'] +1 ) )
		{
			throw new FakeException ( 'fail to get gift, step err' );
		}
		$onlineInfo['accumulate_time'] += (Util::getTime() - $onlineInfo['begin_time']);
		if (empty( $onlineConf[$step] ))
		{
			throw new ConfigException( 'fail to step %d, config is not exist.', $step );
		}
		$cfg = $onlineConf[$step];
		if ($onlineInfo['accumulate_time'] < $cfg['needTime'])
		{
			throw new FakeException('fail to get gift %d , accumulate_time %d is not enough.', $step, $onlineInfo['accumulate_time']);
		}
		//修改数据
		$onlineInfo ['accumulate_time'] = OnlineCfg::RESET_ACCTIME;
		$onlineInfo ['end_time'] = OnlineCfg::RESET_ENDTIME;
		$onlineInfo ['begin_time'] = Util::getTime();
		$onlineInfo ['step'] += 1;
		
		//发奖
		$ret = RewardUtil::rewardById($onlineInfo[ 'uid' ], $step, StatisticsDef::ST_FUNCKEY_ONLINE_REWARD);
		
		//更新
		OnlineDao::update($onlineInfo['uid'], $onlineInfo);
		RPCContext::getInstance()->setSession( OnlineDef::SESSIONKEY , $onlineInfo);
		
		if ( $ret[ 'bagModify' ] )
		{
			$bag = BagManager::getInstance()->getBag( $onlineInfo[ 'uid' ] );
			$bag->update();
		}
		EnUser::getUserObj()->update();
		
		return $cfg[ 'rewardArr' ];
	}
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */