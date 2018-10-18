<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PayBack.def.php 259543 2016-08-31 03:11:56Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/PayBack.def.php $
 * @author $Author: GuohaoZheng $(yangwenhai@babeltime.com)
 * @date $Date: 2016-08-31 03:11:56 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259543 $
 * @brief 
 *  
 **/


class PayBackDef
{
	

	const PAYBACK_SQL_INFO_TABLE							=	't_pay_back_info';	//赔偿信息的表格
	const PAYBACK_SQL_USER_TABLE							=	't_pay_back_user';	//哪些人领过赔偿的表格
	
	const PAYBACK_SQL_PAYBACK_ID							=	'payback_id';		//t_pay_back_info的id
	const PAYBACK_SQL_UID									=	'uid';				//领过赔偿人的uid
	const PAYBACK_SQL_ARRY_INFO								=	'va_payback_info';	//赔偿信息
	const PAYBACK_SQL_TIME_START							=	'time_start';		//赔偿对应的开始时间
	const PAYBACK_SQL_TIME_END								=	'time_end';			//赔偿对应的结束时间
	const PAYBACK_SQL_TIME_EXECUTE							=	'time_execute';		//获得赔偿的时间
	const PAYBACK_SQL_IS_OPEN								=	'isopen';			//赔偿功能是否开启
	

	const PAYBACK_TYPE 										=	'type';		 		// 类型
	const PAYBACK_MSG										=	'msg';		 	// 贝里
	const PAYBACK_TITLE                                     =   'title';        // 补偿标题
	

	
		
	
}

class PayBackType
{
	const SYSTEM	=  	1;//系统补偿
	const LOGIN		=	2;//每日登录回馈  
	const LORDWAR_WHOLEWORLD	=	3;//跨服战冠军所在服全服奖励
	const GUILDWAR_WHOLDWORLD	= 	4;//跨服军团战冠军所在服全服奖励
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */