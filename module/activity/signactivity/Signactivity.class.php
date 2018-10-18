<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Signactivity.class.php 232025 2016-03-10 08:33:38Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/signactivity/Signactivity.class.php $
 * @author $Author: JiexinLin $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-03-10 08:33:38 +0000 (Thu, 10 Mar 2016) $
 * @version $Revision: 232025 $
 * @brief 
 *  
 **/
class Signactivity implements ISignactivity
{
	public $arrFields =  array (
			'uid' ,
			'acti_sign_time' ,
			'acti_sign_num' ,
			'va_acti_sign',
	);
	
	public $initValArr = array(
			'acti_sign_time'	=> 0 ,
			'acti_sign_num' 	=> 0 ,
			'va_acti_sign'		=> array(),
	);
	
	public function getSignactivityInfo()
	{
		Logger::trace('getSignactivityInfo begin');
		$uid = RPCContext::getInstance()->getUid();
		if ( empty($uid) )
		{
			throw new FakeException( 'invalid uid' );
		}
		$conf = $this->getConfig();
		if ( empty( $conf ) )
		{
			//非活动期间，返回空
			return array();
		}
		
		$ret = SignactivityDao::getSignactivityInfo($uid, $this->arrFields );
		if ( empty( $ret ) )
		{
			$ret = $this->initSignactivityInfo($uid);
		}
		$ret = $this->refreshSignactivity($ret);
		$ret['today'] = $this->getDateNumOfToday();
		Logger::trace('getSignactivityInfo end');
		return $ret;
	}
	
	public function getConfig()
	{
		//只在获取配置的时候进行判定 接口都要调用该方法
		if ( !EnActivity::isOpen( ActivityName::SIGN_ACTIVITY ) )
		{
			return array();
		}
		$conf = EnActivity::getConfByName( ActivityName::SIGN_ACTIVITY );
	
		return $conf;
	}
	
	
	public function initSignactivityInfo( $uid )
	{
		$initValArr = $this->initValArr;
		$initValArr['uid'] = $uid;
		SignactivityDao::insert( $uid, $initValArr );
		return $initValArr;
	}
	
	public function refreshSignactivity( $signInfo )
	{
		if ( empty( $signInfo ) )
		{
			throw new FakeException( 'empty sign info' );
		}
		//今天已经签到过了
		if ( Util::isSameDay($signInfo[ 'acti_sign_time' ] ))
		{
			return $signInfo;
		}
		//如果上次签到时间不是此次活动的时间，需要重置
		$conf = $this->getConfig();
		if ( $signInfo[ 'acti_sign_time' ] < $conf['start_time'] )
		{
			$signInfo['acti_sign_time'] = Util::getTime() ;
			$signInfo['acti_sign_num'] = 1;
			$signInfo['va_acti_sign'] = array();
			
			Logger::info('reset signactivity');
		}
		elseif ( $signInfo[ 'acti_sign_time' ] > $conf['end_time'] )
		{
			throw new InterException( 'signtime: %d beyond activity endtime: %d',$signInfo[ 'acti_sign_time' ], $conf['end_time'] );
		}
		else 
		{
			$signInfo['acti_sign_time'] = Util::getTime() ;
			$signInfo['acti_sign_num'] ++;
		}
		//签到
		SignactivityDao::update( $signInfo['uid'] , $signInfo );
		Logger::debug('signInfo now is: %s', $signInfo);
		return $signInfo;
	}
	
	public static function getDateNumOfToday()
	{
		$conf = EnActivity::getConfByName( ActivityName::SIGN_ACTIVITY );
		$startDateZeroTime = strtotime( date('Ymd', $conf['start_time']) );
		return intval( ( Util::getTime() - $startDateZeroTime ) / SECONDS_OF_DAY ) + 1;
	}
	
	public function gainSignactivityReward( $prizeIndex )
	{
		Logger::trace('gainSignactivityReward begin');
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'invalid uid' );
		}
		//此处理论上讲逻辑不太流畅
		//$signInfo可能为空 被接下来的判定拦住了（conf为空是$signInfo为空的原因）
		$signInfo = $this->getSignactivityInfo();
		$conf = $this->getConfig();
		if ( empty($conf) )
		{
			throw new FakeException( 'invalid time' );
		}
		// 数据库里不需要存today这个字段,只是返回给前端使用,所以unset掉
		unset($signInfo['today']);
		Logger::debug('conf in db is: %s', $conf);
		$confData = $conf['data'];
		if ( !isset( $confData[$prizeIndex] ) )
		{
			throw  new FakeException( 'nosuch prizeIndex: %d' , $prizeIndex );
		}	
// 		$needDays = $confData[$prizeIndex][ 'needDays' ];
// 		if ( $signInfo[ 'acti_sign_num' ] < $needDays )
// 		{
// 			throw new FakeException( 'needDays:  %d , signed days : %d ' , $needDays,$signInfo[ 'acti_sign_num' ]);
// 		}

		if ( in_array( $prizeIndex , $signInfo[ 'va_acti_sign' ]) )
		{
			throw new FakeException( 'already gain the prizeIndex %d' , $prizeIndex );
		}
		$signInfo[ 'va_acti_sign' ][] = $prizeIndex;
	
		// 判断是否是补签领奖,如果是,则需要扣金币
		// $ifRemedy为0表示非补签,为1表示补签
		$ifRemedy = 0;
		$reqCost = 0;
		$today = $this->getDateNumOfToday();
		if ( $prizeIndex < $today )
		{
			$ifRemedy = 1;
			$reqCost = intval( $confData[ $prizeIndex ][ 'cost' ] );
		}
		else if ( $prizeIndex > $today )
		{
			throw new FakeException('signed day:%d is not opened, cant gain', $prizeIndex);
		}
		if ( 1 == $ifRemedy )
		{
			$user = EnUser::getUserObj($uid);
			$curGold = $user->getGold();
			if ( false == $user->subGold( $reqCost, StatisticsDef::ST_FUNCKEY_SIGN_ACTIVITY_COST ) )
			{
				throw new FakeException('too less gold:%d to remedy sign, at least gold:%d', $curGold, $reqCost);
			}
		}
		
		$arrReward = $confData[ $prizeIndex ][ 'rewardArr' ];
		//满足 发奖
		$ret = RewardUtil::reward($uid, $arrReward, StatisticsDef::ST_FUNCKEY_SIGN_ACTIVITY );
		
		SignactivityDao::update( $uid , $signInfo );
		
		if ( $ret[ 'bagModify' ] )
		{
			BagManager::getInstance()->getBag( $uid )->update();
		}
		EnUser::getUserObj( $uid )->update();
		
		Logger::trace('gainSignactivityReward end');
		return 'ok';
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */