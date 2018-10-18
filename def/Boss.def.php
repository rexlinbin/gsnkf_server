<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Boss.def.php 178608 2015-06-12 09:00:10Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Boss.def.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-06-12 09:00:10 +0000 (Fri, 12 Jun 2015) $
 * @version $Revision: 178608 $
 * @brief 
 *  
 **/
class BossDef
{
	const FLAGS_SUB_CD_TIME			= 1;
	
	const BOSS_ID 					= 'boss_id';
	const BOSS_LEVEL 				= 'level';
	const BOSS_HP 					= 'hp';
	const START_TIME 				='start_time';
	const SUPERHERO_REFRESH_TIME 	= 'refresh_time';
	const BOSS_VA 					= 'va_boss';
	
	const BOSS_MAXHP 				= 'boss_maxhp';
	const ADDITION					= 'boss_addition';
	const ATK_UID 					= 'uid';
	const LAST_ATK_TIME 			= 'last_attack_time';
	const ATK_HP 					= 'attack_hp';	
	const ATK_NUM 					= 'attack_num';
	const LAST_INSPIRE_TIME 		= 'inspire_time_silver';
	const LAST_INSPIRE_TIME_GOLD	= 'inspire_time_gold';
	const INSPIRE 					= 'inspire';
	const REVIVE					= 'revive';
	const FLAGS 					= 'flags';
	const FORMATION_SWITCH			= 'formation_switch';
	const VA_BOSS_ATK				= 'va_boss_atk';
	const ATK_UNAME                 = 'uname';
	const ATK_RANK 					= 'atk_rank';
	const ATK_ADDITION				= 'atk_addition';
	
	
	const ATK_LIST 					= 'atkList';
	const BOSS_COST_HP 				= 'bossAtkHp';
	const LOCK_PREFIX 				=	'boss_lock_';
	const REWARD_SILVER 			= 'rewardSilver';
	const REWARD_PRESTIGE 				= 'rewardPrestige';
	const REWARD_GOLD 				= 'rewardGold';
	const REWARD_ITEMS 				= 'rewardItem';
	
	
	const BASE_ID 					= 'base_id';
	const BOSS_INIT_LEVEL			= 'boss_init_level';
	const BOSS_MIN_LEVEL			= 'boss_min_level';
	const BOSS_MAX_LEVEL			= 'boss_max_level';

	const BOSS_KILLER				= 'boss_killer';
	const KILL_TIME					= 'kill_time';
	const TOPTHREE					= 'top_three';
	const SUPERHERO			= 'superhero';
	const SUPERHERO_GOOD			= 'superhero_good';
	const SUPERHERO_BETTER			= 'superhero_better';
	const SUPERHERO_BEST			= 'superhero_best';
	const SUPERHERO_DROP_TPL		= 'superhero_drop_tpl';
	const BOSS_START_TIME			= 'boss_start_time';
	const BOSS_END_TIME				= 'boss_end_time';
	const BOSS_DAY_LIST				= 'boss_day_list';
	const BOSS_WEEK_LIST			= 'boss_week_list';
	const BOSS_DAY_START_TIMES		= 'boss_day_start_times';
	const BOSS_DAY_END_TIMES		= 'boss_day_end_times';
	const SUPERHERO_NUM_ARR			= 'superhero_num_arr';

	const NEWBOSS_DAY				= 'newboss_days';
	const NEWBASE_ID				= 'new_base_id';
	const NEWREWARD_ID				= 'new_reward_id';
	const NEWBOSS_NEEDLV			= 'new_boss_neelv';
 	const LV_TIME					= 'lv_time';
 	const CHANGE_REWARD1			= 'change_reward_1';
 	const CHANGE_REWARD2			= 'change_reward_2';
	
	
	const REWARD_ID					= 'reward_id';
	const REWARD_SILVER_BASIC		= 'reward_silver_basic';
	const REWARD_PRESTIGE_BASIC			= 'reward_prestige_basic';
	const REWARD_ITEM_BASIC			= 'reward_item';
	
	const REWARD_ORDER_LIST_NUM		= 'reward_order_list_num';
	const REWARD_ORDER_LIST			= 'reward_order_list';
	
	
	const REWARD_ORDER_LOW			= 'reward_order_low';
	const REWARD_ORDER_UP			= 'reward_order_up';
	const REWARD_INFO				= 'reward_info';
	const REWARD_DROP_TEMPLATE_ID	= 'reward_drop_id';
	
	const IR_ID						= 'inspire_revive_id';
	const ADDITION_ARR				= 'addition_arr';
	const INSPIRE_BASERATIO			= 'inspire_base_ratio';
	const INSPIRE_LIMIT				= 'inspire_limit';
	const INSPIRE_BASE_RATIO		= 'inspire_base_ratio';
	const INSPIRE_SUCCESS_RATIO		= 'inspire_success_ratio';
	const INSPIRE_SILVER_CD			= 'inspire_silver_cd';
	const INSPIRE_NEED_SILVER		= 'inspire_need_silver';
	const INSPIRE_NEED_GOLD		= 'inspire_need_gold';
	const REBIRTH_GOLD_BASE		= 'rebirth_gold_base';
	const REBIRTH_GOLD_INC		= 'rebirth_gold_inc';
	const ATK_CD				= 'atk_cd';
	
	const ATK_TABLE = 't_boss_atk';
	const BOSS_TABLE = 't_boss';
	
	const FLAGS_SUB_CD		=	1;
	const SESSION_KILLER = 'killer';
	const SWITCH_OPEN = 1;
	const SWITCH_CLOSE = 0;
	
	static $heroNeedInfo = array(
		'hid','htid','position','level','evolve_level',
	);
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */