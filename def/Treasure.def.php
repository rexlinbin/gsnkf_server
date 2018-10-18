<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Treasure.def.php 253496 2016-07-28 03:51:28Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Treasure.def.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-07-28 03:51:28 +0000 (Thu, 28 Jul 2016) $
 * @version $Revision: 253496 $
 * @brief 
 *  
 **/
class TreasureDef
{
	//宝物数量，TODO：暂定4
	const TREASURE_TYPE_NUM 								=			2;
	
	//宝物分类：1名马，2名书，TODO:3,4
	const TREASURE_TYPE_HORSE 								=			1;
	const TREASURE_TYPE_BOOK								=			2;
	
	//宝物位置
	const TREASURE_POSITION_HORSE							=			1;
	const TREASURE_POSITION_BOOK							=			2;
	
	//常量
	const TREASURE_UPGRADE_USE_LIMIT						=			5;
	const ITEM_ATTR_NAME_TREASURE_INIT_LEVEL				=			0;
	const ITEM_ATTR_NAME_TREASURE_INIT_EXP					=			0;
	const ITEM_ATTR_NAME_TREASURE_INIT_EVOLVE				=			0;
	const ITEM_ATTR_NAME_TREASURE_INIT_DEVELOP				=			-1;
	//进阶0-5是橙色0-5，6-11是红色0-5
	const ORANGE_INIT_DEVELOP 					=			0;
	const RED_INIT_DEVELOP 						=			6;
	public static $ITEM_ATTR_NAME_TREASURE_INIT_INLAY		=			array();
	
	//宝物正确的类型
	public static $TREASURE_VALID_TYPES 					= 			array	(
			self::TREASURE_TYPE_HORSE,
			self::TREASURE_TYPE_BOOK,
	);

	//valid arm positions
	public static $TREASURE_VALID_POSITIONS					=			array (
			self::TREASURE_POSITION_HORSE					=>			array (
					self::TREASURE_TYPE_HORSE,
			),
			self::TREASURE_TYPE_BOOK					=>			array (
					self::TREASURE_TYPE_BOOK,
			),
	);
	
	//默认所有装备为空
	public static $TREASURE_NO_ARMING = array (
			self::TREASURE_POSITION_HORSE					=>			ItemDef::ITEM_ID_NO_ITEM,
			self::TREASURE_POSITION_BOOK					=>			ItemDef::ITEM_ID_NO_ITEM,
	);
	
	const ITEM_ATTR_NAME_TREASURE_TYPE 						= 			'treasureType';
	const ITEM_ATTR_NAME_TREASURE_ATTRS						=			'treasureAttrs';
	const ITEM_ATTR_NAME_TREASURE_EXTRA						=			'treasureExtra';
	const ITEM_ATTR_NAME_TREASURE_VALUE_BASE				=			'treasureValueBase';
	const ITEM_ATTR_NAME_TREASURE_VALUE_UPGRADE				=			'treasureValueUpgrade';
	const ITEM_ATTR_NAME_TREASURE_EXPEND_UPGRADE			=			'treasureExpendUpgrade';
	const ITEM_ATTR_NAME_TREASURE_LEVEL_LIMIT				=			'treasureLevelLimit';
	const ITEM_ATTR_NAME_TREASURE_FRAGMENTS					=			'treasureFragments';
	const ITEM_ATTR_NAME_TREASURE_SCORE_BASE				=			'treasureScoreBase';
	const ITEM_ATTR_NAME_TREASURE_SCORE_ADD					=			'treasureScoreAdd';
	const ITEM_ATTR_NAME_TREASURE_NO_ATTR					=			'treasureNoAttr';
	const ITEM_ATTR_NAME_TREASURE_CAN_EVOLVE				=			'treasureCanEvolve';
	const ITEM_ATTR_NAME_TREASURE_EVOLVE_ATTRS				= 			'treasureEvolveAttrs';
	const ITEM_ATTR_NAME_TREASURE_EVOLVE_EXTRA				= 			'treasureEvolveExtra';
	const ITEM_ATTR_NAME_TREASURE_EVOLVE_LIMIT				=			'treasureEvolveLimit';
	const ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND				=			'treasureEvolveExpend';
	const ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND_SILVER		=			'treasureEvolveExpendSilver';
	const ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND_ITEM1		=			'treasureEvolveExpendItem1';
	const ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND_ITEM2		=			'treasureEvolveExpendItem2';
	const ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND_ITEM3		=			'treasureEvolveExpendItem3';
	const ITEM_ATTR_NAME_TREASURE_EVOLVE_RESOLVE			=			'treasureEvolveResolve';
	const ITEM_ATTR_NAME_TREASURE_RESOLVE_ITEM				=			'treasureResolveItem';
	const ITEM_ATTR_NAME_TREASURE_REBORN_COST				=			'treasureRebornCost';
	const ITEM_ATTR_NAME_TREASURE_CAN_INLAY					=			'treasureCanInlay';
	const ITEM_ATTR_NAME_TREASURE_INLAY_OPEN				=			'treasureInlayOpen';
	const ITEM_ATTR_NAME_TREASURE_CAN_DEVELOP				=			'treasureCanDevelop';
	const ITEM_ATTR_NAME_TREASURE_DEVELOP_EXPEND			=			'treasureDevelopExpend';
	const ITEM_ATTR_NAME_TREASURE_DEVELOP_QUALITY			=			'treasureDevelopQuality';
	const ITEM_ATTR_NAME_TREASURE_DEVELOP_SCORE				=			'treasureDevelopScore';
	const ITEM_ATTR_NAME_TREASURE_DEVELOP_EXTRA				=			'treasureDevelopExtra';
	const ITEM_ATTR_NAME_TREASURE_DEVELOP_ATTRS				=			'treasureDevelopAttrs';
	const ITEM_ATTR_NAME_TREASURE_CAN_UPGRADE				=			'treasureCanUpgrade';
	const ITEM_ATTR_NAME_TREASURE_EVOLVE					=			'treasureEvolve';
	const ITEM_ATTR_NAME_TREASURE_LEVEL						= 			'treasureLevel';
	const ITEM_ATTR_NAME_TREASURE_EXP						=			'treasureExp';
	const ITEM_ATTR_NAME_TREASURE_DEVELOP					=			'treasureDevelop';
	const ITEM_ATTR_NAME_TREASURE_INLAY						=			'treasureInlay';
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */