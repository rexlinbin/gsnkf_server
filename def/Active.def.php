<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Active.def.php 254609 2016-08-03 12:38:27Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Active.def.php $
 * @author $Author: GuohaoZheng $(tianming@babeltime.com)
 * @date $Date: 2016-08-03 12:38:27 +0000 (Wed, 03 Aug 2016) $
 * @version $Revision: 254609 $
 * @brief 
 *  
 **/
class ActiveDef
{
	//事件类型id
	//副本
	const NCOPY = 1;
	//精英副本
	const ECOPY = 2;
	//活动副本
	const ACOPY = 3;
	//占星
	const DIVINE = 4;
	//猎魂
	const HUNT = 5;
	//夺宝
	const FRAGSEIZE = 6;
	//竞技场
	const ARENA = 7;
	//试练塔
	const TOWER = 8;
	//世界boss
	const BOSS = 9;
	//好友送/领耐力
	const LOVE = 10;
	//名将好感送礼
	const FAVOR = 11;
	//装备洗练
	const ARMRFH = 12;
	//拜关公
	const GUILDREWARD = 13;
	//签到
	const SIGN = 14;
	//月签到
	const MONTHSIGN = 15;
	//学习技能
	const LEARNSKILL = 16;
	//军团建设
	const GUILDCONTRI = 17;
	//军团组队副本
	const GUILDCOPYTEAM = 18;
	//军团任务
	const GUILDTASK = 19;
	//酒馆招将
	const RECRUIT = 20;
	//资源矿
	const MINERAL = 21;
	//寻龙探宝
	const DRAGON = 22;
	//比武
	const COMPETE = 23;
	//神秘商店
	const MYSTERYSHOP = 24;
	//吃烧鸡
	const SUPPLY = 25;
	//采集粮草
	const HARVEST = 26;
	//过关斩将
	const PASS = 27;
	//星魂
	const ATHENA = 28;
	//军团副本
	const GUILDCOPY = 29;
	//水月之镜
	const MOON = 30;
	//天工宝箱
	const MOONBOX = 31;
	//水月之境梦魇模式
	const MOON_NIGHTMARE = 32;
	//跨服比武
	const WORLD_COMPETE = 33;
	//城外boss(攻城略地boss)
	const GUILDCOPY_BOSS = 34;
	//购买体力丹
	const BUY_EXECUTION_PILL = 35;
	//购买耐力丹
	const BUY_STAMINA_PILL = 36;
	//试炼梦魇
	const HELL_TOWER = 37;
	
	
	public static $VALID_TYPES = array(
			self::NCOPY,
			self::ECOPY,
			self::ACOPY,
			self::DIVINE,
			self::HUNT,
			self::FRAGSEIZE,
			self::ARENA,
			self::TOWER,
			self::BOSS,
			self::LOVE,
			self::FAVOR,
			self::ARMRFH,
			self::GUILDREWARD,
			self::SIGN,
			self::MONTHSIGN,
			self::LEARNSKILL,
			self::GUILDCONTRI,
			self::GUILDCOPYTEAM,
			self::GUILDTASK,
			self::RECRUIT,
			self::MINERAL,
			self::DRAGON,
			self::COMPETE, 
			self::MYSTERYSHOP,
			self::SUPPLY,
			self::HARVEST,
			self::PASS,
			self::ATHENA,
			self::GUILDCOPY,
			self::MOON,
			self::MOONBOX,
			self::MOON_NIGHTMARE,
			self::WORLD_COMPETE,
			self::GUILDCOPY_BOSS,
			self::BUY_EXECUTION_PILL,
			self::BUY_STAMINA_PILL,
	        self::HELL_TOWER,
	);
	
	// session key
	const SESSION_KEY = 'active.info';
	
	const ACTIVE_NUM = 'active_num';
	const ACTIVE_POINT = 'active_point';
	const ACTIVE_TYPE = 'active_type';
	const ACTIVE_PRIZE = 'active_prize';
	const ACTIVE_LEVEL = 'active_level';
	const ACTIVE_TASK = 'active_task';
	const ACTIVE_REWARD = 'active_reward';
	const ACTIVE_OPEN_LIMIT = 'active_open_limit';
	
	//SQL表名
	const ACTIVE_TABLE = 't_active';
	//SQL：字段
	const UID = 'uid';
	const POINT = 'point';
	const LAST_POINT = 'last_point';
	const UPDATE_TIME = 'update_time';
	const VA_ACTIVE = 'va_active';
	const TASK = 'task';
	const PRIZE = 'prize';
	const STEP = 'step';
	const TASK_REWARD = 'taskReward';
	
	
	//SQL：表字段
	public static $ACTIVE_FIELDS = array(
			self::UID,
			self::POINT,
			self::LAST_POINT,
			self::UPDATE_TIME,
			self::VA_ACTIVE,
	);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */