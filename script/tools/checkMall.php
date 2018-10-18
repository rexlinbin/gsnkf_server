<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: checkMall.php 78898 2013-12-05 07:57:54Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/checkMall.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-12-05 07:57:54 +0000 (Thu, 05 Dec 2013) $
 * @version $Revision: 78898 $
 * @brief 
 *  
 **/
class CheckMall extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript ($arrOption)
	{
		if (empty($arrOption[0]) || $arrOption[0] == 'help' || (count($arrOption) < 3))
		{
			$this->usage();
			return;
		}

		$option = $arrOption[0];
		if ($option == 'check')
		{
			$fix = false;
		}
		elseif ($option == 'fix')
		{
			$fix = true;
		}
		else
		{
			echo "invalid operation!\n";
			$this->usage();
			return;
		}
		
		$mallType = intval($arrOption[1]);
		if (!in_array($mallType, MallDef::$MALL_VALID_TYPES)) 
		{
			echo "invalid malltype!\n";
			$this->usage();
			return;
		}

		$uid = intval($arrOption[2]);
		Util::kickOffUser($uid);
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$user = EnUser::getUserObj($uid);
		if(empty($user))
		{
			echo "empty user!\n";
			$this->usage();
			return;
		}
		
		$invalid = array();
		$conf = btstore_get()->GOODS;
		$ret = MallDao::select($uid, $mallType);
		foreach ($ret['all'] as $goodsId => $goodsInfo)
		{
			if (!isset($conf[$goodsId]))
			{
				$invalid[] = $goodsId;
			}
		}
		
		echo "error data in this malltype:\n";
		print_r($invalid);
		
		if ($fix)
		{
			foreach ($invalid as $goodsId)
			{
				unset($ret['all'][$goodsId]);
			}
			$arrField = array(
				MallDef::USER_ID => $uid,
				MallDef::MALL_TYPE => $mallType,
				MallDef::VA_MALL => $ret,
			);
			MallDao::insertOrUpdate($arrField);
		}
		
		echo "ok\n";
	}
		
	private function usage()
	{
		echo "usage: btscript game001 checkMall.php check|fix malltype uid\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */