<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChangeItem.php 135713 2014-10-11 03:20:12Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/ChangeItem.php $
 * @author $Author: MingTian $(wuqilin@babeltime.com)
 * @date $Date: 2014-10-11 03:20:12 +0000 (Sat, 11 Oct 2014) $
 * @version $Revision: 135713 $
 * @brief 
 *  
 **/




class ChangeItem extends BaseScript
{

	protected function executeScript ($arrOption)
	{
		$uid = intval($arrOption[0]);
		$srcItemId = intval($arrOption[1]);
		$desItemTpl = intval($arrOption[2]);
		$check = intval($arrOption[3]);

		$ret = UserDao::getUserByUid($uid, array('pid', 'uid', 'uname') );
		if(empty($ret))
		{
			printf("not found uid:%d\n", $uid);
			Logger::warning('not found uid:%d', $uid);
			return ;
		}
		$uname = $ret['uname'];
		printf("uid:%d, uname:%s\n", $uid, $uname);
		Util::kickOffUser($uid);
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$srcItem = ItemManager::getInstance()->getItem($srcItemId);
		if ($srcItem == NULL)
		{
			printf("not found itemId:%d\n", $srcItemId);
			Logger::warning('not found itemId:%d', $srcItemId);
			return ;
		}
		$srcItemTpl = $srcItem->getItemTemplateID();
		if ($srcItemTpl == $desItemTpl)
		{
			printf("no change\n");
			Logger::warning('no change');
			return ;
		}
		$srcItemType = ItemManager::getInstance()->getItemType($srcItemTpl);
		$desItemType = ItemManager::getInstance()->getItemType($desItemTpl);
		if ($srcItemType != $desItemType) 
		{
			printf("item type is not same\n");
			Logger::warning('item type is not same');
			return ;
		}
		$srcItemText = $srcItem->getItemText();
		$bag = BagManager::getInstance()->getBag($uid);
		if ($bag->deleteItem($srcItemId) == false)
		{
			printf("not found itemId:%d in bag\n", $srcItemId);
			Logger::warning('not found itemId:%d in bag', $srcItemId);
			return ;
		}
		$arrItemId = ItemManager::getInstance()->addItem($desItemTpl, 1);
		$desItemId = $arrItemId[0];
		$desItem = ItemManager::getInstance()->getItem($desItemId);
		$desItem->setItemText($srcItemText);
		$bag->addItem($desItemId, true);
		if ($check == 'fix')
		{
			$bag->update();
		}
		echo "ok\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
