<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AddTreasureFrag.php 137425 2014-10-24 03:36:46Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/AddTreasureFrag.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-10-24 03:36:46 +0000 (Fri, 24 Oct 2014) $
 * @version $Revision: 137425 $
 * @brief 
 *  
 **/
class AddTreasureFrag extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption) 
	{
		if( count( $arrOption ) != 4 )
		{
			echo "invalid args usage: check|add uid treasureFragId Num";
			return;
		}
		
		$operation = $arrOption[0];
		$uid = $arrOption[1];
		$fragId = $arrOption[2];
		$num = $arrOption[3];
		
		$userObj = EnUser::getUserObj( $uid );
		$tid = TreasFragItem::getTreasureId( $fragId );
		if( $num > 5 )
		{
			echo "nima, too much";
			return;
		}
			
		$inst = FragseizeObj::getInstance( $uid );
		$info = $inst->getFragsByTid($tid);
		var_dump( $info );
		
		$inst->addFrags( array( $fragId => $num ) );
		$info = $inst->getFragsByTid($tid);
		var_dump( $info );
		
		
		if( $operation == 'check' )
		{
			return;
		}
		if( $operation == 'add' )
		{
			$inst->updateFrags();
			Logger::info('add treasurefragId: %d num:%d for uid: %d', $fragId, $num, $uid);
		}
		else 
		{
			echo "invalid operation code";
			return;
		}
		
	}

	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */