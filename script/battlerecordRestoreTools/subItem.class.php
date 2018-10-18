<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: subItem.class.php 106098 2014-05-05 09:06:18Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/battlerecordRestoreTools/subItem.class.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2014-05-05 09:06:18 +0000 (Mon, 05 May 2014) $
 * @version $Revision: 106098 $
 * @brief
 *
 **/

/**
 *
 * 通过邮件发送指定物品
 *
 * @example btscript subItem.class.php 51846 娜芙亚琪娜 10001
 *
 * @author pkujhd
 *
 */
class subItem extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript ($arrOption)
	{

		//检查参数是否合法
		if ( count($arrOption) != 4 )
		{
			echo "subitem need two arguments: uid uname item_template_id item_num\n";
			return;
		}

		$uid = intval($arrOption[0]);
		$uname = strval($arrOption[1]);
		$itemTplId = intval($arrOption[2]);
		$itemNum = intval($arrOption[3]);

		//得到用户信息
		RPCContext::getInstance()->setSession('global.uid', $uid);
		$userObj = EnUser::getUserObj($uid);

		
		//如果用户名和uid不匹配,则退出
		if( $userObj->getUname() != $uname )
		{
			$this->info("uid:%d, uname:%s not match %s", $uid, $userObj->getUname(), $uname);
			return;
		}

		Util::kickOffUser($uid);

		
		$bag = BagManager::getInstance()->getBag();
		
		if ( $bag->deleteItembyTemplateID($itemTplId, $itemNum) == false )
		{
			echo "delete item_template_id: $itemTplId, item_num:$itemNum failed!";
			return;
		}
		
		$bag->update();

		echo "subItem::subitem to user:$uid uname:$uname item template_id: $itemTplId item_num:$itemNum!\n";
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */