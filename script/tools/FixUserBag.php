<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FixUserBag.php 93870 2014-03-18 05:50:48Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/FixUserBag.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-03-18 05:50:48 +0000 (Tue, 18 Mar 2014) $
 * @version $Revision: 93870 $
 * @brief 
 *  
 **/
class FixUserBag extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$usage = "usage::btscript game001 fixUserBag.php check|fix uid\n";
		
		$fix = false;
		if( isset( $arrOption[0] ) &&  $arrOption[0] == 'fix' )
		{
			$fix = true;
		}
		
		$uid = intval($arrOption[1]);
		Util::kickOffUser($uid);
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$bag = BagManager::getInstance()->getBag($uid);
		$bagName = BagDef::BAG_ARM;
		$ret = $bag->bagInfo();
		$armBag = $ret[$bagName];
		
		$arrItemId = array();
		$errItemId = array();
		foreach ($armBag as $gid => $itemInfo)
		{
			$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
			if (in_array($itemId, $arrItemId)) 
			{
				$bag->removeItem($itemId);
				$errItemId[$gid] = $itemId;
			}
			else 
			{
				$arrItemId[$gid] = $itemId;
			}
		}
		
		echo "err data in arm bag:\n";
		print_r($errItemId);
		
		if ($fix)
		{
			$bag->update();
		}
		echo "ok\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */