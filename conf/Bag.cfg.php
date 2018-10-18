<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Bag.cfg.php 250248 2016-07-06 09:32:12Z QingYao $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Bag.cfg.php $
 * @author $Author: QingYao $(jhd@babeltime.com)
 * @date $Date: 2016-07-06 09:32:12 +0000 (Wed, 06 Jul 2016) $
 * @version $Revision: 250248 $
 * @brief
 *
 **/

class BagConf
{	
	//装备背包初始格子数
	const INIT_GRID_NUM_ARM						=					500;
	//道具背包初始格子数
	const INIT_GRID_NUM_PROPS					=					500;
	//宝物背包初始格子数
	const INIT_GRID_NUM_TREAS					=					500;
	//装备碎片背包初始格子数
	const INIT_GRID_NUM_ARM_FRAG				=					500;
	//时装背包初始格子数
	const INIT_GRID_NUM_DRESS					= 					500;
	//战魂背包初始格子数
	const INIT_GRID_NUM_FIGHT_SOUL				=					500;
    //神兵背包初始格子数
    const INIT_GRID_NUM_GOD_WEAPON              =                   500;
    //神兵碎片背包初始格子数
    const INIT_GRID_NUM_GOD_WEAPON_FRAG         =                   500;
    //符印背包初始格子数
    const INIT_GRID_NUM_RUNE              		=                   500;
    //符印碎片背包初始格子数
    const INIT_GRID_NUM_RUNE_FRAG         		=                   500;
    //锦囊背包初始格子数
    const INIT_GRID_NUM_POCKET         			=                   500;
    //兵符背包初始格子数
    const INIT_GRID_NUM_TALLY         			=                   500;
    //兵符碎片背包初始格子数
    const INIT_GRID_NUM_TALLY_FRAG         		=                   500;
    //战车背包初始格子数
    const INIT_GRID_NUM_CHARIOT              =    500;
	
	//用户每次开背包格子数
	const BAG_UNLOCK_GRID						=					5;
	//用户背包解锁初始价格
	const BAG_UNLOCK_GOLD						=					5;
	//用户背包解锁价格增加值
	const BAG_UNLOCK_GOLD_STEP					=					5;
	//用户背包解锁所需物品
	const BAG_UNLOCK_ITEM_ID					=					60029;
	
	//批量使用物品最大个数
	const BAG_USE_LIMIT							=					50;


	static $INIT_ARR_ITEM = array(
			60002 => 50,
			);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */