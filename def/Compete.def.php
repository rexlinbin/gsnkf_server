<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Compete.def.php 117368 2014-06-26 07:10:38Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Compete.def.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-06-26 07:10:38 +0000 (Thu, 26 Jun 2014) $
 * @version $Revision: 117368 $
 * @brief 
 *  
 **/
class CompeteDef
{	
	//比武有效类型
	const COMPETE_TYPE_RIVAL = 0;									// 比武
	const COMPETE_TYPE_FOE = 1;										// 复仇
	public static $COMPETE_VALID_TYPES = array(self::COMPETE_TYPE_RIVAL, self::COMPETE_TYPE_FOE);
	
	//比武配置表
	const COMPETE_TEMPLATE_ID = 'compete_template_id';				// 比武id
	const COMPETE_POINT_GROUP = 'compete_point_group';				// 比武积分分组
	const COMPETE_SUC_POINT = 'compete_suc_point';					// 比武胜利积分
	const COMPETE_FAIL_POINT = 'compete_fail_point'; 				// 比武失败积分
	const COMPETE_MAX_POINT = 'compete_max_point';					// 比武积分最大值
	const COMPETE_SUC_RATE = 'compete_suc_rate';					// 比武胜利积分比率
	const COMPETE_FAIL_RATE = 'compete_fail_rate';					// 比武失败积分比率
	const COMPETE_COST_STAMINA = 'compete_cost_stamina';			// 比武消耗耐力
	const COMPETE_SUC_EXP = 'compete_suc_exp';						// 比武胜利经验
	const COMPETE_FAIL_EXP = 'compete_fail_exp';					// 比武失败经验
	const COMPETE_SUC_FLOP = 'compete_suc_flop';					// 比武胜利翻牌id
	const COMPETE_LAST_TIME = 'compete_last_time';					// 比武持续时间组
	const COMPETE_REST_TIME = 'compete_rest_time';					// 比武休息时间组
	const COMPETE_REFRESH_TIME = 'compete_refresh_time';			// 比武刷新冷却时间组
	const COMPETE_INIT_POINT = 'compete_init_point';				// 比武初始积分
	const COMPETE_ADD_HONOR = 'compete_add_honor';					// 比武加荣誉值
	const COMPETE_REWARD_ID = 'compete_reward_id';					// 比武奖励id
	const COMPETE_REWARD_MIN = 'compete_reward_min';				// 奖励最小排名
	const COMPETE_REWARD_MAX = 'compete_reward_max';				// 奖励最大排名
	const COMPETE_REWARD_SILVER = 'compete_reward_silver';			// 奖励银币
	const COMPETE_REWARD_SOUL = 'compete_reward_soul';				// 奖励将魂
	const COMPETE_REWARD_GOLD = 'compete_reward_gold';				// 奖励金币
	const COMPETE_REWARD_ITEM = 'compete_reward_item';				// 奖励物品
	const COMPETE_REWARD_HONOR = 'compete_reward_honor';			// 奖励荣誉值
	
	//SQL表名
	const COMPETE_TABLE = 't_compete';
	//SQL：字段
	const COMPETE_UID = 'uid';
	const COMPETE_NUM = 'num';
	const COMPETE_BUY = 'buy';
	const COMPETE_HONOR = 'honor';
	const COMPETE_POINT = 'point';
	const LAST_POINT = 'last_point';
	const POINT_TIME = 'point_time';
	const COMPETE_TIME = 'compete_time';
	const REFRESH_TIME = 'refresh_time';
	const REWARD_TIME = 'reward_time';
	const VA_COMPETE = 'va_compete';
	const RIVAL_LIST = 'rival';
	const FOE_LIST = 'foe';
	
	//SQL：表字段
	public static $COMPETE_FIELDS = array(
			self::COMPETE_UID,
			self::COMPETE_NUM,
			self::COMPETE_BUY,
			self::COMPETE_HONOR,
			self::COMPETE_POINT,
			self::LAST_POINT,
			self::POINT_TIME,
			self::COMPETE_TIME,
			self::REFRESH_TIME,
			self::REWARD_TIME,
			self::VA_COMPETE,
	);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */