<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Divine.def.php 105473 2014-04-30 10:04:14Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Divine.def.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-04-30 10:04:14 +0000 (Wed, 30 Apr 2014) $
 * @version $Revision: 105473 $
 * @brief 
 *  
 **/
class DivineDef
{
	public static $DIVI_SESSION_KEY 	= 'divine.info';//占星session的键名
	
	const  TARGET 				= 'target';		//va字段目标星座键名
	const  CURRENT 				= 'current';	//va字段占星星座键名
	
	public static $STAR_NO_LIGHTED 		= 0;			//星星没有被点亮
	
	public static $TBL 					= 't_divine';	//占星系统使用数据库表名
	public static $DIVI_FIELDS 			= array( 		//数据库查询字段
			'uid',				
			'divi_times',			
			'refresh_time', 		
			'free_refresh_num',			
			'prize_step', 			
			'target_finish_num',		
			'integral', 			
			'prize_level',
			'ref_prize_num',
			'va_divine' 
	);
	
	const DIVI_FAKEDATA					= 'divine.fakedata';
	const LIGHTED 						= 'lighted';
	
	const FAKE							= 'fakeTime';
	
	const NORMAL_CONFIG_INDEX			= 8;
	
	const NEWREWRD						= 'newreward';
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */