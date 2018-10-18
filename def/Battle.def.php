<?php
/**********************************************************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Battle.def.php 259834 2016-09-01 02:37:07Z BaoguoMeng $
 * 
 **********************************************************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Battle.def.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2016-09-01 02:37:07 +0000 (Thu, 01 Sep 2016) $
 * @version $Revision: 259834 $
 * @brief 
 *  
 **/

/**********************************************************************************************************************
 * Class       : 
 * Description : 
 * Inherit     : 
 **********************************************************************************************************************/
class BattleDef
{
	static $ARR_BATTLE_KEY_UNSET_IF_0 = array
	(
			PropertyKey::FATAL_RESIST,
			PropertyKey::PARRY_RESIST,
			PropertyKey::PVP_DAMAGE_ADDITION,
			PropertyKey::PVP_DAMAGE_RESIS_ADDITION,
			PropertyKey::BURN_DAMAGE_ADDITION,
			PropertyKey::POISON_DAMAGE_ADDITION,
			PropertyKey::BURN_RESIS_ADDITION,
			PropertyKey::POISON_RESIS_ADDITION,
			PropertyKey::CHARM_SKILL,
			PropertyKey::LAUGH_SKILL,
			PropertyKey::CHAOS_SKILL,
			PropertyKey::PARRY_SKILL,
			PropertyKey::DODGE_SKILL,
			PropertyKey::DEATH_SKILL,
			PropertyKey::ROUND_BEGIN_SKILL,
			PropertyKey::BIG_ROUND_BEGIN_SKILL,
			PropertyKey::ROUND_END_SKILL,
			PropertyKey::COUNTRY_RESTRAIN_WEI,
			PropertyKey::COUNTRY_RESTRAIN_SHU,
			PropertyKey::COUNTRY_RESTRAIN_WU,
			PropertyKey::COUNTRY_RESTRAIN_QUN,
			PropertyKey::COUNTRY_COUNTER_WEI,
			PropertyKey::COUNTRY_COUNTER_SHU,
			PropertyKey::COUNTRY_COUNTER_WU,
			PropertyKey::COUNTRY_COUNTER_QUN,
			PropertyKey::MAX_SUFFER_DAMAGE_REVERSE,
			PropertyKey::RAGE_GET_RATIO,
			PropertyKey::FATAL_RATIO,
			PropertyKey::ABSOLUTE_ATK_RATIO,
			PropertyKey::ABSOLUTE_DFS_RATIO,
			PropertyKey::MODIFY_CURE_RATIO,
			PropertyKey::MODIFY_BECURE_RATIO,
	);
	
	static $ARR_BATTLE_KEY_UNSET_IF_EMPTY_ARRAY = array
	(
			PropertyKey::ARR_SKILL,
			PropertyKey::ARR_IMMUNED_BUFF,
			PropertyKey::ARR_IMMUNED_SKILL_TYPE,
			PropertyKey::ARR_IMMUNED_TARGET_TYPE,
			PropertyKey::ARR_IMMUNED_TRIGGER_CONDITION,
			PropertyKey::ARR_ATTACK_SKILL,
			PropertyKey::ARR_RAGE_SKILL,
			PropertyKey::ARR_DEATH_SKILL,
			PropertyKey::ARR_PARRY_SKILL,
			PropertyKey::ARR_DODGE_SKILL,
			PropertyKey::ARR_ATTACK_BUFF,
			PropertyKey::ARR_RAGE_BUFF,
			PropertyKey::ARR_DEATH_BUFF,
			PropertyKey::ARR_PARRY_BUFF,
			PropertyKey::ARR_DODGE_BUFF,
	);

	static $ARR_BATTLE_KEY = array (
			PropertyKey::HID 						  => 'int',
	        PropertyKey::HTID                         => 'int',
	        PropertyKey::GENDER                       => 'int', 
			PropertyKey::BASE_HTID 					  => 'int',
			PropertyKey::LEVEL 						  => 'int',
			PropertyKey::POSITION 					  => 'int',
			PropertyKey::FIGHT_FORCE 				  => 'int',
			
			PropertyKey::MAX_HP 					  => 'int',
			PropertyKey::CURR_HP 					  => 'int_empty',
			PropertyKey::CURR_RAGE					  => 'int_empty',
			
			PropertyKey::STRENGTH	 				  => 'int',
			PropertyKey::INTELLIGENCE	 			  => 'int',
			PropertyKey::REIGN		 				  => 'int',
			
			PropertyKey::HIT						  => 'int',
			PropertyKey::PARRY		 				  => 'int',			
			PropertyKey::DODGE		 				  => 'int',
			PropertyKey::FATAL 						  => 'int',
            PropertyKey::FATAL_RATIO                  => 'int_empty',
			PropertyKey::FATAL_RESIST                 => 'int_empty',
			PropertyKey::PARRY_RESIST				  => 'int_empty',
			
			PropertyKey::RAGE_GET_BASE			 	  => 'int',
			PropertyKey::RAGE_GET_RATIO 			  => 'int_empty',
			PropertyKey::RAGE_GET_AMEND 			  => 'int',
			
			PropertyKey::ARR_ATTACK_SKILL			  => 'array_int_empty',
			PropertyKey::ARR_RAGE_SKILL			  	  => 'array_int_empty',
			PropertyKey::ARR_DEATH_SKILL			  => 'array_int_empty',
			PropertyKey::ARR_PARRY_SKILL			  => 'array_int_empty',
			PropertyKey::ARR_DODGE_SKILL			  => 'array_int_empty',
			PropertyKey::ARR_ROUND_BEGIN_SKILL		  => 'array_int_empty',
			PropertyKey::ARR_ROUND_END_SKILL		  => 'array_int_empty',
					
			PropertyKey::ARR_ATTACK_BUFF			  => 'array_int_empty',
			PropertyKey::ARR_RAGE_BUFF			  	  => 'array_int_empty',
			PropertyKey::ARR_PARRY_BUFF			  	  => 'array_int_empty',
			PropertyKey::ARR_DODGE_BUFF			  	  => 'array_int_empty',
			
			PropertyKey::ARR_IMMUNED_BUFF 		  	  => 'array_int_empty',
			PropertyKey::ARR_IMMUNED_SKILL_TYPE	 	  => 'array_int_empty',
			PropertyKey::ARR_IMMUNED_TARGET_TYPE	  => 'array_int_empty',
			PropertyKey::ARR_IMMUNED_TRIGGER_CONDITION=> 'array_int_empty',
			
			PropertyKey::ARR_SKILL 					  => 'array_int_empty',
			PropertyKey::CHAOS_SKILL 				  => 'int_empty', 
			PropertyKey::CHARM_SKILL 				  => 'int_empty',
			PropertyKey::LAUGH_SKILL 				  => 'int_empty',
			PropertyKey::ATTACK_SKILL 				  => 'int', 			 
			PropertyKey::RAGE_SKILL 				  => 'int',
			PropertyKey::PARRY_SKILL 				  => 'int_empty',
			PropertyKey::DODGE_SKILL 				  => 'int_empty',
			PropertyKey::DEATH_SKILL				  => 'int_empty',
			PropertyKey::ROUND_BEGIN_SKILL			  => 'int_empty',
			PropertyKey::BIG_ROUND_BEGIN_SKILL		  => 'int_empty',
			
			PropertyKey::PHYSICAL_ATTACK_BASE 		  => 'int',
			PropertyKey::PHYSICAL_ATTACK_ADDITION 	  => 'int',			 
			PropertyKey::PHYSICAL_DEFEND_BASE 		  => 'int',
			PropertyKey::PHYSICAL_DEFEND_ADDITION 	  => 'int',
			PropertyKey::PHYSICAL_ATTACK_RATIO 		  => 'int',
			PropertyKey::PHYSICAL_DAMAGE_IGNORE_RATIO => 'int',
			
// 			PropertyKey::KILL_ATTACK_BASE 			  => 'int', 
// 			PropertyKey::KILL_ATTACK_ADDITION 		  => 'int',
// 			PropertyKey::KILL_DEFEND_BASE 			  => 'int',
// 			PropertyKey::KILL_DEFEND_ADDITION 		  => 'int', 			
// 			PropertyKey::KILL_ATTACK_RATIO 			  => 'int', 
// 			PropertyKey::KILL_DAMAGE_IGNORE_RATIO 	  => 'int', 
			
			PropertyKey::MAGIC_ATTACK_BASE 			  => 'int',
			PropertyKey::MAGIC_ATTACK_ADDITION 		  => 'int',
			PropertyKey::MAGIC_DEFEND_BASE 			  => 'int',
			PropertyKey::MAGIC_DEFEND_ADDITION 		  => 'int',			
			PropertyKey::MAGIC_ATTACK_RATIO  		  => 'int',
			PropertyKey::MAGIC_DAMAGE_IGNORE_RATIO 	  => 'int',
			
			
// 			PropertyKey::WIND_ATTACK_BASE 			  => 'int',
// 			PropertyKey::WIND_ATTACK_ADDITION 		  => 'int',
// 			PropertyKey::WIND_DEFEND_BASE 			  => 'int',
			
// 			PropertyKey::THUNDER_ATTACK_BASE 		  => 'int',
// 			PropertyKey::THUNDER_ATTACK_ADDITION 	  => 'int',
// 			PropertyKey::THUNDER_DEFEND_BASE 		  => 'int',

// 			PropertyKey::WATER_ATTACK_BASE 			  => 'int',
// 			PropertyKey::WATER_ATTACK_ADDITION 		  => 'int',
// 			PropertyKey::WATER_DEFEND_BASE 			  => 'int',
			
// 			PropertyKey::FIRE_ATTACK_BASE 			  => 'int',
// 			PropertyKey::FIRE_ATTACK_ADDITION 		  => 'int',
// 			PropertyKey::FIRE_DEFEND_BASE 			  => 'int',	
		
			
// 			PropertyKey::ABSOLUTE_KILL_ATTACK 		  => 'int',
// 			PropertyKey::ABSOLUTE_KILL_DEFEND 		  => 'int',
	
			PropertyKey::ABSOLUTE_MAGIC_ATTACK 		  => 'int',
			PropertyKey::ABSOLUTE_MAGIC_DEFEND 		  => 'int',
			PropertyKey::ABSOLUTE_PHYSICAL_ATTACK 	  => 'int',
			PropertyKey::ABSOLUTE_PHYSICAL_DEFEND 	  => 'int',
			
			PropertyKey::ABSOLUTE_ATTACK 			  => 'int',
			PropertyKey::ABSOLUTE_DEFEND 			  => 'int',
			
			PropertyKey::MODIFY_PHYSIC_ATK 			  => 'int',
			PropertyKey::MODIFY_PHYSIC_DEF 			  => 'int',
			PropertyKey::MODIFY_RAGE_ATK 			  => 'int',
			PropertyKey::MODIFY_RAGE_DEF 			  => 'int',
			PropertyKey::MODIFY_CURE_RATIO 			  => 'int_empty',
			PropertyKey::MODIFY_BECURE_RATIO 		  => 'int_empty',
						
			PropertyKey::ABSOLUTE_ATK_RATIO 		  => 'int_empty',
			PropertyKey::ABSOLUTE_DFS_RATIO 		  => 'int_empty',		
			
			PropertyKey::COUNTRY 						=> 'int',
			PropertyKey::COUNTRY_RESTRAIN_WEI 		  	=> 'int_empty',
			PropertyKey::COUNTRY_RESTRAIN_SHU 		  	=> 'int_empty',
			PropertyKey::COUNTRY_RESTRAIN_WU 		  	=> 'int_empty',
			PropertyKey::COUNTRY_RESTRAIN_QUN 		  	=> 'int_empty',
			PropertyKey::COUNTRY_COUNTER_WEI 		  	=> 'int_empty',
			PropertyKey::COUNTRY_COUNTER_SHU 		  	=> 'int_empty',
			PropertyKey::COUNTRY_COUNTER_WU 		  	=> 'int_empty',
			PropertyKey::COUNTRY_COUNTER_QUN 		  	=> 'int_empty',
		
			PropertyKey::MAX_SUFFER_DAMAGE_REVERSE 		=> 'int_empty',
	        
	        PropertyKey::GENERAL_ATTACK_BASE            => 'int',
	        PropertyKey::GENERAL_ATTACK_ADDITION        => 'int',
			PropertyKey::ABSOLUTE_GENERAL_ATTACK		=> 'int_empty',
			
			PropertyKey::PHYSICAL_PENETRATE            	=> 'int',
			PropertyKey::PHYSICAL_RESISTANCE            => 'int',
			PropertyKey::MAGIC_PENETRATE            	=> 'int',
			PropertyKey::MAGIC_RESISTANCE            	=> 'int',
			PropertyKey::PENETRATE_ADDITION            	=> 'int_empty',
			PropertyKey::MORALE            				=> 'int_empty',
			PropertyKey::MODIFY_CURE            		=> 'int',
			PropertyKey::MODIFY_BECURE            		=> 'int',
			PropertyKey::BURN_DAMAGE					=> 'int',
			PropertyKey::POISON_DAMAGE					=> 'int',
			PropertyKey::BURN_RESISTANCE				=> 'int',
			PropertyKey::POISON_RESISTANCE				=> 'int',
			
			
			PropertyKey::SYSTEM_MODIFY_ATTACK_RATIO		=> 'int_empty',
			PropertyKey::SYSTEM_MODIFY_DEFEND_RATIO		=> 'int_empty',
			
			PropertyKey::BUFF_PROB_RESIS_FAINT			=> 'int_empty',
			PropertyKey::BUFF_PROB_RESIS_SILENT			=> 'int_empty',
			PropertyKey::BUFF_PROB_RESIS_STOP_ADD_RAGE	=> 'int_empty',
			PropertyKey::BUFF_PROB_RESIS_SUB_RAGE		=> 'int_empty',
			PropertyKey::BUFF_PROB_RESIS_PARALYSIS		=> 'int_empty',
			PropertyKey::BUFF_PROB_RESIS_STOP_ADD_HP	=> 'int_empty',
			PropertyKey::BUFF_PROB_RESIS_CHAOS			=> 'int_empty',
			PropertyKey::BUFF_PROB_RESIS_CHARMED		=> 'int_empty',
			PropertyKey::BUFF_PROB_RESIS_FOSSILIZED		=> 'int_empty',
			PropertyKey::BUFF_PROB_RESIS_FREEZE			=> 'int_empty',
			PropertyKey::BUFF_PROB_RESIS_TEAR			=> 'int_empty',
			
			PropertyKey::PVP_DAMAGE_ADDITION			=> 'int_empty',
			PropertyKey::PVP_DAMAGE_RESIS_ADDITION		=> 'int_empty',
			
			PropertyKey::BURN_DAMAGE_ADDITION			=> 'int_empty',
			PropertyKey::POISON_DAMAGE_ADDITION			=> 'int_empty',
			PropertyKey::BURN_RESIS_ADDITION			=> 'int_empty',
			PropertyKey::POISON_RESIS_ADDITION			=> 'int_empty',
			PropertyKey::DESTINY 			  			=> 'int_empty',
			
			);
	

	static $ARR_CLIENT_KEY = array (
			PropertyKey::HID 				  => 'int',
			PropertyKey::HTID 				  => 'raw',
			PropertyKey::POSITION 			  => 'int',
			PropertyKey::LEVEL 				  => 'int',
			PropertyKey::EVOLVE_LEVEL 		  => 'int',
	        PropertyKey::FIGHT_FORCE          => 'int',
			
			PropertyKey::MAX_HP 			  => 'int',
			PropertyKey::CURR_HP 			  => 'int_empty',
			PropertyKey::CURR_RAGE 			  => 'int_empty',
			
			PropertyKey::EQUIP_INFO			  => 'raw',
	        PropertyKey::DRESS_INFO           => 'array_int_empty',
			 
			PropertyKey::ARR_SKILL 			  => 'array_int_empty',
			PropertyKey::RAGE_SKILL 		  => 'int_empty',
			PropertyKey::ATTACK_SKILL 		  => 'int',
			PropertyKey::DESTINY 			  => 'int_empty',
						
			);
	
	static $CAR_ID_OFFSET = array(
			1 => 0,
			2 => 100,
	);
	
	//战车
	static $ARR_CAR_BATTLE_KEY = array (
			'cid'										=> 'int',
			'tid' 										=> 'int',
			'attackRound'								=> 'int',
			PropertyKey::ATTACK_SKILL 				  	=> 'int',
			'skillLevel'								=> 'int_empty',
			'fightRatio'								=> 'int_empty',
			PropertyKey::HIT 							=> 'int_empty',
			PropertyKey::FATAL 							=> 'int_empty',
			PropertyKey::FATAL_RATIO 					=> 'int_empty',
			PropertyKey::PHYSICAL_ATTACK_RATIO 			=> 'int_empty',
			PropertyKey::MAGIC_ATTACK_RATIO 			=> 'int_empty',
	);
	
	static $ARR_CAR_CLIENT_KEY = array (
			'cid'										=> 'int',
			'tid' 										=> 'int',
			PropertyKey::ATTACK_SKILL 				  	=> 'int',
	);
	
	static $ARR_PET_BATTLE_KEY = array ();
	
	static $ARR_PET_CLIENT_KEY = array ('petid' => 'int', 'level' => 'int', 'pet_tmpl' => 'int', 'arrSkill' => 'raw',
        'evolveLevel' => 'int_empty', 'confirmed' => 'raw');

	const BATTLE_RECORD_ENCODE_FLAGS = BATTLE_RECORD_ENCODE_FLAGS;

	/**
	 * 评价用数组
	 */
	public static $APPRAISAL = array ('SSS'   => 1,  'SS'   => 2,  'S'   => 3, 
	 'A'   => 4, 'B'   => 5,'C'   => 6, 'D'   => 7,  'E'   => 8,  'F'   => 9 );
}

class RecordType
{

	/**
	 * 临时
	 * @var int
	 */
	const TEMP = 1;

	/**
	 * 永久
	 * @var int
	 */
	const PERM = 2;
	
	/**
	 * 个人跨服战战报前缀
	 */
	const LRD_PREFIX = 'LRD_';
	
	/**
	 * 跨服军团战战报前缀
	 */
	const GDW_PREFIX = 'GDW_';
	
	/**
	 * 跨服竞技场战报前缀
	 */
	const WAN_PREFIX = 'WAN_';
	
	/**
	 * 跨服嘉年华战报前缀
	 */
	const WCN_PREFIX = 'WCN_';
}


class BattleType
{

	/**
	 * 竞技场战斗结算面板
	 * @var string
	 */
	const ARENA = 1;


	/**
	 * 普通副本战斗
	 */
	const NCOPY = 2;
	
	/**
	 * 精英副本
	 */
	const ECOPY = 4;

	/**
	 * 活动副本之活动据点
	 */
	const ACOPY = 5;
	/**
	 *	掠夺资源矿战斗 
	 */
	const MINERAL	=	6;
	
	/**
	 * 爬塔系统战斗
	 */
	const TOWER	=	7;
	/**
	 * 
	 * 活动副本之金钱树
	 */
	const GOLD_TREE = 8;
	
	/**
	 * 活动副本之经验宝物
	 */
	const EXP_TREASURE = 9;
	
	/**
	 * 活动副本之经验熊猫
	 */
	const EXP_HERO = 10;
	
	/**
	 * 试练塔隐藏关卡
	 */
	const SPECAIL_TOWER = 11;
	
	/**
	 * 武将列传
	 */
	const HCOPY = 12;

    /**
     * 寻龙探宝
     */
    const DRAGON = 13;
    
    /**
     * 活动副本之主角经验副本
     */
    const EXP_USER = 14;
    
    /**
     * 活动副本之天命副本
     */
    const DESTINY = 15;
    
    /**
     * 试炼梦魇
     */
    const HELL_TOWER = 16;
    
    /**
     * 个人跨服战
     */
    const LORD_WAR = 101;
    
    
    //下面是组队战类型
    
    /**
     * 工会组队战
     */
    const COPY_TEAM	= 10001;
    
    /**
     * 城站站组队战
     */
    const CITY_WAR	= 10002;
    
    /**
     * 跨服军团战
     */
    const GUILD_WAR = 10003;
    
    /**
     * 跨服竞技场
     */
    const WORLD_ARENA = 10004;
    
    /**
     * 跨服嘉年华
     */
    const WORLD_CARNIVAL = 10005;
}

class BattleDamageIncreType
{
	const None = 0;
	const Fix = 1;
	const Step = 2;
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */