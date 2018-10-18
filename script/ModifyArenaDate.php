<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ModifyArenaDate.php 19763 2012-05-03 11:03:30Z HongyuLan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/test/ModifyArenaDate.php $
 * @author $Author: HongyuLan $(lanhongyu@babeltime.com)
 * @date $Date: 2012-05-03 19:03:30 +0800 (星期四, 03 五月 2012) $
 * @version $Revision: 19763 $
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

class ModifyArenaDate extends BaseScript
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
		echo '设置日期为' . $beginDate . "\n";
		$data = new CData();
		$data->update('t_arena_lucky')->set(array('begin_date'=>$beginDate))
			->where('begin_date','=', $curDate)->query();
		ArenaRound::setCurRoundDate();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */