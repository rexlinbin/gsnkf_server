<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnPass.class.php 221022 2016-01-11 11:06:41Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pass/EnPass.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-01-11 11:06:41 +0000 (Mon, 11 Jan 2016) $
 * @version $Revision: 221022 $
 * @brief 
 *  
 **/
class EnPass
{
	public static function getPassObj( $uid )
	{
		$passObj = PassObj::getInstance( $uid );
		return $passObj;
	}
	
	
	public static function getPassConfTimeArr()
	{
		$curDaySeconds = 
		$beginSeconds = strtotime( date( 'Ymd', Util::getTime() ).PassCfg::HANDSOFF_BEGINTIME )
		- strtotime( date( 'Ymd', Util::getTime() )."000000" );
		return array( 
				'handsOffBeginTime' => $beginSeconds, 
				'handsOffLastSeconds' => PassCfg::HANDSOFF_LASTTIME 
		);
	}
	
	/**
	 * 
	 * @param unknown $arrHid
	 * @return bool, 如果有一个英雄不能删就不让删了
	 */
	public static function canArrHidBeDel( $uid, $arrHid )
	{
		if( !EnSwitch::isSwitchOpen( SwitchDef::PASS) )
		{
			return true;
		}
		
		$passObj = self::getPassObj( $uid );
		$heroInfo = $passObj ->getVaParticular( PassDef::VA_HEROINFO );
		
		foreach ( $arrHid as $hid )
		{
			if( isset( $heroInfo[$hid] ) )
			{
				return false;
			}
		}
		
		return true;
	}
	
	public static function getTopActivityInfo()
	{
		$ret = array(
				'status' => 'ok',
				'extra' => array(
						'num' => 0,		//剩余可挑战次数
						'pass' => 0,	//是否通关了
						'curr' => 0,    //当前是多少关
		),
		);
		
		$uid = RPCContext::getInstance()->getUid();
		if( !EnSwitch::isSwitchOpen( SwitchDef::PASS,$uid ) 
		|| PassLogic::isHandsOffTime( Util::getTime() ) )
		{
			$ret['status'] = 'invalid';
			return $ret;
		} 
		$passObj = PassObj::getInstance($uid);
		if( $passObj->allBaseDone() )
		{
			$ret['extra']['pass'] = 1;
			return $ret;
		}
		$confNum = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_PASS_FREE_NUM];
		$buyNum = $passObj->getBuyNum();
		$loseNum = $passObj->getLoseNum();
		$leftNum = $confNum + $buyNum - $loseNum;
		$leftNum = $leftNum < 0 ? 0:$leftNum;
		$ret['extra']['num'] = $leftNum;
		
		$ret['extra']['curr'] = $passObj->getBase();
		
		return $ret;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */