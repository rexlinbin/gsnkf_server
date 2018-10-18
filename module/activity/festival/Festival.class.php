<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Festival.class.php 157250 2015-02-05 10:04:16Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/festival/Festival.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-02-05 10:04:16 +0000 (Thu, 05 Feb 2015) $
 * @version $Revision: 157250 $
 * @brief 
 *  
 **/
class Festival implements IFestival
{
	private $uid = 0;
	
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	
	public function getFestivalInfo()
	{
		Logger::trace('Festival::getFestivalInfo Start.');

		$festivalInfo = FestivalLogic::getFestivalInfo($this->uid);

		Logger::trace('Festival::getFestivalInfo End.');

		return $festivalInfo;
	}

	public function compose($fNumber, $num = 1)
	{
		Logger::trace('Festival::compose Start.');

		$uid = RPCContext::getInstance()->getUid();
		$fNumber = intval($fNumber);

		if ( $fNumber < 1 || $fNumber > FestivalLogic::getFormulaNum())
		{
			throw new FakeException('param err. fNumber: %d.',$fNumber);
		}
		
		if ( $num <= 0 || $num > FestivalDef::COMPOSE_MAX_EACH)
		{
			throw new FakeException('param err. num: %d.',$num);
		}

		$ret = FestivalLogic::compose($uid, $fNumber, $num);

		Logger::trace('Festival::compose End.');

		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */