<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FightSoul.def.php 195142 2015-08-28 05:51:14Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/FightSoul.def.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-08-28 05:51:14 +0000 (Fri, 28 Aug 2015) $
 * @version $Revision: 195142 $
 * @brief 
 *  
 **/
class FightSoulDef
{	
	//战魂类型
	const FIGHTSOUL_TYPE_ONE = 1;
	const FIGHTSOUL_TYPE_TWO = 2;
	const FIGHTSOUL_TYPE_THREE = 3;
	const FIGHTSOUL_TYPE_FOUR = 4;
	const FIGHTSOUL_TYPE_FIVE = 5;
	const FIGHTSOUL_TYPE_SIX = 6;
	const FIGHTSOUL_TYPE_SEVEN = 7;
	const FIGHTSOUL_TYPE_EIGHT = 8;
	//常量
	const ITEM_ATTR_NAME_FIGHTSOUL_INIT_LEVEL				=			0;
	const ITEM_ATTR_NAME_FIGHTSOUL_INIT_EXP					=			0;
	const ITEM_ATTR_NAME_FIGHTSOUL_INIT_EVOLVE				=			0;
	
	const ITEM_ATTR_NAME_FIGHTSOUL_TYPE 					= 			'fightSoulType';
	const ITEM_ATTR_NAME_FIGHTSOUL_VALUE					=			'fightSoulValue';
	const ITEM_ATTR_NAME_FIGHTSOUL_SCORE					=			'fightSoulScore';
	const ITEM_ATTR_NAME_FIGHTSOUL_SORT						=			'fightSoulSort';
	const ITEM_ATTR_NAME_FIGHTSOUL_EXPID					=			'fightSoulExpId';
	const ITEM_ATTR_NAME_FIGHTSOUL_ATTRS					=			'fightSoulAttrs';
	const ITEM_ATTR_NAME_FIGHTSOUL_LEVELLIMIT				=			'fightSoulLevelLimit';
	const ITEM_ATTR_NAME_FIGHTSOUL_BASERATIO				=			'fightSoulBaseRatio';
	const ITEM_ATTR_NAME_FIGHTSOUL_LEVELRATIO				=			'fightSoulLevelRatio';
	const ITEM_ATTR_NAME_FIGHTSOUL_CANDEVELOP				=			'fightSoulCanDevelop';
	const ITEM_ATTR_NAME_FIGHTSOUL_EVOLVELIMIT				=			'fightSoulEvolveLimit';
	const ITEM_ATTR_NAME_FIGHTSOUL_ATTRRATIO				=			'fightSoulAttrRatio';	
	const ITEM_ATTR_NAME_FIGHTSOUL_EVOLVECOST				=			'fightSoulEvolveCost';
	const ITEM_ATTR_NAME_FIGHTSOUL_DEVELOPLV				=			'fightSoulDevelopLv';
	const ITEM_ATTR_NAME_FIGHTSOUL_DEVELOPID				=			'fightSoulDevelopId';
	const ITEM_ATTR_NAME_FIGHTSOUL_DEVELOPCOST				=			'fightSoulDevelopCost';
	const ITEM_ATTR_NAME_FIGHTSOUL_LEVEL					=			'fsLevel';
	const ITEM_ATTR_NAME_FIGHTSOUL_EXP						=			'fsExp';
	const ITEM_ATTR_NAME_FIGHTSOUL_EVOLVE					=			'fsEvolve';
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */