<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Items.validate.php 218720 2015-12-30 09:13:52Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/Items.validate.php $
 * @author $Author: MingTian $(wuqilin@babeltime.com)
 * @date $Date: 2015-12-30 09:13:52 +0000 (Wed, 30 Dec 2015) $
 * @version $Revision: 218720 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Property.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Item.def.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Arm.def.php";

$arrItemType = array(
		ItemDef::ITEM_TYPE_HEROFRAG => array(
				'testFunc' => 'testHeroFragItem'
		),
		ItemDef::ITEM_TYPE_ARM => array(
				'testFunc' => 'testArmItem'
		),
		ItemDef::ITEM_TYPE_DIRECT => array(
				'testFunc' => 'testDirectItem'
		),
		ItemDef::ITEM_TYPE_BOOK => array(
				'testFunc' => 'testBookItem'
		),
		ItemDef::ITEM_TYPE_GIFT => array(
				'testFunc' => 'testGiftItem'
		),
		ItemDef::ITEM_TYPE_RANDGIFT => array(
				'testFunc' => 'testRandGiftItem'
		),
		ItemDef::ITEM_TYPE_GOODWILL => array(
				'testFunc' => 'testGoodWillItem'
		),
		ItemDef::ITEM_TYPE_FRAGMENT => array(
				'testFunc' => 'testFragmentItem'
		),
		ItemDef::ITEM_TYPE_FEED => array(
				'testFunc' => 'testFeedItem'
		),
		ItemDef::ITEM_TYPE_NORMAL => array(
				'testFunc' => 'testNormalItem'
		),
		ItemDef::ITEM_TYPE_TREASURE => array(
				'testFunc' => 'testTreasureItem'
		),
		ItemDef::ITEM_TYPE_TREASFRAG => array(
				'testFunc' => 'testTreasFragItem'
		),
		ItemDef::ITEM_TYPE_FIGHTSOUL => array(
				'testFunc' => 'testFightSoulItem'
		),
		ItemDef::ITEM_TYPE_DRESS => array(
				'testFunc' => 'testDressItem'
		),
		ItemDef::ITEM_TYPE_PETFRAG => array(
				'testFunc' => 'testPetFragItem'
		),
        ItemDef::ITEM_TYPE_GODWEAPON => array(
                'testFunc' => 'testGodWeapon'
        ),
        ItemDef::ITEM_TYPE_GODWEAPONFRAG => array(
                'testFunc' => 'testGodWeaponFrag'
        ),
		ItemDef::ITEM_TYPE_RUNE => array(
				'testFunc' => 'testRune'
		),
		ItemDef::ITEM_TYPE_RUNEFRAG => array(
				'testFunc' => 'testRuneFrag'
		),
		ItemDef::ITEM_TYPE_POCKET => array(
				'testFunc' => 'testPocket'
		),
		ItemDef::ITEM_TYPE_TALLY => array(
				'testFunc' => 'testTally'
		),
		ItemDef::ITEM_TYPE_TALLYFRAG => array(
				'testFunc' => 'testTallyFrag'
		),
);

$mapTypeItem = array();
$allItem = btstore_get()->ITEMS;

foreach ($allItem as $itemId => $itemConf)
{
	switch ($itemConf[ItemDef::ITEM_ATTR_NAME_TYPE])
	{
		case ItemDef::ITEM_TYPE_HEROFRAG:
			$mapTypeItem[ItemDef::ITEM_TYPE_HEROFRAG][$itemId] = $itemConf;
			break;
		case ItemDef::ITEM_TYPE_ARM:
			$mapTypeItem[ItemDef::ITEM_TYPE_ARM][$itemId] = $itemConf;
			break;
		case ItemDef::ITEM_TYPE_DIRECT:
			$mapTypeItem[ItemDef::ITEM_TYPE_DIRECT][$itemId] = $itemConf;
			break;
		case ItemDef::ITEM_TYPE_BOOK:
			$mapTypeItem[ItemDef::ITEM_TYPE_BOOK][$itemId] = $itemConf;
			break;
		case ItemDef::ITEM_TYPE_GIFT:
			$mapTypeItem[ItemDef::ITEM_TYPE_GIFT][$itemId] = $itemConf;
			break;
		case ItemDef::ITEM_TYPE_RANDGIFT:
			$mapTypeItem[ItemDef::ITEM_TYPE_RANDGIFT][$itemId] = $itemConf;
			break;
		case ItemDef::ITEM_TYPE_GOODWILL:
			$mapTypeItem[ItemDef::ITEM_TYPE_GOODWILL][$itemId] = $itemConf;
			break;
		case ItemDef::ITEM_TYPE_FRAGMENT:
			$mapTypeItem[ItemDef::ITEM_TYPE_FRAGMENT][$itemId] = $itemConf;
			break;
		case ItemDef::ITEM_TYPE_FEED:
			$mapTypeItem[ItemDef::ITEM_TYPE_FEED][$itemId] = $itemConf;
			break;
		case ItemDef::ITEM_TYPE_NORMAL:
			$mapTypeItem[ItemDef::ITEM_TYPE_NORMAL][$itemId] = $itemConf;
			break;
		case ItemDef::ITEM_TYPE_TREASURE:
			$mapTypeItem[ItemDef::ITEM_TYPE_TREASURE][$itemId] = $itemConf;
			break;
		case ItemDef::ITEM_TYPE_TREASFRAG:
			$mapTypeItem[ItemDef::ITEM_TYPE_TREASFRAG][$itemId] = $itemConf;
			break;
		case ItemDef::ITEM_TYPE_FIGHTSOUL:
			$mapTypeItem[ItemDef::ITEM_TYPE_FIGHTSOUL][$itemId] = $itemConf;
			break;
		case ItemDef::ITEM_TYPE_DRESS:
			$mapTypeItem[ItemDef::ITEM_TYPE_DRESS][$itemId] = $itemConf;
			break;
		case ItemDef::ITEM_TYPE_PETFRAG:
			$mapTypeItem[ItemDef::ITEM_TYPE_PETFRAG][$itemId] = $itemConf;
			break;
        case ItemDef::ITEM_TYPE_GODWEAPON:
            $mapTypeItem[ItemDef::ITEM_TYPE_GODWEAPON][$itemId] = $itemConf;
            break;
        case ItemDef::ITEM_TYPE_GODWEAPONFRAG:
            $mapTypeItem[ItemDef::ITEM_TYPE_GODWEAPONFRAG][$itemId] = $itemConf;
            break;
        case ItemDef::ITEM_TYPE_RUNE:
            $mapTypeItem[ItemDef::ITEM_TYPE_RUNE][$itemId] = $itemConf;
            break;
        case ItemDef::ITEM_TYPE_RUNEFRAG:
            $mapTypeItem[ItemDef::ITEM_TYPE_RUNEFRAG][$itemId] = $itemConf;
            break;
        case ItemDef::ITEM_TYPE_POCKET:
            $mapTypeItem[ItemDef::ITEM_TYPE_POCKET][$itemId] = $itemConf;
            break;
        case ItemDef::ITEM_TYPE_TALLY:
            $mapTypeItem[ItemDef::ITEM_TYPE_TALLY][$itemId] = $itemConf;
            break;
        case ItemDef::ITEM_TYPE_TALLYFRAG:
           	$mapTypeItem[ItemDef::ITEM_TYPE_TALLYFRAG][$itemId] = $itemConf;
            break;
	}
} 

foreach ( $arrItemType as $type => $value)
{
	$ret = call_user_func($value['testFunc'], $mapTypeItem[$type], $allItem);

	if( ! $ret )
	{
		trigger_error( "there are invalie config found in {$value['testFunc']}\n" );
	}
}

echo "config test ok\n";

function testHeroFragItem($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_HEROFRAG )
		{
			trigger_error("heroFrag:$itemId type is wrong!\n");
			return false;
		}
		//check hero id exist
		$heroId = key($itemConf[ItemDef::ITEM_ATTR_NAME_USE_ACQ][ItemDef::ITEM_ATTR_NAME_USE_ACQ_HERO]);
		if ( !isset(btstore_get()->HEROES[$heroId]) )
		{
			trigger_error("heroFrag:$itemId hero id:$heroId is not exist!\n");
			return false;
		}
	}	
	return true;
}

function testArmItem($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_ARM )
		{
			trigger_error("arm:$itemId type is wrong!\n");
			return false;
		}
		
		$suitId = $itemConf[ArmDef::ITEM_ATTR_NAME_ARM_SUIT];
		if ( $suitId != 0 && !isset(btstore_get()->SUIT_ITEM[$suitId])) 
		{
			trigger_error("arm:$itemId suit id:$suitId is not exist!\n");
			return false;
		}
		
		//check reinforce id exist
		$feeId = $itemConf[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE];
		if ( !isset(btstore_get()->REINFORCE_FEE[$feeId]) )
		{
			trigger_error("arm:$itemId reinforce fee id:$feeId is not exist!\n");
			return false;
		}	
		//check rand potence and refresh
		$randPotence = $itemConf[ArmDef::ITEM_ATTR_NAME_ARM_RAND_POTENCE];
		if ( !empty($randPotence) && !isset(btstore_get()->POTENCE_ITEM[$randPotence]) )
		{
			trigger_error("arm:$itemId rand potence id:$randPotence is not exist!\n");
			return false;
		}
	}
	return true;
}

function testDirectItem($arrItem, $allItem)
{

	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_DIRECT )
		{
			trigger_error("direct:$itemId type is wrong!\n");
			return false;
		}
		//check acq item
		if (!empty($itemConf[ItemDef::ITEM_ATTR_NAME_USE_ACQ][ItemDef::ITEM_ATTR_NAME_USE_ACQ_ITEMS])) 
		{
			$item = key($itemConf[ItemDef::ITEM_ATTR_NAME_USE_ACQ][ItemDef::ITEM_ATTR_NAME_USE_ACQ_ITEMS]);
			if ( !isset($allItem[$item]) )
			{
				trigger_error("direct:$itemId acq item id:$item is not exist!\n");
				return false;
			}
		}
		
		//check acq hero
		if (!empty($itemConf[ItemDef::ITEM_ATTR_NAME_USE_ACQ][ItemDef::ITEM_ATTR_NAME_USE_ACQ_HERO])) 
		{
			$heroId = $itemConf[ItemDef::ITEM_ATTR_NAME_USE_ACQ][ItemDef::ITEM_ATTR_NAME_USE_ACQ_HERO];
			if ( !isset(btstore_get()->HEROES[$heroId]) )
			{
				trigger_error("direct:$itemId acq hero id:$heroId is not exist!\n");
				return false;
			}
		}	
	}
	return true;
}

function testBookItem($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_BOOK )
		{
			trigger_error("book:$itemId type is wrong!\n");
			return false;
		}
		//技能书附加属性
		foreach ( $itemConf[ItemDef::ITEM_ATTR_NAME_BOOK_ATTRS] as $attrId => $value )
		{
			if ( !in_array($attrId, array_keys(PropertyKey::$MAP_CONF)) )
			{
				trigger_error("book:$itemId has invalid attrId:$attrId!\n");
				return false;
			}
		}
		//摘除所需的物存在吗
		foreach ( $itemConf[ItemDef::ITEM_ATTR_NAME_BOOK_ERASURE_ITEMS] as $erasureItemId => $itemNum )
		{
			if ( !isset($allItem[$erasureItemId]) )
			{
				trigger_error("book:$itemId erasure item id:$erasureItemId is not exist!\n");
				return false;
			}
		}
		//validate book item skill buff group
		foreach ( $itemConf[ItemDef::ITEM_ATTR_NAME_BOOK_SKILL_BUFF_GROUP] as $buffId)
		{
			if ( !isset(btstore_get()->SKILL_ITEM[$buffId]) )
			{
				trigger_error("book:$itemId skill buff group id:$buffId is not exist!\n");
				return false;
			}
		}
		//check level up table id
		$expTableId = $itemConf[ItemDef::ITEM_ATTR_NAME_BOOK_LEVEL_TABLE];
		if ($itemConf[ItemDef::ITEM_ATTR_NAME_BOOK_CAN_LEVEL_UP] 
		&& !isset(btstore_get()->EXP_TBL[$expTableId]) )
		{
			trigger_error("book:$itemId exp table id:$expTableId is not exist!\n");
			return false;
		}		
	}
	return true;
}

function testGiftItem($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_GIFT )
		{
			trigger_error("gift:$itemId type is wrong!\n");
			return false;
		}
		//check acq item
		$items = $itemConf[ItemDef::ITEM_ATTR_NAME_USE_ACQ][ItemDef::ITEM_ATTR_NAME_USE_ACQ_ITEMS];
		foreach ( $items as $acqItemId => $itemNum )
		{
			if ( !isset($allItem[$acqItemId]) )
			{
				trigger_error("gift:$itemId acq item id:$acqItemId is not exist!\n");
				return false;
			}
		}
		//check req item
		if (!empty($itemConf[ItemDef::ITEM_ATTR_NAME_USE_REQ][ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS])) 
		{
			$item = key($itemConf[ItemDef::ITEM_ATTR_NAME_USE_REQ][ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS]);
			if ( !isset($allItem[$item]) )
			{
				trigger_error("gift:$itemId req item id:$item is not exist!\n");
				return false;
			}
		}	
	}
	return true;
}

function testRandGiftItem($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_RANDGIFT )
		{
			trigger_error("randgift:$itemId type is wrong!\n");
			return false;
		}
		//check drop id
		$dropId = $itemConf[ItemDef::ITEM_ATTR_NAME_USE_ACQ][ItemDef::ITEM_ATTR_NAME_USE_ACQ_DROP];
		if ( !isset(btstore_get()->DROP_ITEM[$dropId]) )
		{
			trigger_error("randgift:$itemId drop id:$dropId is not exist!\n");
			return false;
		}
		//check req item
		if (!empty($itemConf[ItemDef::ITEM_ATTR_NAME_USE_REQ][ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS]))
		{
			$item = key($itemConf[ItemDef::ITEM_ATTR_NAME_USE_REQ][ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS]);
			if ( !isset($allItem[$item]) )
			{
				trigger_error("randgift:$itemId req item id:$item is not exist!\n");
				return false;
			}
		}
	}
	return true;
}

function testGoodWillItem($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_GOODWILL )
		{
			trigger_error("goodwill:$itemId type is wrong!\n");
			return false;
		}
	}
	return true;
}

function testFragmentItem($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_FRAGMENT )
		{
			trigger_error("fragment:$itemId type is wrong!\n");
			return false;
		}
		//check acq item
		$item = key($itemConf[ItemDef::ITEM_ATTR_NAME_USE_ACQ][ItemDef::ITEM_ATTR_NAME_USE_ACQ_ITEMS]);
		if ( !empty($item) && !isset($allItem[$item]) )
		{
			trigger_error("fragment:$itemId acq item id:$item is not exist!\n");
			return false;
		}
	}
	return true;
}

function testFeedItem($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_FEED )
		{
			trigger_error("feed:$itemId type is wrong!\n");
			return false;
		}
	}
	return true;
}

function testNormalItem($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_NORMAL )
		{
			trigger_error("normal:$itemId type is wrong!\n");
			return false;
		}
	}
	return true;
}

function testTreasureItem($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_TREASURE )
		{
			trigger_error("treasure:$itemId type is wrong!\n");
			return false;
		}
	}
	return true;
}

function testTreasFragItem($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_TREASFRAG )
		{
			trigger_error("treasFrag:$itemId type is wrong!\n");
			return false;
		}
		$treasId = $itemConf[ItemDef::ITEM_ATTR_NAME_TREASFRAG_FORM];
		$treasFrags = $allItem[$treasId][TreasureDef::ITEM_ATTR_NAME_TREASURE_FRAGMENTS];
		if (!in_array($treasId, $treasFrags)) 
		{
			trigger_error("treasFrag:$itemId is not in treas:$treasId frags arry!\n");
			return false;
		}
	}
	return true;
}

function testFightSoulItem($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_FIGHTSOUL )
		{
			trigger_error("fightsoul:$itemId type is wrong!\n");
			return false;
		}
	}
	return true;
}

function testDressItem($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_DRESS )
		{
			trigger_error("dress:$itemId type is wrong!\n");
			return false;
		}
	}
	return true;
}

function testPetFragItem($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_PETFRAG )
		{
			trigger_error("petFrag:$itemId type is wrong!\n");
			return false;
		}
	}
	return true;
}

function testGodWeapon($arrItem, $allItem)
{
    foreach ( $arrItem as $itemId => $itemConf )
    {
        //check type
        if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_GODWEAPON )
        {
            trigger_error("GodWeapon:$itemId type is wrong!\n");
            return false;
        }
    }
}

function testGodWeaponFrag($arrItem, $allItem)
{
    foreach ( $arrItem as $itemId => $itemConf )
    {
        //check type
        if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_GODWEAPONFRAG )
        {
            trigger_error("petFrag:$itemId type is wrong!\n");
            return false;
        }
    }
}

function testRune($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_RUNE )
		{
			trigger_error("rune:$itemId type is wrong!\n");
			return false;
		}
	}
}

function testRuneFrag($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_RUNEFRAG )
		{
			trigger_error("runeFrag:$itemId type is wrong!\n");
			return false;
		}
	}
}

function testPocket($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_POCKET )
		{
			trigger_error("pocket:$itemId type is wrong!\n");
			return false;
		}
	}
}

function testTally($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_TALLY )
		{
			trigger_error("tally:$itemId type is wrong!\n");
			return false;
		}
	}
}

function testTallyFrag($arrItem, $allItem)
{
	foreach ( $arrItem as $itemId => $itemConf )
	{
		//check type
		if ( $itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] != ItemDef::ITEM_TYPE_TALLYFRAG )
		{
			trigger_error("tallyFrag:$itemId type is wrong!\n");
			return false;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */