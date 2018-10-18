<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: checkBag.php 66052 2013-09-24 07:18:38Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/checkBag.php $
 * @author $Author: MingTian $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-09-24 07:18:38 +0000 (Tue, 24 Sep 2013) $
 * @version $Revision: 66052 $
 * @brief 
 *  
 **/
class CheckBag extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        $usage = "usage::btscript game001 CheckBag.php check|fix uid\n";

        if(empty($arrOption) || $arrOption[0] == 'help' || (count($arrOption) < 2))
        {
            echo "invalid parameter!\n".$usage;
            return;
        }
        
        $operation = $arrOption[0];
        if($operation != 'fix' && $operation != 'check')
        {
        	echo "invalid operation!\n".$usage;
        	return;
        }
        
        $uid = intval($arrOption[1]);
        if(empty($uid))
        {
            echo "invalid uid!\n".$usage;
            return;
        }
        
        $fix = false;
        if($operation == 'fix')
        {
            $fix = true;
        }
        
        Util::kickOffUser($uid);
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        $user = EnUser::getUserObj($uid);
        if(empty($user))
        {
            echo "empty user!\n".$usage;
            return;
        }
        
        $select = array(BagDef::SQL_ITEM_ID, BagDef::SQL_GID);
		$where = array(BagDef::SQL_UID, '=', $uid);
		$return = BagDAO::selectBag($select, $where);
		$invalid = array();
		foreach ($return as $value)
		{
		    $gid = intval($value[BagDef::SQL_GID]);
		    $itemId = intval($value[BagDef::SQL_ITEM_ID]);
		    if(empty($itemId))
		    {
		        continue;
		    }
		    $itemInfo = ItemStore::getItem($itemId);
		    if(empty($itemInfo))
		    {
		        $invalid[$gid] = $itemId;
		        continue;
		    }
		    if(!isset(btstore_get()->ITEMS[$itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]]))
		    {
		        $invalid[$gid] = $itemId;
		    }
		}
		echo "error data in bag:\n";
		print_r($invalid);
		
		if($fix == TRUE)
		{
		    foreach($invalid as $gid => $itemId)
		    {
		        $values = array(
		                BagDef::SQL_ITEM_ID => ItemDef::ITEM_ID_NO_ITEM,
		                BagDef::SQL_UID => $uid,
		                BagDef::SQL_GID => $gid
		        );
		        BagDAO::insertOrupdateBag($values);
		        echo "fix gid: " . $gid . "\n";
		    }
		}
		
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */