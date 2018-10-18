<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Pass.def.php 258597 2016-08-26 08:40:27Z MingmingZhu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Pass.def.php $
 * @author $Author: MingmingZhu $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-08-26 08:40:27 +0000 (Fri, 26 Aug 2016) $
 * @version $Revision: 258597 $
 * @brief 
 *  
 **/
class PassDef
{
	const PASS_SESSION = 'pass.info';
	const PASS_MEM_KEY = 'pass.battleInfo';
	
	const VA_HEROINFO = 'heroInfo';
	const VA_CHESTSHOW = 'chestShow';
	const VA_BUFFSHOW = 'buffShow';
	const VA_BUFFINFO = 'buffInfo';
	const VA_FORMATION = 'formation';
	const VA_OPPINFO = 'opponentInfo';
	const VA_UNION= 'unionInfo';
	const VA_BENCH = 'bench';
	const VA_SECONDATTR = 'secondAttrInfo';
	const VA_SWEEPINFO = 'sweepInfo';
	
	const HP_PERCENT = 'currHp';
	const RAGE = 'currRage';
	const EQUIP = 'equipInfo';
	
	const SESSKEY = 'pass.atk';
	
	
	static $allFields = array(
			'uid',
			'refresh_time',
			'luxurybox_num',
			'cur_base',
			'reach_time',
			'pass_num',
			'point',
			'star_star',
			'coin',
			'reward_time',
			'lose_num',
			'buy_num',
			'va_pass',
	);
	
	static $rankShowFields = array(
			'uid', 'pass_num', 'point',
	);
	
	static $rankShowUserFields = array(
		'uname', 'utid', 'level', 'fight_force', 'guild_id', 'vip','dress', 'htid' 
	);
	
	static $rankShowGuildFields = array(
		'guild_name',
	);
	
	static $rankRewardFields = array(
		'uid', /* 'reward_time' */
	);
	//前段需要的对手的用户级别和英雄级别的信息
	static $oppoUserFieldForFront = array( 
			'uid', 'name', 'level', 'fightForce',
	 );
	static $oppoHeroFieldForFront = array(
			'hid', 'htid', 'level', 'evolve_level', 'currRage', PropertyKey::DRESS_INFO,
	);

	static $myHeroInfoInVa = array(
		'currRage'/* ,'equipInfo' */
	);
	
	static $unsetFieldForFront = array(
		'reach_time', 'reward_time',
	);
	
	static $unsetFieldInVaForFront = array(
		/* self::VA_OPPINFO, self::VA_UNION,*/
	);
	
	static $unsetAfterAttack = array(
		'refresh_time', 'luxurybox_num', 'reach_time', 'coin','reward_time'
	);
	
	static $unsetHeroField = array(
		self::EQUIP,
	);
	
	const ATTACK_BEFORE_NOT = 0;
	const ATTACK_BEFORE = 1;
	
	const CHEST_STATUS_UNDEAL = 0;
	const CHEST_STATUS_DEAL = 1;//箱子购买没上限,所有不需要有一个购买状态了
	
	const BUFF_STATUS_UNDEAL = 0;
	const BUFF_STATUS_DEAL = 1;
	
	const HERITAGE = 0;
	const REVIVE = 1;
	
	const TYPE_ADDITION = 1;
	const TYPE_RECOVER_HP = 2;
	const TYPE_RECOVER_RAGE = 3;
	const TYPE_REVIVE = 4;
	
	const LEAVE_BUFF = 999;
	
	const PASS_SHOP_GOODS_ARRAY_NUM = 10; 							// 神兵商店中商品组数
	
	public static $ExcludeTeam = array(0,1);						// 特殊格子索引，从0开始，当天刷出来过的商品，就不能再被刷出来啦
	
	const TBL_FIELD_VA_ALL = 'all';									//商品购买信息
	const TBL_FIELD_VA_GOODS_LIST = 'goods_ist';					//商品列表
	const TBL_FIELD_VA_LAST_SYS_RFR_TIME = 'last_sys_rfr_time';		//最后一次系统刷新时间
	const TBL_FIELD_VA_LAST_USR_RFR_TIME = 'last_usr_rfr_time';		//最后一次玩家刷新时间
	const TBL_FIELD_VA_FREE_RFR_NUM = 'free_refresh';	
	const TBL_FIELD_VA_USR_GOLD_RFR_NUM = 'usr_rfr_num';					//当日玩家主动刷新次数
	const TBL_FIELD_VA_USR_STONE_RFR_NUM = 'usr_stone_rfr_num';		//当日玩家使用“神兵刷新石进行刷新”的次数（与使用金币刷新（TBL_FIELD_VA_USR_GOLD_RFR_NUM）区分开）
	const TBL_FIELD_VA_EXCLUDE = 'exclude';							//针对特殊格子里的商品，当天刷出来过，就不能再被刷出来啦
	
	const PASSSHOP_SPEND_TYPE_WEAPON_COIN = 1;						//神兵商店消费类型之神兵币
	const PASSSHOP_SPEND_TYPE_GOLD = 2;								//神兵商店消费类型之金币
	const PASSSHOP_SPEND_TYPE_SILVER = 3;							//神兵商店消费类型之银币
	
	const TYPE_RFR_GOLD = 1;				// 神兵商店商品列表刷新消耗类型——金币
	const TYPE_RFR_STONE = 2;				// 神兵商店商品列表刷新消耗类型——神兵刷新石
}

class PassShopCsvTag
{
	const ID				 			= 'id'; 					// id
	const SYS_REFRESH_INTERVAL 			= 'sys_refresh_interval'; 	// 系统刷新时间间隔
	const USR_REFRESH_COST    			= 'usr_refresh_cost';		// 玩家刷新花费数组
	const GOODS_NUM						= 'goods_num';				// 刷新的商品数量数组
	const GOODS_ARRAY					= 'goods_array';			// 所有商品的id,按组区分
	const REFRESH_LIMIT					= 'refresh_limit';			// 玩家每天刷新商品列表上限
	const FREE_REFRESH    				= 'free_refresh';			//玩家每天免费刷新次数
	const USR_REFRESH_STONE				= 'usr_refresh_stone_cost';		// 玩家刷新花费神兵刷新石
}

class PassGoodsCsvTag
{
	const GOODS_ID      				= 'goods_id';				// 商品ID
	const GOODS_ITEM					= 'goods_item';				// 商品内容
	const COST_TYPE						= 'cost_type';				// 花费类型
	const COST_NUM						= 'cost_num';				// 花费数值
	const LIMIT_TYPE					= 'limit_type';				// 限制类型
	const LIMIT_NUM						= 'limit_num';				// 限制次数
	const GOODS_WEIGHT					= 'goods_weight';			// 商品权重
	const IS_SOLD						= 'is_sold';				// 是否出售，如果不能出售，也不会被读入表中
	const NEED_LEVEL					= 'need_level';				// 需要等级
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */