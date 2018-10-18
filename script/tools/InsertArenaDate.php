<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: InsertArenaDate.php 197761 2015-09-10 05:23:01Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/InsertArenaDate.php $
 * @author $Author: MingTian $(lanhongyu@babeltime.com)
 * @date $Date: 2015-09-10 05:23:01 +0000 (Thu, 10 Sep 2015) $
 * @version $Revision: 197761 $
 * @brief 
 *  
 **/

/**
 * 修改当前的发奖日期
 * 用法： btscript ModifyArenaDate.php 日期
 * 如 btscript ModifyArenaDate.php 20120503
 * 
 * Enter description here ...
 * @author idyll
 *
 */

class InsertArenaDate extends BaseScript
{

	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption)
	{
		if (empty($arrOption))
		{
			exit("没有参数\n");
		}
		
		$beginDate = intval($arrOption[0]);
		$curDate = ArenaRound::getCurRoundDate();
		if ($curDate < $beginDate) 
		{
			$arrField = array('begin_date', 'active_rate', 'va_lucky');
			$arrRet = ArenaLuckyDao::get(array($curDate,$beginDate), $arrField);
			$arrRet = Util::arrayIndex($arrRet, 'begin_date');
			if (!isset($arrRet[$beginDate])) 
			{
				echo '插入日期为' . $beginDate . "\n";
				$arrRet[$curDate]['begin_date'] = $beginDate;
				ArenaLuckyDao::insert($arrRet[$curDate]);
				ArenaRound::setCurRoundDate();
			}
		}
		echo "ok\n";
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */