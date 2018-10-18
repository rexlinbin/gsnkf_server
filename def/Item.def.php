<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Item.def.php 251627 2016-07-14 11:23:17Z BaoguoMeng $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Item.def.php $
 * @author $Author: BaoguoMeng $(jhd@babeltime.com)
 * @date $Date: 2016-07-14 11:23:17 +0000 (Thu, 14 Jul 2016) $
 * @version $Revision: 251627 $
 * @brief
 *
 **/

class ItemDef
{
	//物品分类
	const ITEM_TYPE_HEROFRAG								=			1;
	const ITEM_TYPE_ARM										=			2;
	const ITEM_TYPE_DIRECT									=			3;
	const ITEM_TYPE_BOOK									=			4;
	const ITEM_TYPE_GIFT									=			5;
	const ITEM_TYPE_RANDGIFT								=			6;
	const ITEM_TYPE_GOODWILL								=			7;
	const ITEM_TYPE_FRAGMENT								=			8;
	const ITEM_TYPE_FEED									= 			9;
	const ITEM_TYPE_NORMAL									=			10;
	const ITEM_TYPE_TREASURE								=			11;
	const ITEM_TYPE_TREASFRAG								=			12;	
	const ITEM_TYPE_FIGHTSOUL								=			13;
	const ITEM_TYPE_DRESS									=			14;
	const ITEM_TYPE_PETFRAG									=			15;
    const ITEM_TYPE_GODWEAPON                               =           16;
    const ITEM_TYPE_GODWEAPONFRAG                           =           17;
    const ITEM_TYPE_RUNE									=			18;
    const ITEM_TYPE_RUNEFRAG								=			19;
    const ITEM_TYPE_POCKET									=			20;
	const ITEM_TYPE_TALLY									=			21;
	const ITEM_TYPE_TALLYFRAG								=			22;
	const ITEM_TYPE_CHARIOT                              =23;
	
	//物品品质常数
	//白色品质1,2
	const ITEM_QUALITY_WHITE								=			2;
	//绿色品质
	const ITEM_QUALITY_GREEN								=			3;
	//蓝色品质
	const ITEM_QUALITY_BLUE									=			4;
	//紫色品质
	const ITEM_QUALITY_PURPLE								=			5;
	//橙色品质
	const ITEM_QUALITY_ORANGE								=			6;
	//红色品质
	const ITEM_QUALITY_RED									=			7;
	
	//物品常数
	//物品不可出售
	const ITEM_CAN_NOT_SELL									=			0;
	//物品不可叠加常数
	const ITEM_CAN_NOT_STACKABLE							=			1;
	//物品可摧毁
	const ITEM_CAN_DESTORY									=			1;
	//物品可使用
	const ITEM_CAN_USE										=			1;
	//no item
	const ITEM_ID_NO_ITEM									=			0;
	//出售类型
	const ITEM_SELL_TYPE_SILVER								=			1;
	
	//装备数量
	const ARM_NUM = 4;
	
	//每类物品放在哪个背包
	public static $MAP_ITEM_TYPE_BAG_NAME = array(
			self::ITEM_TYPE_HEROFRAG  						=> 			BagDef::BAG_HERO_FRAG,
			self::ITEM_TYPE_ARM 							=> 			BagDef::BAG_ARM,
			self::ITEM_TYPE_DIRECT 							=> 			BagDef::BAG_PROPS,
			self::ITEM_TYPE_BOOK 							=> 			BagDef::BAG_PROPS,
			self::ITEM_TYPE_GIFT							=> 			BagDef::BAG_PROPS,
			self::ITEM_TYPE_RANDGIFT 						=> 			BagDef::BAG_PROPS,
			self::ITEM_TYPE_GOODWILL 						=> 			BagDef::BAG_PROPS,
			self::ITEM_TYPE_FRAGMENT 						=>			BagDef::BAG_ARM_FRAG,
			self::ITEM_TYPE_FEED      						=> 			BagDef::BAG_PROPS,
			self::ITEM_TYPE_NORMAL							=>			BagDef::BAG_PROPS,
			self::ITEM_TYPE_TREASURE						=>			BagDef::BAG_TREAS,
			self::ITEM_TYPE_FIGHTSOUL						=>			BagDef::BAG_FIGHT_SOUL,
			self::ITEM_TYPE_DRESS							=>			BagDef::BAG_DRESS,
			self::ITEM_TYPE_PETFRAG							=>			BagDef::BAG_PET_FRAG,
            self::ITEM_TYPE_GODWEAPON                       =>          BagDef::BAG_GOD_WEAPON,
            self::ITEM_TYPE_GODWEAPONFRAG                   =>          BagDef::BAG_GOD_WEAPON_FRAG,
            self::ITEM_TYPE_RUNE                       		=>          BagDef::BAG_RUNE,
            self::ITEM_TYPE_RUNEFRAG                   		=>          BagDef::BAG_RUNE_FRAG,
            self::ITEM_TYPE_POCKET                   		=>          BagDef::BAG_POCKET,
            self::ITEM_TYPE_TALLY                       	=>          BagDef::BAG_TALLY,
            self::ITEM_TYPE_TALLYFRAG                   	=>          BagDef::BAG_TALLY_FRAG,
            self::ITEM_TYPE_CHARIOT                      => BagDef::BAG_CHARIOT,
	);
	
	//技能书正确的类型 TODO： 策划以后确定类型
	public static $BOOK_VALID_TYPES = array(0,1,2,3,4,5,6,7,8,9);
	
	//限制出售的物品类型
	public static $SELL_INVALID_TYPES = array(
			self::ITEM_TYPE_ARM,
			self::ITEM_TYPE_TREASURE,
	);
	
	//碎片类型
	public static $FRAG_VALID_TYPES = array(
			self::ITEM_TYPE_FRAGMENT,
			self::ITEM_TYPE_HEROFRAG,
			self::ITEM_TYPE_PETFRAG,
            self::ITEM_TYPE_GODWEAPONFRAG,
			self::ITEM_TYPE_RUNEFRAG,
			self::ITEM_TYPE_TALLYFRAG,
	);
	
	//直接使用类和礼包类物品有使用上限
	public static $USE_LIMIT_TYPES = array(
			self::ITEM_TYPE_DIRECT => 5,
			self::ITEM_TYPE_RANDGIFT => 5, 
	);
		
	//物品属性名
	//基本属性
	const ITEM_ATTR_NAME_TEMPLATE							=			'templateId';
	const ITEM_ATTR_NAME_TYPE								=			'type';
	const ITEM_ATTR_NAME_NUM								=			'num';
	const ITEM_ATTR_NAME_QUALITY							=			'quality';
	const ITEM_ATTR_NAME_SELL								=			'sell';
	const ITEM_ATTR_NAME_SELL_PRICE							=			'sellPrice';
	const ITEM_ATTR_NAME_SELL_TYPE							=			'sellType';
	const ITEM_ATTR_NAME_BIND								=			'bind';	
	const ITEM_ATTR_NAME_STACKABLE							=			'stackable';
	const ITEM_ATTR_NAME_DESTORY							=			'destory';	
	const ITEM_ATTR_NAME_EXP								=			'exp';
	const ITEM_ATTR_NAME_USE								=			'use';
	const ITEM_ATTR_NAME_VALUE								=			'value';
	
	//normal普通物品特有字段
	const ITEM_ATTR_NAME_NORMAL_CAN_DONATE					=			'normalCanDonate';
	const ITEM_ATTR_NAME_NORMAL_FAME	 					=			'normalFame';
	const ITEM_ATTR_NAME_NORMAL_IS_HERO_JH_ITEM				=			'normalIsHeroJHItem';		// 是否是武将精华物品
	const ITEM_ATTR_NAME_NORMAL_RESOLVE_HERO_JH_GET			=			'normalResolveHeroJHGet';	// 武将精华物品分解获得的精华数值
	const ITEM_ATTR_NAME_NORMAL_TALLYEXP					=			'normalTallyExp';
	
	//herofrag武将碎片类物品特有字段
	const ITEM_ATTR_NAME_HEROFRAG_UNIVERSAL					=			'heroFragUniversalNum';
	const ITEM_ATTR_NAME_HEROFRAG_FORM						=			'heroFragFormId';
	
	//direct直接使用类物品特有字段
	const ITEM_ATTR_NAME_USE_CHAT							=			'useChat';
	//使用要求
	const ITEM_ATTR_NAME_USE_REQ							=			'useReq';
	const ITEM_ATTR_NAME_USE_REQ_ITEMS						=			'useReqItems';
	const ITEM_ATTR_NAME_USE_REQ_SILVER						=			'useReqSilver';
	const ITEM_ATTR_NAME_USE_REQ_GOLD						=			'useReqGold';
	const ITEM_ATTR_NAME_USE_REQ_DELAY_TIME					=			'useReqDelayTime';
	const ITEM_ATTR_NAME_USE_REQ_USER_LEVEL					=			'useReqUserLevel';
	
	//使用获得
	const ITEM_ATTR_NAME_USE_ACQ							=			'useAcq';
	const ITEM_ATTR_NAME_USE_ACQ_SILVER						=			'useAcqSilver';
	const ITEM_ATTR_NAME_USE_ACQ_GOLD						=			'useAcqGold';
	const ITEM_ATTR_NAME_USE_ACQ_EXECUTION					=			'useAcqExecution';
	const ITEM_ATTR_NAME_USE_ACQ_SOUL						=			'useAcqSoul';
	const ITEM_ATTR_NAME_USE_ACQ_STAMINA					=			'useAcqStamina';
	const ITEM_ATTR_NAME_USE_ACQ_ITEMS						=			'useAcqItems';
	const ITEM_ATTR_NAME_USE_ACQ_HERO						=			'useAcqHero';
	const ITEM_ATTR_NAME_USE_ACQ_CHALLENGE 					=			'useAcqChallenge';
	const ITEM_ATTR_NAME_USE_ACQ_DROP						=			'useAcqDrop';
	const ITEM_ATTR_NAME_USE_ACQ_PET						=			'useAcqPet';
	const ITEM_ATTR_NAME_USE_ACQ_DROP_SPECIAL				=			'useAcqDropSpecial';
	const ITEM_ATTR_NAME_USE_ACQ_EXP						=			'useAcqExp';
	const ITEM_ATTR_NAME_IS_ADD_VIP_EXP						=			'isAddVipExp';
	const ITEM_ATTR_NAME_USE_ACQ_PRESTIGE					=			'useAcqPrestige';
	const ITEM_ATTR_NAME_USE_ACQ_BOOK						=			'useAcqBook';

	//book技能书特有的字段
	const ITEM_ATTR_NAME_BOOK_ATTRS							=			'bookAttrs';
	const ITEM_ATTR_NAME_BOOK_SKILLS						=			'bookSkills';
	const ITEM_ATTR_NAME_BOOK_ERASURE						=			'bookErasure';
	const ITEM_ATTR_NAME_BOOK_ERASURE_SILVER				=			'bookErasureSilver';
	const ITEM_ATTR_NAME_BOOK_ERASURE_GOLD					=			'bookErasureGold';
	const ITEM_ATTR_NAME_BOOK_ERASURE_ITEMS					=			'bookErasureItems';
	const ITEM_ATTR_NAME_BOOK_LEVEL_EXTRA					=			'bookLevelExtra';
	const ITEM_ATTR_NAME_BOOK_EQUIP_SLOT					=			'bookEquipSlot';
	const ITEM_ATTR_NAME_BOOK_TYPE							=			'bookType';
	const ITEM_ATTR_NAME_BOOK_SKILL_BUFF_GROUP				=			'bookSkillBuffGroup';
	const ITEM_ATTR_NAME_BOOK_EXP							=			'bookExp';
	const ITEM_ATTR_NAME_BOOK_CAN_LEVEL_UP					=			'bookCanLevelUp';
	const ITEM_ATTR_NAME_BOOK_LEVEL_TABLE					=			'bookLevelTable';
	const ITEM_ATTR_NAME_BOOK_MAX_LEVEL						=			'bookMaxLevel';
	const ITEM_ATTR_NAME_BOOK_MIN_LEVEL 					=			0;
	
	//gift礼包类物品特有字段
	const ITEM_ATTR_NAME_GIFT_NUM							=			'giftItemNum';
	const ITEM_ATTR_NAME_GIFT_OPTIONS						=			'giftOptions';
	
	//goodwill好感度礼物类物品特有字段
	const ITEM_ATTR_NAME_GOODWILL_EXP						=			'goodWillExp';
	
	//fragment碎片类物品特有字段
	const ITEM_ATTR_NAME_FRAGMENT_NUM						=			'fragmentNum';
	const ITEM_ATTR_NAME_FRAGMENT_FORM						=			'fragmentFormId';
	
	//feed饲料类物品特有字段
	const ITEM_ATTR_NAME_FEED_EXP							= 			'feedExp'; 
	
	//treasFrag宝物碎片类物品特有字段
	const ITEM_ATTR_NAME_TREASFRAG_FORM						=			'treasFragFormId';
	const ITEM_ATTR_NAME_TREASFRAG_ROBRATIO_BASE			=			'treasFragRobRatioBase';
	const ITEM_ATTR_NAME_TREASFRAG_ROBRATIO_NPC				=			'treasFragRobRatioNpc';
	const ITEM_ATTR_NAME_TREASFRAG_SPECIAL_NUM				=			'treasFragSpecialNum';
	
	//dress时装类物品特有字段
	const ITEM_ATTR_NAME_DRESS_ATTRS						=			'dressAttrs';
 	const ITEM_ATTR_NAME_DRESS_COST							=			'dressCost';
 	const ITEM_ATTR_NAME_DRESS_RESOLVE						=			'dressResolve';
 	const ITEM_ATTR_NAME_DRESS_REBORN						=			'dressReborn';
 	const ITEM_ATTR_NAME_DRESS_LEVEL						=			'dressLevel';
 	const ITEM_ATTR_NAME_DRESS_EXTRA						=			'dressExtra';
 	const ITEM_ATTR_NAME_DRESS_EXTRA_ATTR        =       'dressExtraAttr';
	
	//时装分类
	const DRESS_TYPE_FASHION								=			1;
	
	//默认所有时装为空
	public static $HERO_NO_DRESS = array (
			self::DRESS_TYPE_FASHION						=>			ItemDef::ITEM_ID_NO_ITEM,
	);
	
	//petfrag宠物碎片类物品特有字段
	const ITEM_ATTR_NAME_PETFRAG_NUM						=			'petFragNum';
	const ITEM_ATTR_NAME_PETFRAG_FORM						=			'petFragFormId';
	
	//rune符印类物品特有字段
	const ITEM_ATTR_NAME_RUNE_TYPE							=			'runeType';
	const ITEM_ATTR_NAME_RUNE_FEATURE						=			'runeFeature';
	const ITEM_ATTR_NAME_RUNE_ATTR							=			'runeAttr';
	const ITEM_ATTR_NAME_RUNE_RESOLVE						=			'runeResolve';
	
	//runefrag符印碎片类物品特有字段
	const ITEM_ATTR_NAME_RUNEFRAG_FORM						=			'runeFragFormId';
	
	//tally兵符类物品特有字段
	const ITEM_ATTR_NAME_TALLY_TYPE							=			'tallyType';
	const ITEM_ATTR_NAME_TALLY_EXPID						=			'tallyExpId';
	const ITEM_ATTR_NAME_TALLY_LEVEL_LIMIT					=			'tallyLevelLimit';
	const ITEM_ATTR_NAME_TALLY_UPGRADE_COST					=			'tallyUpgradeCost';
	const ITEM_ATTR_NAME_TALLY_ATTRS						=			'tallyAttrs';
	const ITEM_ATTR_NAME_TALLY_DEVELOP_NEED					=			'tallyDevelopNeed';
	const ITEM_ATTR_NAME_TALLY_DEVELOP_COST					=			'tallyDevelopCost';
	const ITEM_ATTR_NAME_TALLY_DEVELOP_ATTRS				=			'tallyDevelopAttrs';
	const ITEM_ATTR_NAME_TALLY_EVOLVE_NEED					=			'tallyEvolveNeed';
	const ITEM_ATTR_NAME_TALLY_EVOLVE_COST					=			'tallyEvolveCost';
	const ITEM_ATTR_NAME_TALLY_EVOLVE_EFFECT				=			'tallyEvolveEffect';
	const ITEM_ATTR_NAME_TALLY_POINT						=			'tallyPoint';
	const ITEM_ATTR_NAME_TALLY_BOOK_ATTRS					=			'tallyBookAttrs';
	const ITEM_ATTR_NAME_TALLY_REBORN_COST					=			'tallyRebornCost';
	const ITEM_ATTR_NAME_TALLY_LEVEL						=			'tallyLevel';
	const ITEM_ATTR_NAME_TALLY_EXP							=			'tallyExp';
	const ITEM_ATTR_NAME_TALLY_EVOLVE						=			'tallyEvolve';
	const ITEM_ATTR_NAME_TALLY_DEVELOP						=			'tallyDevelop';
	
	//tally兵符类物品常量
	const ITEM_ATTR_NAME_TALLY_INIT_LEVEL					=			0;
	const ITEM_ATTR_NAME_TALLY_INIT_EXP						=			0;
	const ITEM_ATTR_NAME_TALLY_INIT_EVOLVE					=			0;
	const ITEM_ATTR_NAME_TALLY_INIT_DEVELOP					=			0;
	
	//兵符经验物品
	public static $TALLY_EXP_ITEMS = array(63003,63002,63001);
	
	//tallyfrag兵符碎片类物品特有字段
	const ITEM_ATTR_NAME_TALLYFRAG_FORM						=			'tallyFragFormId';

	//SQL attribute name
	const ITEM_TABLE_NAME									=			't_item';
	const ITEM_SQL_ITEM_ID									=			'item_id';
	const ITEM_SQL_ITEM_TEMPLATE_ID							=			'item_template_id';
	const ITEM_SQL_ITEM_NUM									=			'item_num';
	const ITEM_SQL_ITEM_TIME								=			'item_time';
	const ITEM_SQL_ITEM_DELTIME								=			'item_deltime';
	const ITEM_SQL_ITEM_TEXT								=			'va_item_text';
	const ITEM_TEXT_LOCK									=			'lock';
	
	//SQL
	public static $ITEM_ALLOW_UPDATE_COL					=			array(ItemDef::ITEM_SQL_ITEM_NUM, ItemDef::ITEM_SQL_ITEM_TEXT);

	//神兵 宝物 锦囊 重生拆成多个物品的上限值
	const UPPER_LIMIT_NUM_FOR_EXP_ITEM						=			10;
	//单个锦囊经验物品可存放经验的最大值
	const UPPER_LIMIT_EXP_FOR_POCKET						=			150000;
	//单个神兵经验物品可存放经验的最大值
	const UPPER_LIMIT_EXP_FOR_GOD_WEAPON					=			20000;
	//单个宝物经验物品可存放经验的最大值
	const UPPER_LIMIT_EXP_FOR_TREASURE						=			150000;
	
	//战车特有字段
	const ITEM_ATTR_NAME_CHARIOT_TYPE = 'chariotType';//战车类型
	const ITEM_ATTR_NAME_CHARIOT_ENFORCE_COST ='enforceCost';//强化消耗
	const ITEM_ATTR_NAME_CHARIOT_MAX_LEVEL ='maxLevel';//最大等级
	const ITEM_ATTR_NAME_CHARIOT_BASE_ATTR = 'baseAttr';//基础属性
	const ITEM_ATTR_NAME_CHARIOT_GROW_ATTR='growAttr';//每级成长属性
	const ITEM_ATTR_NAME_CHARIOT_RESOLVE_GOT='resolveGot';//分解获得
	const ITEM_ATTR_NAME_CHARIOT_BOOK_ATTR='bookAttr';//图鉴属性
	const ITEM_ATTR_NAME_CHARIOT_REBORN_COST='rebornCost';//重生花费
	const ITEM_ATTR_NAME_CHARIOT_ROUND='round';//战车在第几个回合放技能
	const ITEM_ATTR_NAME_CHARIOT_SKILL='skill';//战车技能
	const ITEM_ATTR_NAME_CHARIOT_FIGHT_RATIO='fightRatio';//战车战斗系数
	const ITEM_ATTR_NAME_CHARIOT_BASE_CRITICAL   = 'baseCritical';//暴击率基础值
	const ITEM_ATTR_NAME_CHARIOT_BASE_CRITICAL_MUTIPLE = 'baseCriticalMutiple';//暴击伤害倍数
	const ITEM_ATTR_NAME_CHARIOT_BASE_HIT ='baseHit';//基础命中
	const ITEM_ATTR_NAME_CHARIOT_PHYSICAL_ATTACK_RATIO ='physicalAttackRatio';
	const ITEM_ATTR_NAME_CHARIOT_MAGIC_ATTACK_RATIO ='magicAttackRatio';
	const ITEM_ATTR_NAME_CHARIOT_ENFORCE= 'chariotEnforce';//战车强化等级
	const ITEM_ATTR_NAME_CHARIOT_DEVELOP= 'chariotDevelop';//战车进阶等级
	
	//战车常量
	const ITEM_ATTR_NAME_CHARIOT_INIT_ENFORCE_LV =0;//战车初始强化等级
	const ITEM_ATTR_NAME_CHARIOT_INIT_DEVELOP_LV    =0;//战车初始进阶等级
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */