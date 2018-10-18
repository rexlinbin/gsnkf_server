<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: subUserSilver.class.php 106098 2014-05-05 09:06:18Z HaidongJia $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/battlerecordRestoreTools/subUserSilver.class.php $
 * @author $Author: HaidongJia $(hoping@babeltime.com)
 * @date $Date: 2014-05-05 09:06:18 +0000 (Mon, 05 May 2014) $
 * @version $Revision: 106098 $
 * @brief 
 *  
 **/

class subUserSilver extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption)
	{
		if (count($arrOption) != 3)
		{
			echo "args err! need args:uid uname subsilvernum!\n";
			return;
		}	
		
		$uid = intval($arrOption[0]);
		if ( $uid <= 0 )
		{
			echo "invalid uid!\n";
			return;
		}
		
		$uname = strval($arrOption[1]);
		
		$belly = intval($arrOption[2]);
		
		if ( $belly <= 0 )
		{
			echo "invalid belly number! must belly number > 0!\n";
			return;
		}
		
		$user = EnUser::getUserObj($uid);
		
		if ( $uname != $user->getUname() )
		{
			echo "user name is invalid!\n";
			return;
		}
		
		if ( $user->subSilver($belly) == FALSE )
		{
			echo "belly number is bigger than now!\n";
			return;
		}
		
		$user->update();

		echo "subUserBelly uid:$uid uname:$uname belly:$belly done!\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */