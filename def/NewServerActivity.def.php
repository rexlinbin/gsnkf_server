<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NewServerActivity.def.php 243206 2016-05-17 10:05:43Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/NewServerActivity.def.php $
 * @author $Author: MingTian $(linjiexin@babeltime.com)
 * @date $Date: 2016-05-17 10:05:43 +0000 (Tue, 17 May 2016) $
 * @version $Revision: 243206 $
 * @brief 
 *  “开服7天乐”def文件
 **/
class NewServerActivityCsvDef
{
	const ID = "ID";
	const RQE = "REQUIRE";
	const REWARD = "REWARD";
	const CLOSEDAY = "CLOSEDAY";
	const TASKID = "TASKID";
	const TYPE = "TYPE";
	const OPENDAY = "OPENDAY";
	const DEADLINE = "DEADLINE";
	const ITEMS = "itemArr";
	const PRICE = "currentPrice";
	const LIMITNUM = "limitNum";
	const LIMITRATIO = "limitRatio";
	const GOODS = "GOODS";
}

class NewServerActivitySqlDef
{
	// 数据表名称
	const T_NEW_SERVER_ACT = 't_new_server_activity';
	const T_NEW_SERVER_GOODS = 't_new_server_goods';
	
	// 表字段
	const UID = 'uid';
	const VA_INFO = 'va_info';
	const VA_GOODS = 'va_goods';
	const TASKINFO = 'taskInfo';
	const DAY = 'day';
	const BUY_NUM = 'buy_num';
	
	public static $arrColumn = array(
			self::UID,
			self::VA_INFO,
			self::VA_GOODS,
	);
	
	public static $goodsColumn = array(
			self::DAY,
			self::BUY_NUM,
	);
}

class NewServerActivityDef
{
	const UPLINE_TIME = 'UPLINETIME';
	
	const ACHIEVE = 1;  // 表示任务是 成就 的情况
	const GOLD = 2;
	const STATUS = 's';
	const FINISHNUM = 'fn';
	const PURCHASE = 'purchase';
	const TASK_INFO = 'taskInfo';
	const LIMIT = 'limit';
	const NOT_LIMIT = 'notLimit';
	const BUYFLAG = 'buyFlag';
	const REMAIN = 'remainNum';
	const RET = 'ret';
	
	// 任务类型
	const DEFAULTTYPE = 'defaultType';    // 默认常规类型
	const RANKTYPE  = 'rankType';     // 排名类型
	const COPYTYPE = 'copyType';		// 副本类型
	const ACCUMTYPE	 = 'accumType';		// 累加类型，目前配置表只有 “探索”是这种类型的任务
	const USER_LEVEL = 101;     // 玩家主角等级
	const GOLD_RECRUIT = 102;		//神将抽将N次
	const FIGHT_FORCE = 103;		//战斗力
	const PASS_COPY = 104;     // 通关“主线副本”
	const TREASURE_STRONG_12 = 106;     //12件宝物强化都达到N级
	const BLUE_TREASURE = 107;			//获得N个蓝色宝物
	const PURPLE_TREASURE = 108;		//获得N个紫色宝物
	const TWELVE_TREASURE_MAGIC_LEVEL = 109;	 // 12件宝物精炼均达到N级
	const ANY_TREASURE_MAGIC_LEVEL = 110;	 // 任意宝物精炼最高等级达到N级
	const ARENA_RANK = 111;		// 竞技场排名达到N名次
	const COST_PRESTIGE = 112;		// 消耗N点竞技场声望
	const COST_JEWEL = 113;		// 消耗N个数量的魂玉
	const COST_GOLD_IN_PROPERTY_SHOP = 114;		//道具商店消耗N个金币		
	const PASS_TOWER = 116;		//通关到N层试练塔
	const RESET_TOWER = 117;	//重置N次试练塔
	const ATTACK_BOSS = 119;		//单次攻打boss伤害达到N万
	const SPECIAL_DONATION = 120;		//军团特级捐献N次
	const ULTIMATE_DONATION = 121;		//军团究极捐赠N次
	const GUILD = 122;		//加入或创建军团
	const MINERAL = 123;		//占领N次资源矿 
	const ACCSIGN = 124;		// 开服第N天(累计登陆)
	const RECHARGE_GOLD = 125;		//累积充值N金币
	
	const EQUIP_STRONG_PREFIX = "EQUIP_STRONG_";
	const EQUIP_STRONG_6 = 105;     //6名伙伴的装备都强化到N级	

	const ADD_PURPLE_FAVOR_PREFIX = "ADD_PURPLE_FAVOR_";
	const ADD_PURPLE_FAVOR_1 = 115; //1个紫色武将好感达到N级
	const ADD_PURPLE_FAVOR_5 = 126; //5个紫色武将好感达到10级
	
	const STRONG_PURPLE_FIGHTSOUL_PREFIX = "STRONG_PURPLE_FIGHTSOUL_";		
	const STRONG_PURPLE_FIGHTSOUL_1 = 118; //任意1个紫色战魂强化到N级
	const STRONG_PURPLE_FIGHTSOUL_6 = 127; //任意6个紫色战魂强化到N级
	
	public static $ALL_SPECIAL_TYPES = array(
			"EQUIP_STRONG_6" => self::EQUIP_STRONG_6,
			"ADD_PURPLE_FAVOR_1" => self::ADD_PURPLE_FAVOR_1,
			"ADD_PURPLE_FAVOR_5" => self::ADD_PURPLE_FAVOR_5,
			"STRONG_PURPLE_FIGHTSOUL_1" => self::STRONG_PURPLE_FIGHTSOUL_1,
			"STRONG_PURPLE_FIGHTSOUL_6" => self::STRONG_PURPLE_FIGHTSOUL_6,
	);
	
	//副本类型数组,以后新增“精英副本类型”或者“活动副本类型”时记得加入到这数组中
	public static $NCOPY_TYPES = array(
			self::PASS_COPY,
	);
	
	// 竞技场的排名范围上限值
	const MAX_BOSS_RANK = 1000000000;
	
	// session中的key
	const KEY_NEW_SERVER_ACT = 'newServerActivity.activityInfo';
	const KEY_NEW_SERVER_GOODS = 'newServerActivity.goodsInfo';
	
	/*
	 * 任务的完成与领奖状态
	*/
	const WAIT = 0;		// 代表任务未完成
	const COMPLETE = 1; // 代表任务完成未领奖
	const REWARDED = 2; // 代表该任务已经领过奖
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */