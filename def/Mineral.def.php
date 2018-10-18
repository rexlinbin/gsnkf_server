<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Mineral.def.php 251426 2016-07-13 08:38:10Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Mineral.def.php $
 * @author $Author: QingYao $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-07-13 08:38:10 +0000 (Wed, 13 Jul 2016) $
 * @version $Revision: 251426 $
 * @brief 
 *  
 **/
class MineralDef
{
	//error_code
	const CAPTURE_PIT_ERROR_CODE		=	'err';
	const CAPTURE_PIT_TO_NUM_LIMIT		=	'numlimit'; //占领矿坑数目超额
	const CAPTURE_PIT_NO_EXECUTION		=	'execution';    //没有行动力
	const CAPTURE_PIT_NO_FIGHTCD		=	'cd';
	const CAPTURE_PIT_FORMATION_ERR		=	'formation';
	const CAPTURE_PIT_IN_PROTECT		=	'protect';
	const CAPTURE_PIT_OF_SELF			=	'self';
	const CAPTURE_PIT_NOT_SELF			=	'notself';
	const CAPTURE_PIT_OK				=	'ok';
    const CAPTURE_PIT_CAPTURED          =   'captured';
    const CAPTURE_PIT_NOTCAPTURED       =   'notcaptured';
    const CAPTURE_PIT_TO_DELAY_LIMIT    =   'delaylimit';   //延期次数超额
    const CAPTURE_PIT_VIP_LIMIT         =   'viplimit';
    const CAPTURE_PIT_NO_GOLD           =   'gold';     //金币不足

	//开启占领两个矿坑的玩家等级
	const SECOND_PIT_OPEN_LEVEL			=	50;
		
	//locker key
	const MINERAL_PIT_LOCKER_PRE		=	'pit_locker_';
	
	const MINERAL_OUTPUT_RATIO        =    0.00005;
	
	const MINERAL_OUTPUT_IRON_RATIO =0.00001;//精铁输出率
	
	const MINERAL_IRON_TPL_ID =60052;//精铁的物品ID，写死
	
	const VIP_HARVEST_ADDTION    =    'pitOccupyTimeAddition';
	
	const MINERAL_SESSION_DOMAIN_ID = 'global.domainid';
	
	const MINERAL_ACQUIRE_MIN_SILVER    =    1; //最少收获
	
	const CAPTURE_TYPE_CAPTURE_FREE    =    0;  //占领无人占领的资源矿  会打守护资源矿的部队
	const CAPTURE_TYPE_GRAB            =    1;  //策划配置时间内抢夺别人的资源矿
	const CAPTURE_TYPE_GRAB_BY_GOLD    =    2;  //策划配置时间外， 通过金币抢夺别人的资源矿

    const OCCUPY_PIT_LIMIT             =    1;  //可占领的最大资源矿数

    const DELAY_TIME_LIMIT             =    2;  //矿坑可延时次数
    
    const CAPTURE_GOLDPIT_NEEDGOLD = 20;
    
    const DUE_MANUALLY_NEED_DUETIME = SECONDS_OF_DAY;//过期多长时间之后手动结束资源矿占领或者守卫
    
    const MAX_ROBLOG_NUM = 20;
    
    const GUILD_STARTTIME=0;     //
    const GUILD_ENDTIME=1;
    
    const IF_GUILD_ADD_OPEN=1;
    
    const MAX_GUILD_ADD_SILVER=20000000; //占矿军团收益加成上限
    
    const MAX_IRON_GOT    =   2000 ;//精铁产出最大值
}
class MINERAL_SESSION_NAME
{
	const DOMAINID	=	'global.resourceId';
	const PITID		=	'mineral.pit';
}
/**
 * 
 * `page_id`						int(10) unsigned not null comment '页ID',
	`pit_id`						int(10) unsigned not null comment '矿坑ID',
	`uid`							int(10) unsigned not null comment '用户ID',
	`occupy_time`					int(10) unsigned not null comment '占领时间',
	`due_time`
 *
 */
class TblMineralField
{
	const DOMAINID		=	'domain_id';    //资源区id
	const PITID			=	'pit_id';   //矿坑id
	const DOMAINTYPE	=	'domain_type';	//资源矿类型 高级或低级之分
	const UID			=	'uid';  //用户id
	const OCCUPYTIME	=	'occupy_time';  //占领时间
	const DUETIMER		=	'due_timer';    //到期时间
    const DELAYTIMES   =   'delay_times';  //资源矿延时次数
    const TOTALGUARDSTIME = 'total_guards_time';    //协助军协助时间总和
	const PITTYPE		= 	'pit_type';
	const GUILDID  = 'guildId';      //占领者的军团id
	const VA_INFO = 'va_info';     //
	const GUILDINFO ='guild_info'; //
	public static $FIELDS		=	array(self::DOMAINID,self::PITID,self::DOMAINTYPE,self::PITTYPE,
			self::UID,self::OCCUPYTIME,self::DUETIMER,self::DELAYTIMES,self::TOTALGUARDSTIME,
			self::GUILDID,self::VA_INFO);
}
/**
 * //与配置表中的index对应
 *
 *
 */
class PitArr
{
	const OUTPUT	=	0;  //资源矿游戏币基础值
	const HARVESTTIME	=	1;  //矿坑的收获时间
	const PROTECTTIME	=	2;  //矿坑的保护时间
	const GUARDARMY		=	3;  //资源1的土著部队
    const GUARDLIMITNUM =   4;  //矿坑的协助军上限
}

class MineralType
{
    const SENIOR    =    1;//高级矿区
    const NORMAL    =    2;//普通矿区
    const GOLD     =    3;//黄金矿区
}

class TblMineralGuards
{
    const UID = 'uid';  //协助军uid
    const DOMAINID = 'domain_id';      //资源区id
    const PITID = 'pit_id'; //矿坑id
    const GUARDTIME = 'guard_time';   //成为协助军的时间
    const DUETIMER = 'due_timer';
    const STATUS = 'status';    //守卫军状态 0 非守卫军 1 守卫军
    public static $GUARDFIELDS = 
        array(
            self::UID, 
            self::DOMAINID, 
            self::PITID, 
            self::GUARDTIME, 
            self::DUETIMER,
            self::STATUS
                );
}

class GuardType
{
    const ISNOTGUARD = 0;
    const ISGUARD = 1;
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */

