<?php

/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: CloseUser.php 66447 2013-09-26 03:25:32Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/CloseUser.php $
 * @author $Author: wuqilin $(lanhongyu@babeltime.com)
 * @date $Date: 2013-09-26 03:25:32 +0000 (Thu, 26 Sep 2013) $
 * @version $Revision: 66447 $
 * @brief
 *
 **/

/**
 * Enter description here ...
 * @author idyll
 *
 */

class CloseUser extends BaseScript
{

	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption)
	{

		$uid = 0;
		if (isset ( $arrOption [0] ))
		{
			$uid = intval ( $arrOption [0] );
		}
		else
		{
			exit ( "usage: uid \n" );
		}

		$proxy = new ServerProxy ();
		$proxy->closeUser ( $uid );
		sleep ( 1 );

		$this->delConnection ( $uid );
		sleep ( 1 );

		echo "ok\n";
	}

	private function delConnection($uid)
	{

		$proxy = new PHPProxy ( 'lcserver' );
		$proxy->setDummyReturn ( true );
		$proxy->delConnection ( $uid );
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */