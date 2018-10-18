<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Tower.def.php 254606 2016-08-03 12:34:18Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Tower.def.php $
 * @author $Author: GuohaoZheng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-03 12:34:18 +0000 (Wed, 03 Aug 2016) $
 * @version $Revision: 254606 $
 * @brief 
 *  
 **/
class TowerDef
{
	//每天更新挑战次数的时间
	const REFRESH_DEFEAT_NUM_TIME = 100;
	//每天更新的挑战次数
	const REFRESH_DEFEAT_NUM = 5;
	//失败次数限制
	const FAIL_NUM_LIMIT = 2;
	//每天购买挑战次数限制
	const GOLD_DEFEAT_NUM_LIMIT = 10;
	//每天金币通关次数限制
	const GOLD_PASS_NUM_LIMIT = 10;
	//抽奖次数限制（免费抽奖1次+金币抽奖n次）
	const LOTTERY_NUM_LIMIT = 5;
	//第一个塔层的id
	const FIRST_TOWER_LEVEL_ID = 1;	
	//金币抽奖花费金币
	const GOLD_LOTTERY_SPEND = 5;
	
	//表示当前塔层在当前爬塔攻击的状态     1是通关了   0是未通关可攻击
	
	//如当前的最高通关塔层是10，reset之后cur_level是1，sweep（1,3），当前塔层是4，状态是ATTACK
	 
	//如果当前的最高通关塔层是顶层30，cur_level是30，reset之后cur_level是1；
	//sweep(1,29),cur_level是30，状态是ATTACK；sweep(29,30),cur_level是30，状态是PASS；
	const CUR_LEVEL_STATUS_PASS = 1;
	const CUR_LEVEL_STATUS_ATTAK = 0;
	
	const BUY_STOWER_SPENDTYPE_SILV = 1;
	const BUY_STOWER_SPENDTYPE_GOLD = 2;
	
	const RAND_STOWER_MAXLV_DECREASE = 10;
	const RAND_STOWER_NEED_TOWERLV = 10;
}
class TowerLevelStatus
{
	const NOTOPEN = -1;
	const SHOW = 0;
	const ATTACK = 1;
	const PASS = 2;
	//
}

class TOWERTBL_FIELD
{
    const UID = 'uid';
    const MAX_LEVEL = 'max_level';
    const MAX_LEVEL_TIME = 'max_level_time';
    const LAST_REFRESH_TIME = 'last_refresh_time';
    const RESET_NUM = 'reset_num';
    const CAN_FAIL_NUM = 'can_fail_num';
    const GOLD_BUY_NUM = 'gold_buy_num';
    const BUY_ATK_NUM  = 'buy_atk_num';
    const BUY_SPECIAL_NUM = 'buy_special_num';
    const VA_TOWER_INFO = 'va_tower_info';
    const VA_TOWER_CURSTATUS = 'cur_status';
    const VA_TOWER_PROGRESS = 'progress';
    const CURRENT_LEVEL = 'cur_level';
    const VA_TOWER_SWEEPINFO = 'sweep_info';
    const VA_TOWER_SPECIALTOWER = 'special_tower';
    const VA_TOWER_SPECIALTOWER_LIST = 'specail_tower_list';
    const VA_TOWER_SPECAIL_TOWERID = 0;
    const VA_TOWER_SPECAIL_TOWERSTARTTIME = 1;
    const VA_TOWER_SPECAIL_TOWERDEFEATNUM = 2;
    const MAX_HELL = 'max_hell';
    const CUR_HELL = 'cur_hell';
    const RESET_HELL = 'reset_hell';
    const CAN_FAIL_HELL = 'can_fail_hell';
    const GOLD_BUY_HELL = 'gold_buy_hell';
    const BUY_HELL_NUM = 'buy_hell_num';
    const VA_TOWER_HELL_STATUS = 'cur_hell_status';
    const VA_TOWER_HELL_SWEEPINFO = 'sweep_hell_info';
    
    public static $TBL_TOWER_ALL_FIELD = array(
            TOWERTBL_FIELD::UID,
            TOWERTBL_FIELD::MAX_LEVEL,
            TOWERTBL_FIELD::MAX_LEVEL_TIME,
            TOWERTBL_FIELD::CURRENT_LEVEL,
            TOWERTBL_FIELD::RESET_NUM,
            TOWERTBL_FIELD::CAN_FAIL_NUM,
            TOWERTBL_FIELD::LAST_REFRESH_TIME,
            TOWERTBL_FIELD::GOLD_BUY_NUM,
            TOWERTBL_FIELD::BUY_ATK_NUM,
    		TOWERTBL_FIELD::BUY_SPECIAL_NUM,
            TOWERTBL_FIELD::MAX_HELL,
            TOWERTBL_FIELD::CUR_HELL,
            TOWERTBL_FIELD::RESET_HELL,
            TOWERTBL_FIELD::CAN_FAIL_HELL,
            TOWERTBL_FIELD::GOLD_BUY_HELL,
            TOWERTBL_FIELD::BUY_HELL_NUM,
            TOWERTBL_FIELD::VA_TOWER_INFO,
    );
    
    const SWEEP_INFO_START_TIME = 'start_time';
    const SWEEP_INFO_START_LEVEL = 'start_level';
    const SWEEP_INFO_END_LEVEL = 'end_level';
    
}

class HellTowerDef
{
    const FIRST_HELL_TOWER_LEVEL_ID = 1;
    
    const TOWER_TYPE_NORMAL = 1; // 试练普通
    const TOWER_TYPE_HELL = 2;   // 试炼噩梦
}

class HellTowerFloorDef
{
    const ID = 'id';               // ID
    const LEVEL = 'level';         // 等级限制
    const NUM = 'num';             // 可重置次数
    const BUY_RESET_GOLD = 'reset_gold'; //购买重置次数花费金币
    const MAX_RESET_NUM = 'reset'; // 次数上限
    const LOSE_NUM = 'lose';       // 失败次数
    const TIME = 'time';           // 扫荡时间
    const BASE_GOLD = 'base';      // 基础金币
    const GROW_GOLD = 'grow';      // 递增金币
    const MAX_FAIL_NUM = 'fail';   // 购买失败次数上限
    const SWEEP_GOLD = 'sweep';    // 扫荡每层需要金币
}

class HellTowerLevelDef
{
    const ID = 'id';                 // ID
    const REWARD = 'reward';         // 奖励
    const PASS_OPEN = 'pass_open';   // 通关开启层数
    const NEED_LEVEL = 'need_level'; // 攻打需要等级
    const BASE_ID = 'base';          // 据点id
}

class HellTowerGoodsDef
{
    const ID = 'id';                 // 商品ID 
    const ITEMS = 'items';           // 物品
    const PRICE = 'price';           // 价格
    const TYPE = 'type';             // 刷新类型
    const NUM = 'num';               // 初始次数
    const LEVEL = 'level';           // 需要的兑换等级
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */