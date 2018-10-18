<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: FixedTreasureItem.class.php 106098 2014-05-05 09:06:18Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/battlerecordRestoreTools/FixedTreasureItem.class.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2014-05-05 09:06:18 +0000 (Mon, 05 May 2014) $
 * @version $Revision: 106098 $
 * @brief
 *
 **/

class FixedTreasureItem extends BaseScript
{

	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption)
	{

		//检查参数是否合法
		if ( count($arrOption) != 6 )
		{
			echo "FixedTreasureItem need six arguments: uid uname item_id item level exp evolve\n";
			return;
		}


		$uid = intval($arrOption[0]);
		$uname = intval($arrOption[1]);
		$item_id = intval($arrOption[2]);
		$level = intval($arrOption[3]);
		$exp = intval($arrOption[4]);
		$evolve = intval($arrOption[5]);
		

		RPCContext::getInstance()->setSession('global.uid', $uid);
		
		$item = ItemManager::getInstance()->getItem($item_id);
		if ( $item === NULL )
		{
			echo "item_id :$item_id not exist!";
			return;
		}

		//检查宝物是否属于该用户
		if (EnUser::isCurUserOwnItem($item_id, ItemDef::ITEM_TYPE_TREASURE) == FALSE)
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $item_id, $uid);
		}
		
		Util::kickOffUser($uid);
		
		Logger::INFO("itemInfo:item_id:%d, item_info:%s", $item_id, $item->getItemText());
		$item->setExp($exp);
		$item->setLevel($level);
		$item->setEvolve($evolve);

		Logger::INFO("itemInfo:item_id:%d, item_info:%s", $item_id, $item->getItemText());
		
		ItemManager::getInstance()->update();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */