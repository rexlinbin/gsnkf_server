<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnMoon.class.php 222326 2016-01-15 04:25:20Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/moon/EnMoon.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-01-15 04:25:20 +0000 (Fri, 15 Jan 2016) $
 * @version $Revision: 222326 $
 * @brief 
 *  
 **/
 
class EnMoon
{
	public static function getTopActivityInfo()
	{
		$ret = array
		(
				'status' => 'ok',
				'extra' => array('normal_num' => 0, 'nightmare_num' => 0), 
		);
		
		if (!EnSwitch::isSwitchOpen(SwitchDef::MOON))
		{
			$ret['status'] = 'invalid';
			return $ret;
		}
		
		//第一关打过啦，肯定有boss可打，第一关没打过，但是所有的据点都打过啦，也有boss可打
		$moonObj = MoonObj::getInstance();
		if ($moonObj->isCopyPass(1) || $moonObj->allGridDone()) 
		{
			$ret['extra']['normal_num'] = MoonObj::getInstance()->getAtkNum();
			
			if (!EnSwitch::isSwitchOpen(SwitchDef::TALLY)) 
			{
				$ret['extra']['nightmare_num'] = 0;
			}
			else 
			{
				$ret['extra']['nightmare_num'] = MoonObj::getInstance()->getNightmareCanAtkNum();
			}
		}
		
		return $ret;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */