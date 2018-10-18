<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Sign.class.php 136427 2014-10-16 05:53:41Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sign/Sign.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-10-16 05:53:41 +0000 (Thu, 16 Oct 2014) $
 * @version $Revision: 136427 $
 * @brief 
 *  
 **/
class Sign implements ISign
{
	private $uid;
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	
	public function getAccInfo()
	{
		$signInfo = AccsignLogic::getSignInfo( $this->uid );
		$gotRewardArr = $signInfo['va_sign'];
		unset( $signInfo['sign_time'] );
		unset( $signInfo[ 'va_sign' ] );
		$signInfo['acc_got'] = $gotRewardArr;
		return $signInfo;
	}
	
	/**
	 * 这是一个因需求修改，而删除的方法。
	 * 线上发现前端请求此方法，为了避免close前端，补上此方法
	 * @throws FakeException
	 */
	public function getSignInfo()
	{
		throw new FakeException( 'method:getSignInfo is old' );
	}
	
	public function getNormalInfo()
	{
		if ( !EnSwitch::isSwitchOpen( SwitchDef::SIGN ) )
		{
			throw new FakeException( 'normal sign is not open' );
		}
		$signInfo = NormalsignLogic::getNormalInfo( $this->uid );
		$signTimes = $signInfo['sign_num'];
		$signTime = $signInfo['sign_time'];
		$maxDays = NormalsignLogic::getMaxNormalDays();
		$gotRewardArr = array_fill( 1 , $maxDays, 2);
		foreach ( $gotRewardArr as $day => $val )
		{
			if ( $day <= $signInfo['sign_num'] )
			{
				$gotRewardArr[$day] = 1;
			}
			elseif ( $day > $signInfo['sign_num']+1 )
			{
				continue;
			}
			else
			{
				if (!Util::isSameDay(  $signInfo['sign_time']))
				{
					$gotRewardArr[$day] = 0;
				}
			}
		}
		
		if ( $signInfo['sign_num'] == $maxDays )
		{
			if (!Util::isSameDay( $signInfo['sign_time']))
			{
				$gotRewardArr[$maxDays] = 0;
			}
		}
		
		$signInfo['normal_list'] = $gotRewardArr;
		unset($signInfo[ 'sign_time' ]);
		return $signInfo;
	}

	public function gainNormalSignReward( $step )
	{
		if ( !EnSwitch::isSwitchOpen( SwitchDef::SIGN ) )
		{
			throw new FakeException( 'normal sign is not open' );
		}
		Logger::trace( 'begin getNormalSignReward' );
		$ret =  NormalsignLogic::getNormalSignReward($this->uid , $step);
		Logger::trace('finish getNormalSignReward');
	}
	
	public function gainAccSignReward( $step )
	{
		Logger::trace( 'begin getAccSignReward' );
		$ret = AccsignLogic::getAccSignReward( $this->uid , $step);
		Logger::trace('finish getAccSignReward');
	}
	/* (non-PHPdoc)
	 * @see ISign::getMonthSignInfo()
	 */
	public function getMonthSignInfo() 
	{
		$signInfo = MonthSignLogic::getMonthSignInfo( $this->uid );
		return $signInfo;
	}

	/* (non-PHPdoc)
	 * @see ISign::gainMonthSignReward()
	 */
	public function gainMonthSignReward() 
	{
		MonthSignLogic::gainMonthSignReward($this->uid);
		return 'ok';
	}

	
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */