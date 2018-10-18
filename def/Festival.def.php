<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Festival.def.php 153764 2015-01-20 08:50:10Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Festival.def.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-01-20 08:50:10 +0000 (Tue, 20 Jan 2015) $
 * @version $Revision: 153764 $
 * @brief 
 *  
 **/
class FestivalDef
{
	//解析配置
	const ID = 'id';
	const ACT_TYPE = 'type';
	const FORMULA_NUM = 'formula_num';
	const EXTRA_DROP = 'extra_drop';
	const FORMULA = 'formula';
	
	//数据库中字段
	const UID = 'uid';
	const UPDATE_TIME = 'update_time';
	const VA_DATA = 'va_data';

	//每个公式占几列
	const EACH_FORMULA = 3;     //每个公式在csv表里有三列，分别是需要物品、获得物品、限制次数

	//活动类型
	const ACT_TYPE_DROP = 1;    //只开放副本掉落
	const ACT_TYPE_COMPOSE = 2; //开放副本掉落和限时合成

	//副本类型
	const COPY_TYPE_NORMAL = 1; //普通副本
	const COPY_TYPE_ELITE  = 2; //精英副本
	
	public static $ALL_TABLE_FIELD = array(
			self::UID,
			self::UPDATE_TIME,
			self::VA_DATA
	);
	
	const COMPOSE_MAX_EACH = 50;//一次合成最大次数

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */