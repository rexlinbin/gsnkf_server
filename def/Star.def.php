<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Star.def.php 139255 2014-11-10 08:45:44Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Star.def.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-11-10 08:45:44 +0000 (Mon, 10 Nov 2014) $
 * @version $Revision: 139255 $
 * @brief 
 *  
 **/

class StarDef
{
	// session key
	const STAR_SESSION_KEY = 'star.all';
	const TRIGER_SESSION_KEY = 'star.triger';
	
	const STAR_GOLD_BASE = 'star_gold_base';						// 赠送金币的基础值
	const STAR_GOLD_INCRE = 'star_gold_incre';						// 赠送金币的递增值
	const STAR_GOLD_FAVOR = 'star_gold_favor';						// 赠送金币的好感度
	const STAR_GOLD_MAX = 'star_gold_max'; 							// 赠送金币的次数上限	
	const STAR_RATIO_ONE = 'star_ratio_one';						// 暴击参数1
	const STAR_RATIO_TWO = 'star_ratio_two';						// 暴击参数2
	const STAR_SWAP_COST = 'star_swap_cost';						// 名将互换星级花费
	const STAR_QUALITY = 'star_quality';							// 名将品质
	const STAR_FAVOR_GIFT = 'star_favor_gift'; 						// 名将喜爱的礼物
	const STAR_FAVOR_ACT = 'star_favor_act';						// 名将喜爱的行为
	const STAR_FAVOR_ABILITY = 'star_favor_ability';				// 名将的特殊能力
	const STAR_NEED_HER0 = 'star_need_hero';						// 名将激活所需武将
	const STAR_LEVEL_ID	= 'star_level_id';							// 名将升级所需经验表id
	const STAR_CAN_FEEL = 'star_can_feel';							// 名将是否能感悟
	const STAR_FEEL_SKILLS = 'star_feel_skills';					// 名将感悟等级对应技能ID组
	const STAR_CHALLENGE_ARMY = 'star_challenge_army';				// 挑战对应部队ID组
	const STAR_FEEL_ABILITY = 'star_feel_ability';					// 名将感悟属性ID组
	const STAR_FEEL_EXP = 'star_feel_exp';							// 感悟成长经验表ID
	const STAR_NORMAL_SKILLS = 'star_normal_skills';				// 更换普通技能ID
	const STAR_ACT_TRIGER = 'star_act_triger';						// 行为触发的答题事件
	const STAR_STAMINA_BASE = 'star_stamina_base';					// 消耗耐力的基础值
	const STAR_STAMINA_INCRE = 'star_stamina_incre';				// 消耗耐力递增值
	const STAR_REWARD_TYPE = 'star_reward_type';					// 增进感情的奖励类型
	const STAR_REWARD_NUM = 'star_reward_num';						// 增进感情的奖励数量
	const STAR_ABILITY_ATTR = 'star_ability_attr';					// 名将能力加成的属性信息
	const STAR_ABILITY_ITEM = 'star_ability_Item';					// 名将能力奖励物品
	const STAR_ABILITY_REWARD = 'star_ability_reward';				// 名将能力奖励资源
	const STAR_ABILITY_STAMINA = 'star_ability_stamina';			// 名将能力增加耐力上限
	const STAR_MAX_LEVEL = 'star_max_level';						// 名将等级上限
	const STAR_FAVOR_LEVEL = 'star_favor_level';					// 名将好感度等级
	const STAR_FAVOR_RATIO = 'star_favor_ratio';					// 名将好感度升级暴击
	const STAR_DRAW_FREE = 'star_draw_free';						// 名将每日免费翻牌次数
	const STAR_CHALLENGE_FREE = 'star_challenge_free';				// 名将每日免费挑战次数
	const STAR_DRAW_COST = 'star_draw_cost';						// 购买翻牌次数金币组
	const STAR_CHALLENGE_COST = 'star_challenge_cost';				// 购买挑战次数金币组
	const STAR_DRAW_COMBINATION = 'star_draw_combination';			// 翻牌组合类型名称表
	const STAR_DRAW_DROP = 'star_draw_drop';						// 翻牌掉落武将ID组
	const STAR_SHUFFLE_COST = 'star_shuffle_cost';					// 洗牌需要金币
	const STAR_CHALLENGE_FEEL = 'star_challenge_feel';				// 挑战增加感悟值组
	const STAR_SPECIAL_COST = 'star_special_cost';					// 一键最大金币
	const STAR_LIST	= 'star_list';
	const STAR_NUM = 'star_num';
	const PATTERN = 0;
	
	//SQL：表名
	const STAR_TABLE_STAR  = 't_star';	
	const STAR_TABLE_ALL_STAR = 't_all_star';										
	
	//SQL：字段
	const STAR_ID = 'star_id';
	const STAR_TID = 'star_tid';
	const STAR_LEVEL = 'level';
	const STAR_TOTAL_EXP = 'total_exp';
	const STAR_FEEL_SKILL = 'feel_skill';
	const STAR_FEEL_LEVEL = 'feel_level';
	const STAR_FEEL_TOTAL_EXP = 'feel_total_exp';
	const STAR_PASS_HCOPY_NUM = 'pass_hcopy_num';
	const STAR_USER_ID = 'uid';								
	const STAR_SEND_NUM = 'send_num';
	const STAR_SEND_TIME = 'send_time';
	const STAR_DRAW_NUM = 'draw_num';
	const STAR_VA_INFO = 'va_act_info';
	const STAR_ACT = 'act';
	const STAR_DRAW = 'draw';
	const STAR_SKILL = 'skill';
	
	//SQL：表字段
	public static $STAR_FIELDS = array(
			self::STAR_ID,
			self::STAR_TID,
			self::STAR_LEVEL,
			self::STAR_TOTAL_EXP,
			self::STAR_FEEL_SKILL,
			self::STAR_FEEL_LEVEL,
			self::STAR_FEEL_TOTAL_EXP,
			self::STAR_PASS_HCOPY_NUM,	
	);
	
	public static $ALL_STAR_FIELDS = array(
			self::STAR_SEND_NUM,
			self::STAR_SEND_TIME,
			self::STAR_DRAW_NUM,
			self::STAR_VA_INFO		
	);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */