<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SetGoodsBuyNum.php 137021 2014-10-22 04:02:59Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/SetGoodsBuyNum.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-10-22 04:02:59 +0000 (Wed, 22 Oct 2014) $
 * @version $Revision: 137021 $
 * @brief 
 *  
 **/
class SetGoodsBuyNum extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$usage = "usage::btscript game001 SetGoodsBuyNum.php uid type id num\n";

		$uid = intval($arrOption[0]);
		$type = intval($arrOption[1]);
		$id = intval($arrOption[2]);
		$num = intval($arrOption[3]);
		$goodsInfo = MallDao::select($uid, $type);
		if (empty($goodsInfo)) 
		{
			return ;
		}
		$goodsInfo[MallDef::ALL][$id][MallDef::NUM] = $num;
		$arrField = array(
				MallDef::USER_ID => $uid,
				MallDef::MALL_TYPE => $type,
				MallDef::VA_MALL => $goodsInfo,
		);
		MallDao::insertOrUpdate($arrField);
		echo "done\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */