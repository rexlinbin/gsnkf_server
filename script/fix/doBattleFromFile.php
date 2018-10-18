<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: doBattleFromFile.php 257598 2016-08-22 07:26:53Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/fix/doBattleFromFile.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-22 07:26:53 +0000 (Mon, 22 Aug 2016) $
 * @version $Revision: 257598 $
 * @brief 
 *  
 **/
 
class doBattleFromFile extends BaseScript
{
	protected function executeScript($arrOption)
	{
		if (count($arrOption) < 2)
		{
			printf("usage btscript game001 doBattleFromFile.php battleFmtFile1 battleFmtFile2\n");
			return;
		}

		$battleFmtStr1 = file_get_contents($arrOption[0]);
		$battleFmtStr2 = file_get_contents($arrOption[1]);
		
		$battleFmt1 = unserialize($battleFmtStr1);
		$battleFmt2 = unserialize($battleFmtStr2);
		
		//var_dump($battleFmt1);
		//var_dump($battleFmt2);
		
		$arrRet = EnBattle::doHero($battleFmt1, $battleFmt2);
		
		var_dump($arrRet);

		printf("done\n");
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */