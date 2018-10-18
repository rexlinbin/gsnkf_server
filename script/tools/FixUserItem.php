<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FixUserItem.php 102045 2014-04-21 02:56:37Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/FixUserItem.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-04-21 02:56:37 +0000 (Mon, 21 Apr 2014) $
 * @version $Revision: 102045 $
 * @brief 
 *  
 **/
class FixUserItem extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$usage = "usage::btscript game001 fixUserItem.php check|fix uid itemId\n";
		
		$fix = false;
		if( isset( $arrOption[0] ) &&  $arrOption[0] == 'fix' )
		{
			$fix = true;
		}
		
		$uid = intval($arrOption[1]);
		$itemId = intval($arrOption[2]);
		$user = EnUser::getUserObj($uid);
		if (empty($user)) 
		{
			printf("user:%d is not exist.", $uid);
			return ;
		}
		$itemInfo = self::getItem($itemId);
		if (empty($itemInfo)) 
		{
			printf("item:%d is not exist.", $itemId);
			return ;
		}
		if (empty($itemInfo[ItemDef::ITEM_SQL_ITEM_DELTIME]))
		{
			printf("item:%d is not deleted\n", $itemId);
			return ;
		}
		
		Util::kickOffUser($uid);
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		
		$itemTplId = $itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID];
		$itemType = ItemManager::getInstance()->getItemType($itemTplId);
		$bag = BagManager::getInstance()->getBag($uid);
		if ($bag->isItemExist($itemId) || EnUser::isCurUserOwnItem($itemId, $itemType)) 
		{
			printf("user:%d has item:%d.", $uid, $itemId);
			return ;
		}
		
		printf("user:%d do not has item:%d.", $uid, $itemId);
		
		if ($fix) 
		{
			if (!empty($itemInfo[ItemDef::ITEM_SQL_ITEM_DELTIME])) 
			{
				self::fixItem($itemId);
				Logger::info('fix item:%d', $itemId);
			}
			$bag->addItem($itemId, true);
			$bag->update();
			Logger::info('user:%d add item:%d', $uid, $itemId);
		}
		printf("ok\n");
	}
	
	public static function getItem($itemId)
	{
		$select = array(
				ItemDef::ITEM_SQL_ITEM_ID,
				ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID,
				ItemDef::ITEM_SQL_ITEM_NUM,
				ItemDef::ITEM_SQL_ITEM_TIME,
				ItemDef::ITEM_SQL_ITEM_TEXT,
				ItemDef::ITEM_SQL_ITEM_DELTIME,
		);
		$data = new CData();
		$ret = $data->select($select)
			 		->from(ItemDef::ITEM_TABLE_NAME)
			 		->where(array(ItemDef::ITEM_SQL_ITEM_ID, '=', $itemId))
					->query();
		if (!empty($ret[0]))
		{
			return $ret[0];
		}
		return array();
	}
	
	public static function fixItem($itemId)
	{
		$data = new CData();
		$data->update(ItemDef::ITEM_TABLE_NAME)
			 ->set(array(ItemDef::ITEM_SQL_ITEM_DELTIME => 0))
			 ->where(array(ItemDef::ITEM_SQL_ITEM_ID, '=', $itemId))
			 ->query();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */