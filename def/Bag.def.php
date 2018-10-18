<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Bag.def.php 250248 2016-07-06 09:32:12Z QingYao $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Bag.def.php $
 * @author $Author: QingYao $(jhd@babeltime.com)
 * @date $Date: 2016-07-06 09:32:12 +0000 (Wed, 06 Jul 2016) $
 * @version $Revision: 250248 $
 * @brief
 *
 **/

class BagDef
{
	const FRONT_CALLBACK_UPDATE					=  			're.bag.bagInfo';

	const BAG_TMP								=			'tmp';
	const BAG_ARM								=			'arm';
	const BAG_PROPS								=			'props';
	const BAG_HERO_FRAG							=			'heroFrag';
	const BAG_TREAS								=			'treas';
	const BAG_ARM_FRAG							=			'armFrag';
	const BAG_DRESS								=			'dress';
	const BAG_FIGHT_SOUL						=			'fightSoul';
	const BAG_PET_FRAG							=			'petFrag';
    const BAG_GOD_WEAPON                        =           'godWp';  //神兵背包
    const BAG_GOD_WEAPON_FRAG                   =           'godWpFrag';   //神兵碎片
    const BAG_RUNE								=			'rune';
    const BAG_RUNE_FRAG							=			'runeFrag';
    const BAG_POCKET							=			'pocket';
    const BAG_TALLY								=			'tally';
    const BAG_TALLY_FRAG						=			'tallyFrag';			
    const BAG_CHARIOT                      = 'chariotBag';//战车背包		
	
	const MAX_GRID								=			100000;
	const WARNING_GRID_NUM						=			500;		//如果某个背包中格子数大于这个数，就告警
	
	const GRID_START_TMP						=			1000001;
	const GRID_START_ARM						=			2000001;
	const GRID_START_PROPS						=			3000001;
	const GRID_START_HERO_FRAG					=			4000001;
	const GRID_START_TREAS						=			5000001;
	const GRID_START_ARM_FRAG					=			6000001;
	const GRID_START_DRESS						=			7000001;
	const GRID_START_FIGHT_SOUL					=			8000001;
	const GRID_START_PET_FRAG					=			9000001;
    const GRID_START_GOD_WEAPON                 =           10000001;
    const GRID_START_GOD_WEAPON_FRAG            =           11000001;
    const GRID_START_RUNE						=			12000001;
    const GRID_START_RUNE_FRAG					=			13000001;
    const GRID_START_POCKET						=			14000001;
    const GRID_START_TALLY						=			15000001;
    const GRID_START_TALLY_FRAG					=			16000001;
    const GRID_START_CHARIOT                      =17000001;
	const GRID_END								=			18000001;
		
	const ITEM_ID_NO_ITEM						=			ItemDef::ITEM_ID_NO_ITEM;
	const INVALID_GRID_ID						=			0;

	//session
	const SESSION_USER_ID						=			'global.uid';
	const SESSION_TMP							=			'bag.tmp';	
	const SESSION_ARM							=			'bag.arm';
	const SESSION_PROPS							=			'bag.props';
	const SESSION_HERO_FRAG						=			'bag.heroFrag';
	const SESSION_TREAS							=			'bag.treas';
	const SESSION_ARM_FRAG						=			'bag.armFrag';
	const SESSION_DRESS							=			'bag.dress';
	const SESSION_FIGHT_SOUL					=			'bag.fightSoul';
	const SESSION_PET_FRAG						=			'bag.petFrag';
	const SESSION_USE_NUM						=			'bag.useNum';
	const SESSION_GIFT_USE_NUM					= 			'bag.giftUseNum';
    const SESSION_GOD_WEAPON                    =           'bag.godWp';
    const SESSION_GOD_WEAPON_FRAG               =           'bag.godWpFrag';
    const SESSION_RUNE							=			'bag.rune';
    const SESSION_RUNE_FRAG						=			'bag.runeFrag';
    const SESSION_POCKET						=			'bag.pocket';
    const SESSION_TALLY							=			'bag.tally';
    const SESSION_TALLY_FRAG					=			'bag.tallyFrag';
    const SESSION_CHARIOT                       ='bag.chariot';
	
	public static $BAG_OPEN_GRID = array(
			1 => array(
					self::BAG_ARM,
					BagConf::INIT_GRID_NUM_ARM,
					StatisticsDef::ST_FUNCKEY_BAG_OPENGRID_ARM
			),
			2 => array(
					self::BAG_PROPS,
					BagConf::INIT_GRID_NUM_PROPS,
					StatisticsDef::ST_FUNCKEY_BAG_OPENGRID_PROPS,
			),
			3 => array(
					self::BAG_TREAS,
					BagConf::INIT_GRID_NUM_TREAS,
					StatisticsDef::ST_FUNCKEY_BAG_OPENGRID_TREAS,
			),
			4 => array(
					self::BAG_ARM_FRAG,
					BagConf::INIT_GRID_NUM_ARM_FRAG,
					StatisticsDef::ST_FUNCKEY_BAG_OPENGRID_ARM_FRAG,
			),
			5 => array(
					self::BAG_DRESS,
					BagConf::INIT_GRID_NUM_DRESS,
					StatisticsDef::ST_FUNCKEY_BAG_OPENGRID_DRESS,
			),
            6 => array(
                    self::BAG_GOD_WEAPON,
                    BagConf::INIT_GRID_NUM_GOD_WEAPON,
                    StatisticsDef::ST_FUNCKEY_BAG_OPENGRID_GOD_WEAPON,
            ),
            7 => array(
                    self::BAG_GOD_WEAPON_FRAG,
                    BagConf::INIT_GRID_NUM_GOD_WEAPON_FRAG,
                    StatisticsDef::ST_FUNCKEY_BAG_OPENGRID_GOD_WEAPON_FRAG,
            ),
			8 => array(
					self::BAG_RUNE,
					BagConf::INIT_GRID_NUM_RUNE,
					StatisticsDef::ST_FUNCKEY_BAG_OPENGRID_RUNE,
			),
			9 => array(
					self::BAG_RUNE_FRAG,
					BagConf::INIT_GRID_NUM_RUNE_FRAG,
					StatisticsDef::ST_FUNCKEY_BAG_OPENGRID_RUNE_FRAG,
			),
			10 => array(
					self::BAG_POCKET,
					BagConf::INIT_GRID_NUM_POCKET,
					StatisticsDef::ST_FUNCKEY_BAG_OPENGRID_POCKET,
			),
			11 => array(
					self::BAG_TALLY,
					BagConf::INIT_GRID_NUM_TALLY,
					StatisticsDef::ST_FUNCKEY_BAG_OPENGRID_TALLY,
			),
			12 => array(
					self::BAG_TALLY_FRAG,
					BagConf::INIT_GRID_NUM_TALLY_FRAG,
					StatisticsDef::ST_FUNCKEY_BAG_OPENGRID_TALLY_FRAG,
			),
			13=>array(
					self::BAG_CHARIOT,
					BagConf::INIT_GRID_NUM_CHARIOT,
					StatisticsDef::ST_FUNCKEY_BAG_OPENGRID_CHARIOT,
			),
	);
	
	public static $BAG_INIT_GRID = array(
			self::BAG_ARM => BagConf::INIT_GRID_NUM_ARM,
			self::BAG_PROPS => BagConf::INIT_GRID_NUM_PROPS,
			self::BAG_TREAS => BagConf::INIT_GRID_NUM_TREAS,
			self::BAG_ARM_FRAG => BagConf::INIT_GRID_NUM_ARM_FRAG,
			self::BAG_DRESS => BagConf::INIT_GRID_NUM_DRESS,
			self::BAG_FIGHT_SOUL => BagConf::INIT_GRID_NUM_FIGHT_SOUL,
            self::BAG_GOD_WEAPON => BagConf::INIT_GRID_NUM_GOD_WEAPON,
            self::BAG_GOD_WEAPON_FRAG => BagConf::INIT_GRID_NUM_GOD_WEAPON_FRAG,
			self::BAG_RUNE => BagConf::INIT_GRID_NUM_RUNE,
			self::BAG_RUNE_FRAG => BagConf::INIT_GRID_NUM_RUNE_FRAG,
			self::BAG_POCKET => BagConf::INIT_GRID_NUM_POCKET,
			self::BAG_TALLY => BagConf::INIT_GRID_NUM_TALLY,
			self::BAG_TALLY_FRAG => BagConf::INIT_GRID_NUM_TALLY_FRAG,
			self::BAG_CHARIOT=> BagConf::INIT_GRID_NUM_CHARIOT,
	);
	
	public static $BAG_GRID_START = array(
			self::BAG_TMP => self::GRID_START_TMP,
			self::BAG_ARM => self::GRID_START_ARM,
			self::BAG_PROPS => self::GRID_START_PROPS,
			self::BAG_HERO_FRAG => self::GRID_START_HERO_FRAG,
			self::BAG_TREAS => self::GRID_START_TREAS,
			self::BAG_ARM_FRAG => self::GRID_START_ARM_FRAG,
			self::BAG_DRESS => self::GRID_START_DRESS,
			self::BAG_FIGHT_SOUL => self::GRID_START_FIGHT_SOUL,
			self::BAG_PET_FRAG => self::GRID_START_PET_FRAG,
            self::BAG_GOD_WEAPON => self::GRID_START_GOD_WEAPON,
            self::BAG_GOD_WEAPON_FRAG => self::GRID_START_GOD_WEAPON_FRAG,
			self::BAG_RUNE => self::GRID_START_RUNE,
			self::BAG_RUNE_FRAG => self::GRID_START_RUNE_FRAG,
			self::BAG_POCKET => self::GRID_START_POCKET,
			self::BAG_TALLY => self::GRID_START_TALLY,
			self::BAG_TALLY_FRAG => self::GRID_START_TALLY_FRAG,
			self::BAG_CHARIOT    =>self::GRID_START_CHARIOT,
	);
	
	public static $BAG_IN_SESSION = array(
			self::BAG_TMP => self::SESSION_TMP,
			self::BAG_ARM => self::SESSION_ARM,
			self::BAG_PROPS => self::SESSION_PROPS,
			self::BAG_HERO_FRAG => self::SESSION_HERO_FRAG,
			self::BAG_TREAS => self::SESSION_TREAS,
			self::BAG_ARM_FRAG => self::SESSION_ARM_FRAG,
			self::BAG_DRESS => self::SESSION_DRESS,
			self::BAG_FIGHT_SOUL => self::SESSION_FIGHT_SOUL,
			self::BAG_PET_FRAG => self::SESSION_PET_FRAG,
            self::BAG_GOD_WEAPON => self::SESSION_GOD_WEAPON,
            self::BAG_GOD_WEAPON_FRAG => self::SESSION_GOD_WEAPON_FRAG,
			self::BAG_RUNE => self::SESSION_RUNE,
			self::BAG_RUNE_FRAG => self::SESSION_RUNE_FRAG,
			self::BAG_POCKET => self::SESSION_POCKET,
			self::BAG_TALLY => self::SESSION_TALLY,
			self::BAG_TALLY_FRAG => self::SESSION_TALLY_FRAG,
			self::BAG_CHARIOT=>self::SESSION_CHARIOT,
			
	);
	
	/**bag SQL**/
	//bag table name
	const BAG_TABLE_NAME						=			't_bag';

	//Bag TABLE
	const SQL_ITEM_ID							=			'item_id';
	const SQL_UID								=			'uid';
	const SQL_GID								=			'gid';
	/**end** bag SQL**/
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */