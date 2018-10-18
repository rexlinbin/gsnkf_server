<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ScoreShop.def.php 159877 2015-03-03 09:12:53Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/ScoreShop.def.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-03-03 09:12:53 +0000 (Tue, 03 Mar 2015) $
 * @version $Revision: 159877 $
 * @brief 
 *  
 **/
class ScoreShopDef
{
	//一积分对应的金币、体力、耐力数
	const GOLD_EACH_POINT = 'gold_each_point';
	const EXECUTION_EACH_POINT = 'execution_each_point';
	const STAMINA_EACH_POINT = 'stamina_each_point';
	
	const GAIN_POINT_DAY = 'gain_point_day';
	const TO_POINT = 'to_point';
	const ITEMS = 'items';
	const POINT = 'point';
	
	//已用的积分
	const SQL_USED_POINT = 'point';
	const SQL_UPDATE_TIME = 'update_time';
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */