<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Arm.def.php 207258 2015-11-04 10:30:43Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Arm.def.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-11-04 10:30:43 +0000 (Wed, 04 Nov 2015) $
 * @version $Revision: 207258 $
 * @brief 
 *  
 **/

class ArmDef
{
	//常量
	const ARM_TYPE_NUM = 4;
	
	//装备分类
	//武器，戒指，护甲，头盔，项链
	const ARM_TYPE_WEAPON									=			1;	
	const ARM_TYPE_ARMOR									=			2;
	const ARM_TYPE_HAT										=			3;
	const ARM_TYPE_NECKLACE									=			4;
	
	//装备位置
	const ARM_POSITION_WEAPON								=			1;
	const ARM_POSITION_ARMOR								=			2;
	const ARM_POSITION_HAT									=			3;
	const ARM_POSITION_NECKLACE								=			4;
	
	//常量
	//默认物品强化等级
	const ARM_REINFORCE_LEVEL_DEFAULT						=			0;	
	//最大物品强化等级
	const ARM_REINFORCE_LEVEL_MAX							= 			400;
	//默认装备进阶等级
	const ARM_DEVELOP_DEFAULT								= 			-1;
	//可以随机洗潜能
	const ARM_CAN_RANDOM_REFRESH							=			1;	
	//可以固定洗潜能
	const ARM_CAN_FIXED_REFRESH								=			1;	
	//没有潜能
	const ARM_INVALID_POTENCE_ID							=			0;
	//不是套装装备
	const ARM_INVALID_SUIT_ID						 		=			0;
	
	//valid arm types
	public static $ARM_VALID_TYPES							=			array (
			self::ARM_TYPE_WEAPON,
			self::ARM_TYPE_ARMOR,
			self::ARM_TYPE_HAT,
			self::ARM_TYPE_NECKLACE
	);
	
	//valid arm positions
	public static $ARM_VALID_POSITIONS						=			array (
			self::ARM_POSITION_WEAPON						=>			array (
					self::ARM_TYPE_WEAPON,
			),
			self::ARM_POSITION_ARMOR						=>			array (
					self::ARM_TYPE_ARMOR,
			),
			self::ARM_POSITION_HAT							=>			array (
					self::ARM_TYPE_HAT,
			),
			self::ARM_POSITION_NECKLACE						=>			array (
					self::ARM_TYPE_NECKLACE,
			)
	);
	
	//默认所有装备为空
	public static $ARM_NO_ARMING = array (
			self::ARM_POSITION_WEAPON						=>			ItemDef::ITEM_ID_NO_ITEM,
			self::ARM_POSITION_ARMOR						=>			ItemDef::ITEM_ID_NO_ITEM,
			self::ARM_POSITION_HAT							=>			ItemDef::ITEM_ID_NO_ITEM,
			self::ARM_POSITION_NECKLACE						=>			ItemDef::ITEM_ID_NO_ITEM
	);
	
	//arm general attr
	const ITEM_ATTR_NAME_ARM_TYPE							=			'armType';
	const ITEM_ATTR_NAME_ARM_SUIT                   		=           'armSuitId';
	const ITEM_ATTR_NAME_ARM_LEVEL							=			'armHeroLevel';	
	const ITEM_ATTR_NAME_ARM_REINFORCE						=           'armReinforceId';	
	const ITEM_ATTR_NAME_ARM_FIXED_REFRESH					=			'armFixedRefresh';
	const ITEM_ATTR_NAME_ARM_RAND_REFRESH					=			'armRandRefresh';
	const ITEM_ATTR_NAME_ARM_FIXED_POTENCE					=			'armFixedPotence';
	const ITEM_ATTR_NAME_ARM_RAND_POTENCE					=			'armRandPotence';
	const ITEM_ATTR_NAME_ARM_INIT_LEVEL						=			'armInitLevel';
	const ITEM_ATTR_NAME_ARM_EXCHANGE						=			'armExchangeId';
	const ITEM_ATTR_NAME_ARM_EVOLVE							=			'armEvolveId';
	const ITEM_ATTR_NAME_ARM_SCORE_BASE						=			'armScoreBase';
	const ITEM_ATTR_NAME_ARM_REINFORCE_LEVEL				=			'armReinforceLevel';
	const ITEM_ATTR_NAME_ARM_REINFORCE_COST					=			'armReinforceCost';
	const ITEM_ATTR_NAME_ARM_POTENCE						=			'armPotence';
	const ITEM_ATTR_NAME_ARM_REINFORCE_RATE					=			'armReinforceRate';	
	const ITEM_ATTR_NAME_ARM_POTENCE_RATIO					=			'armPotenceRatio';
	const ITEM_ATTR_NAME_ARM_POTENCE_INIT					=			'armPotenceInit';
	const ITEM_ATTR_NAME_ARM_POTENCE_LIMIT					=			'armPotenceLimit';
	const ITEM_ATTR_NAME_ARM_POTENCE_RESOLVE				=			'armPotenceResolve';
	const ITEM_ATTR_NAME_ARM_REBORN_COST					=			'armRebornCost';
	const ITEM_ATTR_NAME_ARM_FOUNDRY						=			'armFoundryId';
	const ITEM_ATTR_NAME_ARM_CAN_DEVELOP					= 			'armCanDevelop';
	const ITEM_ATTR_NAME_ARM_DEVELOP_LIMIT					=			'armDevelopLimit';
	const ITEM_ATTR_NAME_ARM_DEVELOP_ATTRS					=			'armDevelopAttrs';
	const ITEM_ATTR_NAME_ARM_DEVELOP_EXTRA					=			'armDevelopExtra';
	const ITEM_ATTR_NAME_ARM_DEVELOP_EXPEND					=			'armDevelopExpend';
	const ITEM_ATTR_NAME_ARM_DEVELOP_EXTRA_ALL				=			'armDevelopExtraAll';
	const ITEM_ATTR_NAME_ARM_DEVELOP_QUALITY				=			'armDevelopQuality';
	const ITEM_ATTR_NAME_ARM_DEVELOP_SCORE					=			'armDevelopScore';
	const ITEM_ATTR_NAME_ARM_DEVELOP						=			'armDevelop';
	
	//arm attr add
	const ITEM_ATTR_NAME_ARM_HP_ADD							=			'armHpAdd';
	const ITEM_ATTR_NAME_ARM_PHYSICAL_ATTACK_ADD			=			'armPhysicalAttackAdd';
	const ITEM_ATTR_NAME_ARM_MAGIC_ATTACK_ADD				=			'armMagicAttackAdd';
	const ITEM_ATTR_NAME_ARM_GENERAL_ATTACK_ADD				=			'armGeneralAttackAdd';
	const ITEM_ATTR_NAME_ARM_PHYSICAL_DEFEND_ADD			=			'armPhysicalDefendAdd';
	const ITEM_ATTR_NAME_ARM_MAGIC_DEFEND_ADD				=			'armMagicDefendAdd';
	const ITEM_ATTR_NAME_ARM_SCORE_ADD						=			'armScoreAdd';
	
	//arm reinforce fee
	const ITEM_ATTR_NAME_ARM_REINFORCE_SILVER				=			'armReinforceSilver';
	const ITEM_ATTR_NAME_ARM_REINFORCE_ITEMS				=			'armReinforceItems';
	
	//suit attr
	const ITEM_ATTR_NAME_ARM_SUIT_NUM						=			'armSuitNum';
	const ITEM_ATTR_NAME_ARM_SUIT_ATTR						=			'armSuitAttr';
	const ITEM_ATTR_NAME_ARM_SUIT_ITEMS						=			'armSuitItems';		
	
	const ITEM_ATTR_NAME_ARM_RESOLVE_VALUE					=			'armResolveValue';
	const ITEM_ATTR_NAME_ARM_RESOLVE_ARGS					=			'armResolveArgs';
	const ITEM_ATTR_NAME_ARM_RESOLVE_NUM					=			'armResolveNum';
	const ITEM_ATTR_NAME_ARM_RESOLVE_DROPS					=			'armResolveDrops';
	
	//装备会改变的人物属性
	public static $ARM_ATTRS_CALC							=			array(
			PropertyKey::HP_BASE => array (
					PropertyKey::HP_BASE,
					self::ITEM_ATTR_NAME_ARM_HP_ADD
			),
			PropertyKey::PHYSICAL_ATTACK_BASE => array(
					PropertyKey::PHYSICAL_ATTACK_BASE,
					self::ITEM_ATTR_NAME_ARM_PHYSICAL_ATTACK_ADD
			),
			PropertyKey::MAGIC_ATTACK_BASE => array(
					PropertyKey::MAGIC_ATTACK_BASE,
					self::ITEM_ATTR_NAME_ARM_MAGIC_ATTACK_ADD
			),
			PropertyKey::GENERAL_ATTACK_BASE => array(
					PropertyKey::GENERAL_ATTACK_BASE,
					self::ITEM_ATTR_NAME_ARM_GENERAL_ATTACK_ADD
			),
			PropertyKey::PHYSICAL_DEFEND_BASE => array(
					PropertyKey::PHYSICAL_DEFEND_BASE,
					self::ITEM_ATTR_NAME_ARM_PHYSICAL_DEFEND_ADD
			),
			PropertyKey::MAGIC_DEFEND_BASE => array(
					PropertyKey::MAGIC_DEFEND_BASE,
					self::ITEM_ATTR_NAME_ARM_MAGIC_DEFEND_ADD
			)		
	);
	public static $ARM_ATTRS_CALC_ADDITION 					=			array(
			PropertyKey::HP_RATIO => PropertyKey::HP_RATIO,
			PropertyKey::PHYSICAL_ATTACK_ADDITION => PropertyKey::PHYSICAL_ATTACK_ADDITION,
			PropertyKey::MAGIC_ATTACK_ADDITION => PropertyKey::MAGIC_ATTACK_ADDITION,
			PropertyKey::GENERAL_ATTACK_ADDITION => PropertyKey::GENERAL_ATTACK_ADDITION,
			PropertyKey::PHYSICAL_DEFEND_ADDITION => PropertyKey::PHYSICAL_DEFEND_ADDITION,
			PropertyKey::MAGIC_DEFEND_ADDITION => PropertyKey::MAGIC_DEFEND_ADDITION,			
	);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */