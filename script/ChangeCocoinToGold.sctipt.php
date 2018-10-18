<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChangeCocoinToGold.sctipt.php 218488 2015-12-29 10:10:06Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/ChangeCocoinToGold.sctipt.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-29 10:10:06 +0000 (Tue, 29 Dec 2015) $
 * @version $Revision: 218488 $
 * @brief 
 *  
 **/
class ChangeCocoinToGold extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption) 
	{
		if( count( $arrOption ) != 5 )
		{
			var_dump('use btscript gameXXX ChangCocoinToGold op[check|run] uid serverId pid backcocoinnum');
			return;
		}
		
		$op = $arrOption[0];
		$uid = intval( $arrOption[1] );
		$serverId = intval( $arrOption[2] );
		$pid = intval( $arrOption[3] );
		$subCocoinNum = intval( $arrOption[4] );
		
		if( $subCocoinNum < 0 )
		{
			throw new InterException( 'subCocoin negtive:%s', $subCocoinNum );
		}
		
		$userField = array('uid','pid');
		if ( defined ( 'GameConf::MERGE_SERVER_OPEN_DATE' ) )
		{
			$userField = array('uid','pid','server_id');
		}
		$ret = EnUser::getArrUser(array($uid), $userField);
		if( empty( $ret ) )
		{
			throw new InterException( 'no such user' );
		}
		$realUid = $ret[$uid]['uid'];
		$realPid = $ret[$uid]['pid'];
		if( defined ( 'GameConf::MERGE_SERVER_OPEN_DATE' ) )
		{
			$realServerId = $ret[$uid]['server_id'];
		}
		else
		{
			$realServerId = Util::getServerId();
		}
		if( $realServerId != $serverId )
		{
			throw new InterException( 'serverId:%s in Rpc is diff from serverId from args:%s', $realServerId, $serverId );
		}
		
		if( $op == 'run' )
		{
			Util::kickOffUser($uid);
		}
		$userObj = EnUser::getUserObj($uid);
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$nowCocoin = $crossUser->getCocoinNum();
		if( $nowCocoin < $subCocoinNum )
		{
			Logger::info('now cocoin:%s less than wnat to:%s',$nowCocoin, $subCocoinNum);
			$subCocoinNum = $nowCocoin;
		}
		
		$cocoinNumPerGold = CountryWarConfig::exchangeRatio();//一金币兑换几个国战币
		$backGoldNum = floor($subCocoinNum/$cocoinNumPerGold);
		$subCocoinNum = $backGoldNum*$cocoinNumPerGold;
		Logger::info('user has cocoin:%s, sub cocoin:%s, back gold:%s', $nowCocoin, $subCocoinNum, $backGoldNum);
		
		if( $op == 'run' && $subCocoinNum > 0 )
		{
			Util::kickOffUser($uid);//再踢一次
			if( !$crossUser->subCocoin( $subCocoinNum, 55013 ))
			{
				throw new InterException( 'subCocoin fail:%s',$subCocoinNum );
			}
			if( !$userObj->addGold( $backGoldNum, 55014 ) )
			{
				throw new InterException( 'addGold fail:%s',$backGoldNum );
			}
			$crossUser->update();
			Logger::info('subCocoin success');
			$userObj->update();
			Logger::info('addGold success');
		}
		echo "done";
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */