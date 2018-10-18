<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Items.script.php 250248 2016-07-06 09:32:12Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/Items.script.php $
 * @author $Author: QingYao $(wuqilin@babeltime.com)
 * @date $Date: 2016-07-06 09:32:12 +0000 (Wed, 06 Jul 2016) $
 * @version $Revision: 250248 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Property.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Item.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Bag.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Arm.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Treasure.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/FightSoul.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/GodWeapon.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Union.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Pocket.def.php";

$csvFile = array(
		'item_hero_fragment.csv',
		'item_arm.csv',
		'item_direct.csv',
		'item_book.csv',
		'item_gift.csv',
		'item_randgift.csv',
		'item_star_gift.csv',
		'item_fragment.csv',
		'item_feed.csv',
		'item_normal.csv',
		'item_treasure.csv',
		'item_treasure_fragment.csv',
		'item_fightsoul.csv',
		'item_dress.csv',
		'item_pet_fragment.csv',
        'item_godarm.csv',
        'item_godarm_fragment.csv',
		'item_fuyin.csv',
		'item_fuyin_fragment.csv',
		'item_pocket.csv',
		'item_bingfu.csv',
		'item_bingfu_fragment.csv',
		'item_warcar.csv'
);
$outFileName = 'ITEMS';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	$csvFileStr = implode(' ', $csvFile);
	exit("usage: $csvFileStr $outFileName\n");
}

if ( $argc < 3 )
{
	trigger_error("Please input enough arguments: inputDir && outputDir!\n");
}

$inputDir = $argv[1];
$outputDir = $argv[2];

$arrItemType = array(	
		ItemDef::ITEM_TYPE_HEROFRAG => array(
				'readFile' => 'HeroFragItem.php',
				'readFunc' => 'readHeroFragItem'
				),
		ItemDef::ITEM_TYPE_ARM => array(
				'readFile' => 'ArmItem.php',
				'readFunc' => 'readArmItem'
				),
		ItemDef::ITEM_TYPE_DIRECT => array(
				'readFile' => 'DirectItem.php',
				'readFunc' => 'readDirectItem'
				),
		ItemDef::ITEM_TYPE_BOOK => array(
				'readFile' => 'BookItem.php',
				'readFunc' => 'readBookItem'
				),
		ItemDef::ITEM_TYPE_GIFT => array(
				'readFile' => 'GiftItem.php',
				'readFunc' => 'readGiftItem'
				),
		ItemDef::ITEM_TYPE_RANDGIFT => array(
				'readFile' => 'RandGiftItem.php',
				'readFunc' => 'readRandGiftItem'
				),
		ItemDef::ITEM_TYPE_GOODWILL => array(
				'readFile' => 'GoodWillItem.php',
				'readFunc' => 'readGoodWillItem'
				),
		ItemDef::ITEM_TYPE_FRAGMENT => array(
				'readFile' => 'FragmentItem.php',
				'readFunc' => 'readFragmentItem'
				),
		ItemDef::ITEM_TYPE_FEED => array(
				'readFile' => 'FeedItem.php',
				'readFunc' => 'readFeedItem'
				),
		ItemDef::ITEM_TYPE_NORMAL => array(
				'readFile' => 'NormalItem.php',
				'readFunc' => 'readNormalItem'
				),
		ItemDef::ITEM_TYPE_TREASURE => array(
				'readFile' => 'TreasureItem.php',
				'readFunc' => 'readTreasureItem'
				),
		ItemDef::ITEM_TYPE_TREASFRAG => array(
				'readFile' => 'TreasFragItem.php',
				'readFunc' => 'readTreasFragItem'
				),
		ItemDef::ITEM_TYPE_FIGHTSOUL => array(
				'readFile' => 'FightSoulItem.php',
				'readFunc' => 'readFightSoulItem'
				),
		ItemDef::ITEM_TYPE_DRESS => array(
				'readFile' => 'DressItem.php',
				'readFunc' => 'readDressItem'
				),
		ItemDef::ITEM_TYPE_PETFRAG => array(
				'readFile' => 'PetFragItem.php',
				'readFunc' => 'readPetFragItem'
				),
        ItemDef::ITEM_TYPE_GODWEAPON => array(
                'readFile' => 'GodWeapon.php',
                'readFunc' => 'readGodWeapon'
        		),
        ItemDef::ITEM_TYPE_GODWEAPONFRAG => array(
                'readFile' => 'GodWeaponFrag.php',
                'readFunc' => 'readGodWeaponFrag'
        		),
		ItemDef::ITEM_TYPE_RUNE => array(
				'readFile' => 'RuneItem.php',
				'readFunc' => 'readRuneItem'
				),
		ItemDef::ITEM_TYPE_RUNEFRAG => array(
				'readFile' => 'RuneFragItem.php',
				'readFunc' => 'readRuneFragItem'
				),
		ItemDef::ITEM_TYPE_POCKET => array(
				'readFile' => 'PocketItem.php',
				'readFunc' => 'readPocketItem'
				),
		ItemDef::ITEM_TYPE_TALLY => array(
				'readFile' => 'TallyItem.php',
				'readFunc' => 'readTallyItem'
				),
		ItemDef::ITEM_TYPE_TALLYFRAG => array(
				'readFile' => 'TallyFragItem.php',
				'readFunc' => 'readTallyFragItem'
				),
		ItemDef::ITEM_TYPE_CHARIOT=>array(
				'readFile' => 'ChariotItem.php',
				'readFunc' => 'readChariotItem'
)
);

//先把各个类型的物品都读进来
$allItem = array();
foreach ( $arrItemType as $type => $value )
{
	require_once dirname ( __FILE__ ) . '/'. $value['readFile'];
	
	$arrItem = call_user_func($value['readFunc'], $inputDir);
	
	foreach( $arrItem as $itemId => $itemConf)
	{
		$arrItem[$itemId][ItemDef::ITEM_ATTR_NAME_TYPE] = $type;
	}
	
	$allItem = $allItem + $arrItem;
}

//输出文件
$file = fopen("$outputDir/$outFileName", "w");
if ( $file == FALSE )
{
	trigger_error( "$outputDir/$outFileName open failed! exit!\n" );
}
fwrite($file, serialize($allItem));
fclose($file);


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */