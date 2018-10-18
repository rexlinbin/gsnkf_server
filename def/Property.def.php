<?php
/**********************************************************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Property.def.php 259834 2016-09-01 02:37:07Z BaoguoMeng $
 * 
 **********************************************************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Property.def.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-09-01 02:37:07 +0000 (Thu, 01 Sep 2016) $
 * @version $Revision: 259834 $
 * @brief 
 *  
 **/

/**********************************************************************************************************************
 * Class       : 
 * Description : creature基础属性的组合/计算得到的属性，在其他模块中使用
 * Inherit     : 
 **********************************************************************************************************************/
class PropertyKey
{

    //三层重新计算或者添加属性：MAX_HP\SANWEI
    //三层没有、有属性加成的属性：HP_BASE、HP_RATIO、HP_FINAL、STG_RATIO、REIGN_RATIO、ITG_RATIO
    
	//基础值
	const HID 							= 'hid';					// => 'int';
	const HTID 							= 'htid';
	const LEVEL 						= 'level'; 					// => 'int';
	const DESTINY						= 'destiny';
	const EVOLVE_LEVEL                  = 'evolve_level';  
	const GENDER                        = 'gender';
	const BASE_HTID 					= 'baseHtid';				// => 'int';
	const POSITION 						= 'position';				// => 'int';	
	const FIGHT_FORCE 					= 'fight_force'; 			// 战力 creature.class中返回数组中有	
	//装备信息
	const EQUIP_INFO					= 'equipInfo';
	const DRESS_INFO                    = 'dress';
	//丹药信息
	const PILL_INFO                     = 'pillInfo';
	
	//血量、怒气
	const MAX_HP 						= 'maxHp';
	const CURR_HP 						= 'currHp';
	const CURR_RAGE 					= 'currRage';
	
	//三围武力统帅智力
	const STRENGTH 						= 'strength';
	const REIGN 						= 'reign'; 
	const INTELLIGENCE 					= 'intelligence';
	
	
	
	
	//闪避 致命一击 格挡 命中
	const DODGE 						= 'dodge'; 
	const FATAL 						= 'fatal'; 
	const FATAL_RATIO					= 'fatalRatio';
	const FATAL_RESIST 					= 'fatalResist';	//抗暴概率
	const PARRY 						= 'parry';
	const PARRY_RESIST					= 'parryResist';	//破挡概率
	const HIT 							= 'hit';
	
	
	//攻击时怒气增长相关的值
	const RAGE_GET_BASE 				= 'rageBase';
	const RAGE_GET_AMEND 				= 'rageAmend';
	const RAGE_GET_RATIO 				= 'rageRatio';
	
	//技能 魅惑 混乱 攻击 怒气 格挡 闪避 死亡 回合前 回合后 随机技能
	const CHARM_SKILL 					= 'charmSkill';
	const LAUGH_SKILL 					= 'laughSkill';
	const CHAOS_SKILL 					= 'chaosSkill';
	const ATTACK_SKILL 					= 'attackSkill';
	const RAGE_SKILL 					= 'rageSkill';	
	const PARRY_SKILL 					= 'parrySkill';
	const DODGE_SKILL 					= 'dodgeSkill';
	const DEATH_SKILL 					= 'deathSkill';
	const ROUND_BEGIN_SKILL				= 'roundBeginSkill';
	const BIG_ROUND_BEGIN_SKILL			= 'bigRoundBeginSkill';
	const ROUND_END_SKILL				= 'roundEndSkill';
	const ARR_SKILL 					= 'arrSkill' ;
	//附加子技能
	const ARR_ATTACK_SKILL				= 'arrAttackSkill';
	const ARR_RAGE_SKILL				= 'arrRageSkill';
	const ARR_DEATH_SKILL				= 'arrDeathSkill';
	const ARR_PARRY_SKILL				= 'arrParrySkill';
	const ARR_DODGE_SKILL				= 'arrDodgeSkill';
	const ARR_ROUND_BEGIN_SKILL			= 'arrRoundBeginSkill';
	const ARR_ROUND_END_SKILL			= 'arrRoundEndSkill';
	//附加buff
	const ARR_ATTACK_BUFF				= 'arrAttackBuff';
	const ARR_RAGE_BUFF					= 'arrRageBuff';
	const ARR_DEATH_BUFF				= 'arrDeathBuff';
	const ARR_PARRY_BUFF				= 'arrParryBuff';
	const ARR_DODGE_BUFF				= 'arrDodgeBuff';
	const ARR_ROUND_BEGIN_BUFF			= 'arrRoundBeginBuff';
	const ARR_ROUND_END_BUFF			= 'arrRoundEndBuff';
	
	
	const ARR_IMMUNED_BUFF 				= 'arrImmunedEffect';
	const ARR_IMMUNED_SKILL_TYPE 		= 'arrImmunedSkillType';
	const ARR_IMMUNED_TARGET_TYPE 		= 'arrImmunedTargetType';
	const ARR_IMMUNED_TRIGGER_CONDITION	= 'arrImmunedTriggerCondition';
	
	
	//物理攻击伤害、防御字段
	const PHYSICAL_ATTACK_BASE 			= 'physicalAttackBase';
	const PHYSICAL_ATTACK_ADDITION 		= 'physicalAttackAddition';
	const PHYSICAL_DEFEND_BASE 			= 'physicalDefendBase';
	const PHYSICAL_DEFEND_ADDITION 		= 'physicalDefendAddition';
	const PHYSICAL_ATTACK_RATIO 		= 'physicalAttackRatio';
	const PHYSICAL_DAMAGE_IGNORE_RATIO 	= 'physicalDamageIgnoreRatio';
	
	
	//魔法攻击伤害字段
	const MAGIC_ATTACK_BASE 			= 'magicAttackBase';
	const MAGIC_ATTACK_ADDITION 		= 'magicAttackAddition';
	const MAGIC_DEFEND_BASE 			= 'magicDefendBase';
	const MAGIC_DEFEND_ADDITION 		= 'magicDefendAddition';	
	const MAGIC_ATTACK_RATIO 			= 'magicAttackRatio';
	const MAGIC_DAMAGE_IGNORE_RATIO 	= 'magicDamageIgnoreRatio';
	
	const ABSOLUTE_KILL_ATTACK      	= 'absoluteKillAttack';	
	const ABSOLUTE_KILL_DEFEND  		= 'absoluteKillDefend';
	const ABSOLUTE_MAGIC_ATTACK     	= 'absoluteMagicAttack';
	const ABSOLUTE_MAGIC_DEFEND 		= 'absoluteMagicDefend';
	const ABSOLUTE_PHYSICAL_ATTACK 		= 'absolutePhysicalAttack';
	const ABSOLUTE_PHYSICAL_DEFEND 		= 'absolutePhysicalDefend';
	
	const ABSOLUTE_ATTACK 				= 'absoluteAttack';// 在计算完 物/必/魔伤害之后额外还有绝对伤害
	const ABSOLUTE_DEFEND 				= 'absoluteDefend';
	
		
	const MODIFY_PHYSIC_ATK  			= 'modifyPhysicalAttack'; 
	const MODIFY_PHYSIC_DEF  			= 'modifyPhysicalDefend'; 
	const MODIFY_RAGE_ATK  				= 'modifyRageAttack';	 
	const MODIFY_RAGE_DEF  				= 'modifyRageDefend'; 
	const MODIFY_CURE_RATIO  			= 'modifyCureRatio' ;	 
	const MODIFY_BECURE_RATIO  			= 'modifyBeCuredRatio';		
	
	const ABSOLUTE_ATK_RATIO 			= 'absoluteAttackRatio' ;	//最终伤害增益
	const ABSOLUTE_DFS_RATIO 			= 'absoluteDefendRatio';	//最终免伤增益

	const COUNTRY						= 'country';
	const COUNTRY_RESTRAIN_WEI			= 'countryRestrainWei';
	const COUNTRY_RESTRAIN_SHU			= 'countryRestrainShu';
	const COUNTRY_RESTRAIN_WU			= 'countryRestrainWu';
	const COUNTRY_RESTRAIN_QUN			= 'countryRestrainQun';
	const COUNTRY_COUNTER_WEI			= 'countryCounterWei';
	const COUNTRY_COUNTER_SHU			= 'countryCounterShu';
	const COUNTRY_COUNTER_WU			= 'countryCounterWu';
	const COUNTRY_COUNTER_QUN			= 'countryCounterQun';
	
	const MAX_SUFFER_DAMAGE_REVERSE		= 'maxSufferDamageReverse';	 //1 - 最大承受maxHp百分之多少的伤害
    const GENERAL_ATTACK_BASE           = 'generalAttackBase';
    const GENERAL_ATTACK_ADDITION       = 'generalAttackAddition';
    
    const ABSOLUTE_GENERAL_ATTACK		= 'absoluteGeneralAttack';
    
    const SYSTEM_MODIFY_ATTACK_RATIO	= 'systemModifyAttackRatio';
    const SYSTEM_MODIFY_DEFEND_RATIO	= 'systemModifyDefendRatio';
   
    
    const PHYSICAL_PENETRATE			= 'physicalPenetrate';
    const PHYSICAL_RESISTANCE			= 'physicalResistance';
    const MAGIC_PENETRATE				= 'magicPenetrate';
    const MAGIC_RESISTANCE				= 'magicResistance';
    const MODIFY_CURE					= 'modifyCure';
    const MODIFY_BECURE					= 'modifyBeCured';
    const BURN_DAMAGE					= 'burnDamage';
    const POISON_DAMAGE					= 'poisonDamage';
	const BURN_RESISTANCE				= 'burnResistance';
	const POISON_RESISTANCE				= 'poisonResistance';
	
	const BURN_DAMAGE_ADDITION			= 'burnDamageAddition';		//灼烧伤害百分比加成
	const POISON_DAMAGE_ADDITION		= 'posionDamageAddition';	//中毒伤害百分比加成
	const BURN_RESIS_ADDITION			= 'burnResisAddition';		//灼烧百分比免伤
	const POISON_RESIS_ADDITION			= 'poisonResisAddition';	//中毒百分比免伤
	
	const BUFF_PROB_RESIS_FAINT			= 'buffProbResisFaint';
	const BUFF_PROB_RESIS_SILENT		= 'buffProbResisSilent';
	const BUFF_PROB_RESIS_STOP_ADD_RAGE	= 'buffProbResisStopAddRage';
	const BUFF_PROB_RESIS_SUB_RAGE		= 'buffProbResisSubRage';
	const BUFF_PROB_RESIS_PARALYSIS		= 'buffProbResisParalysis';
	const BUFF_PROB_RESIS_STOP_ADD_HP	= 'buffProbResisStopAddHp';
	const BUFF_PROB_RESIS_CHAOS			= 'buffProbResisChaos';
	const BUFF_PROB_RESIS_CHARMED		= 'buffProbResisCharmed';
	const BUFF_PROB_RESIS_FOSSILIZED	= 'buffProbResisFossilized';
	const BUFF_PROB_RESIS_FREEZE		= 'buffProbResisFreeze';
	const BUFF_PROB_RESIS_TEAR			= 'buffProbResisTear';
	
	const PVP_DAMAGE_ADDITION			= 'pvpDamageAddition';      // PvP伤害加成
	const PVP_DAMAGE_RESIS_ADDITION		= 'pvpDamageResisAddition'; // PvP减伤加成
	
	const PENETRATE_ADDITION			= 'penetrateAddition';      // 穿透百分比加成（物理，法术）
	
	const MORALE						= 'morale';					// 士气值
	
	
//--以下是一些这些字段只参与战斗属性的计算，但不在最终的战斗属性中------------
	
	const HP_BASE 						= 'hpBase'; 				// hp基础值
	const HP_RATIO 						= 'hpRatio'; 				// hp百分比(在hp基础值上的附加百分比)
	const HP_FINAL 						= 'hpFinal'; 				// hp最终值
	
	const STG_BASE                      = 'stgBase';   
	const REIGN_BASE                    = 'reignBase';
	const ITG_BASE                      = 'itgBase';  
	
	const STG_RATIO 					= 'stgRatio';				// strenght百分比
	const REIGN_RATIO 					= 'reignRatio';				// 统帅百分比
	const ITG_RATIO 					= 'itgRatio';				// 智慧百分比
	
	
 	

//----以下是策划配置表中的属性ID和属性的映射
 
 	static $MAP_CONF = array(
 			1 => self::HP_BASE,
 			2 => self::PHYSICAL_ATTACK_BASE,
 			3 => self::MAGIC_ATTACK_BASE,
 			4 => self::PHYSICAL_DEFEND_BASE,
 			5 => self::MAGIC_DEFEND_BASE,
 			6 => self::REIGN_BASE,
  			7 => self::STG_BASE,
 			8 => self::ITG_BASE,
			9 => self::GENERAL_ATTACK_BASE,
//			10 => self::KILL_ATTACK_BASE,
 			11 => self::HP_RATIO,
 			12 => self::PHYSICAL_ATTACK_ADDITION,
 			13 => self::MAGIC_ATTACK_ADDITION,
 			14 => self::PHYSICAL_DEFEND_ADDITION,
 			15 => self::MAGIC_DEFEND_ADDITION,
 			16 => self::REIGN_RATIO,
 			17 => self::STG_RATIO,
 			18 => self::ITG_RATIO,
			19 => self::GENERAL_ATTACK_ADDITION,
// 			20 =>　self::KILL_DEFEND_ADDITION,
 			21 => self::HIT,
 			22 => self::PHYSICAL_ATTACK_RATIO,
 			23 => self::MAGIC_ATTACK_RATIO,
 			24 => self::PHYSICAL_DAMAGE_IGNORE_RATIO,
 			25 => self::MAGIC_DAMAGE_IGNORE_RATIO,
 			26 => self::FATAL,
 			27 => self::PARRY,
 			28 => self::DODGE,
 			29 => self::ABSOLUTE_ATTACK,
 			30 => self::ABSOLUTE_DEFEND,
		
 			//...
 			
 			49 => self::CURR_RAGE,
 			50 => self::MAX_SUFFER_DAMAGE_REVERSE,
 			51 => self::HP_FINAL,
 			52 => self::ABSOLUTE_PHYSICAL_ATTACK,
 			53 => self::ABSOLUTE_MAGIC_ATTACK,
 			54 => self::ABSOLUTE_PHYSICAL_DEFEND,
 			55 => self::ABSOLUTE_MAGIC_DEFEND,
 			56 => self::ABSOLUTE_ATK_RATIO,
 			57 => self::ABSOLUTE_DFS_RATIO,
 			58 => self::MODIFY_PHYSIC_ATK,
 			59 => self::MODIFY_PHYSIC_DEF,
 			60 => self::MODIFY_RAGE_ATK,
 			61 => self::MODIFY_RAGE_DEF,
 			62 => self::MODIFY_CURE_RATIO,
 			63 => self::MODIFY_BECURE_RATIO,
 			
 			// 64-66 之前是免疫暴击，闪避，格挡三个属性。删掉
 			
 			67 => self::COUNTRY_RESTRAIN_WEI,
 			68 => self::COUNTRY_RESTRAIN_SHU,
 			69 => self::COUNTRY_RESTRAIN_WU,
 			70 => self::COUNTRY_RESTRAIN_QUN,
 			71 => self::COUNTRY_COUNTER_WEI,
 			72 => self::COUNTRY_COUNTER_SHU,
 			73 => self::COUNTRY_COUNTER_WU,
 			74 => self::COUNTRY_COUNTER_QUN,
 	        75 => self::FATAL_RATIO,
 	        76 => self::FATAL_RESIST,
 	        77 => self::PARRY_RESIST,
 	        78 => self::SYSTEM_MODIFY_ATTACK_RATIO,
 	        79 => self::SYSTEM_MODIFY_DEFEND_RATIO,
 	        80 => self::PHYSICAL_PENETRATE,
 	        81 => self::MAGIC_PENETRATE,
 	        82 => self::PHYSICAL_RESISTANCE,
 	        83 => self::MAGIC_RESISTANCE,
 	        84 => self::MODIFY_CURE,
 	        85 => self::MODIFY_BECURE,
 	        86 => self::BURN_DAMAGE,
 	        87 => self::POISON_DAMAGE,
 	        88 => self::BURN_RESISTANCE,
 	        89 => self::POISON_RESISTANCE,
 	        90 => self::BUFF_PROB_RESIS_FAINT, 	       
 	        91 => self::BUFF_PROB_RESIS_SILENT,
 	        92 => self::BUFF_PROB_RESIS_STOP_ADD_RAGE,
 	        93 => self::BUFF_PROB_RESIS_SUB_RAGE,
 	        94 => self::BUFF_PROB_RESIS_PARALYSIS,
 	        95 => self::BUFF_PROB_RESIS_STOP_ADD_HP,
 	        96 => self::BURN_DAMAGE_ADDITION,
 	        97 => self::POISON_DAMAGE_ADDITION,
 	        98 => self::BURN_RESIS_ADDITION,
 	        99 => self::POISON_RESIS_ADDITION,
 	        100 => self::ABSOLUTE_GENERAL_ATTACK,
 	        101 => self::PVP_DAMAGE_ADDITION,
 	        102 => self::PVP_DAMAGE_RESIS_ADDITION,
 	        103 => self::BUFF_PROB_RESIS_CHAOS,
 	        104 => self::BUFF_PROB_RESIS_CHARMED,
 	        105 => self::BUFF_PROB_RESIS_FOSSILIZED,
 	        106 => self::BUFF_PROB_RESIS_FREEZE,
 	        107 => self::BUFF_PROB_RESIS_TEAR,
 	        108 => self::PENETRATE_ADDITION,
 	        109 => self::MORALE,
 			); 
 	
}






/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */