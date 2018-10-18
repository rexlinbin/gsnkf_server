<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Retrieve.def.php 257786 2016-08-23 06:32:53Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Retrieve.def.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-23 06:32:53 +0000 (Tue, 23 Aug 2016) $
 * @version $Revision: 257786 $
 * @brief 
 *  
 **/

class RetrieveCsvTag
{
	const TYPE 			= 'type'; 				// 追回类型
	const SILVER		= 'silver';				// 追回消耗银币
	const GOLD			= 'gold';				// 追回消耗金币
	const SILVER_REWARD	= 'silver_reward';		// 银币追回奖励
	const GOLD_REWARD   = 'gold_reward';		// 金币追回奖励
}

class RetrieveDef
{
	/**
	 * 资源追回类型
	 */
	const BOSS 			= 1; 					// 资源追回类型之BOSS
	const OLYMPIC 		= 2; 					// 资源追回类型之擂台赛
	const COUNTRYWAR	= 3; 					// 资源追回类型之国战
	const SUPPLY        = 4;                    // 资源追回类型之吃烧鸡
	
	const RETRIEVE_SIGLE = 0;                   // 单条追回
	const RETRIEVE_ALL  = 1;                    // 一键追回
	
	public static $RETRIEVE_TYPE = array
	(
			self::BOSS,
			self::OLYMPIC,
			self::COUNTRYWAR,
	        self::SUPPLY,
	);
	
	/**
	 * t_retrieve表字段
	 */
	const TBL_FIELD_UID 					= 'uid';
	const TBL_FIELD_VA_EXTRA 				= 'va_extra';
	const TBL_VA_EXTRA_FIELD_RETRIEVE_TYPE 	= 'retrieve_type';
	
	public static $RETRIEVE_ALL_FIELDS = array
	(
			self::TBL_FIELD_UID,
			self::TBL_FIELD_VA_EXTRA,
	);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */