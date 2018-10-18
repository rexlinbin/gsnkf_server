<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: LordwarScrTest.php 128726 2014-08-22 11:43:56Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/lordwar/test/LordwarScrTest.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-08-22 11:43:56 +0000 (Fri, 22 Aug 2014) $
 * @version $Revision: 128726 $
 * @brief 
 *  
 **/
class LordwarScrTest extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption) 
	{
		if(count( $arrOption) < 1 )
		{
			echo 'lack para : method args';
			return;
		}
		
		if(  $arrOption[0] == 'push' )
		{
			if( $arrOption[1] != LordwarField::INNER && $arrOption[1] != LordwarField::CROSS )
			{
				echo "invalid field";
				return;
			}
			
			LordwarLogic::push($arrOption[1], LordwarPush::NOW_STATUS);
			
		}
		
		if( $arrOption[0] == 'btlview' )
		{
			
			$view = LordwarLogic::getPromotionBtl($arrOption[1], $arrOption[2], $arrOption[3], $arrOption[4], $arrOption[5], $arrOption[6]);
			var_dump( $view );
			
		}
		
		if( $arrOption[0] ==  'rewardSupport' )
		{
			LordwarLogic::reward(LordwarReward::SUPPORT);
		}
		
		if( $arrOption[0] == 'history' )
		{
			$ret = LordwarLogic::getPromotionHistory(LordwarRound::INNER_2TO1);
			var_dump( $ret );
		}
		
		if( $arrOption[0] == 'worship' )
		{
			$pid = 40000 + rand(0,9999);
			$utid = 1;
			$uname = 't' . $pid;
			$ret = UserLogic::createUser($pid, $utid, $uname);
			$users = UserLogic::getUsers( $pid );
			$uid = $users[0]['uid'];
			RPCContext::getInstance()->setSession('global.uid', $uid);
			
			$user = EnUser::getUserObj( $uid );
			$console = new Console();
			$console->level( 65 );
			$console->silver( 9999999 );
			$user->update();

			LordwarLogic::worship(0, 0);
			$ret = LordwarLogic::getLordInfo($uid);
			var_dump( $ret );
		}
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */