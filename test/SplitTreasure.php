<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DelUserItem.php 176159 2015-06-02 07:11:03Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/test/DelUserItem.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-06-02 15:11:03 +0800 (星期二, 02 六月 2015) $
 * @version $Revision: 176159 $
 * @brief 
 *  
 **/
class SplitTreasure extends BaseScript
{
	
	public function getItemInfo($uid)
	{

		$userObj = EnUser::getUserObj($uid);
		$uname = $userObj->getUname();
		
		$bag = BagManager::getInstance()->getBag($uid);
		
		$itemType = ItemDef::ITEM_TYPE_TREASURE;
		
		$arrItemIdInBag = $bag->getItemIdsByItemType($itemType);
		
		$arrItemIdInFormation = array();
		
		$arrHid = EnFormation::getArrHidInFormation($uid);
		foreach ($arrHid as $hid)
		{
			$heroObj = $userObj->getHeroManager()->getHeroObj($hid);
			$arrItemIdInFormation = array_merge($arrItemIdInFormation, $heroObj->getAllEquipId() );
		}
		
		$arrItemByType = array();
		$allItemId = array_merge($arrItemIdInFormation, $arrItemIdInBag);
		ItemManager::getInstance()->getItems( $allItemId  );
		
		$msg = sprintf("uid:%d, uname:%s:\n", $uid, $uname);
		foreach( $allItemId as $itemId )
		{
			if($itemId == 0)
			{
				continue;
			}
			$itemObj = ItemManager::getInstance()->getItem($itemId);
			if( empty($itemObj) )
			{
				self::fatal('cant find itemId:%d', $itemId);
				return;
			}
			if( $itemObj->getItemType() == $itemType )
			{
				$arrItemByType[] = $itemId;
		
				if( in_array( $itemId, $arrItemIdInBag ) )
				{
					$msg .= sprintf("\t[in  bag]");
				}
				else if( in_array( $itemId, $arrItemIdInFormation ) )
				{
					$msg .= sprintf("\t[in hero]");
				}
				else
				{
					Logger::fatal('cant be true');
					return;
				}
				$msg .= sprintf("itemId:%d, tplId:%d",
						$itemId, $itemObj->getItemTemplateID() );
		
				$msg .= sprintf(" exp:%d, level:%d, evolve:%d\n", $itemObj->getExp(), $itemObj->getLevel(), $itemObj->getEvolve() );
			}
		}
		printf("%s\n", $msg);
		Logger::info("%s", $msg);
		
		
	}
	
	protected function executeScript($arrOption)
	{
		if ( count($arrOption) < 1 )
		{
			printf("param: uid [itemId num ]");
			return;
		}
		
		$uid = intval( $arrOption[0] );
		
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		
		$userObj = EnUser::getUserObj($uid);
		$uname = $userObj->getUname();
		
		$bag = BagManager::getInstance()->getBag($uid);
		
		if ( count( $arrOption ) < 3 )
		{
			$this->getItemInfo($uid);
			return;
		}
		Util::kickOffUser($uid);
		
		$srcItemId = intval( $arrOption[1] );
		$num = intval( $arrOption[2] );
		
		if( $num < 2 )
		{
			printf("invalid num:%d\n", $num);
			return;
		}
		$newItemNum = $num - 1;
		
		$srcItemObj = ItemManager::getInstance()->getItem($srcItemId);
		
		$treasureType = $srcItemObj->getType();
		if( !in_array( $treasureType,  array( TreasureDef::TREASURE_TYPE_BOOK, TreasureDef::TREASURE_TYPE_HORSE ) ) )
		{
			printf("invalid type:%d\n", $treasureType);
			return;
		}
		$itemTplId = $srcItemObj->getItemTemplateID();

		$itemConfExp = $srcItemObj->getBaseValue();
		$exp = $srcItemObj->getExp();
		
		if ( $itemConfExp * $newItemNum > $exp )
		{
			printf("num to big. num:%d, exp:%d, confExp:%d\n", $num, $exp, $itemConfExp);
			return;
		}
		
		$eachExp = intval( ( $exp - $itemConfExp * $newItemNum) / $num );
		
		$leftExp = $exp - ( $itemConfExp + $eachExp) * $newItemNum;
		
		$msg = sprintf("set item:%d leftExp:%d, orgExp:%d, eachExp:%d, newItemNum:%d\n", 
				$srcItemId, $leftExp, $exp, $eachExp, $newItemNum);
		
		$srcItemObj->setExp($leftExp);
		
		$arrNewItemObj = array();
		for( $i = 0; $i < $newItemNum; $i++  )
		{
			$ret = ItemManager::getInstance()->addItem($itemTplId);
			if( empty($ret[0]) )
			{
				printf("add item failed\n");
				return;
			} 
			$id = $ret[0];
			$itemObj = ItemManager::getInstance()->getItem($id);
			$itemObj->setExp($eachExp);
			$bag->addItem($id, true);
			$msg .= sprintf("new item:%d  exp:%d\n", $id, $eachExp);
		}
		
		printf("%s\n", $msg);
		$ret = trim(fgets(STDIN));
		if( $ret != 'y' )
		{
			printf("ignore\n");
			return;
		}
		
		Logger::info('%s', $msg);
		
		$bag->update();
		
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */