<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PrepareMissionMassData.php 215701 2015-12-15 06:32:38Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/mission/test/PrepareMissionMassData.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-15 06:32:38 +0000 (Tue, 15 Dec 2015) $
 * @version $Revision: 215701 $
 * @brief 
 *  
 **/
class PrepareMissionMassData extends BaseScript
{
	protected function executeScript($arrOption)
	{
		
		if( !FrameworkConfig::DEBUG )
		{
			throw new FakeException( 'not in debug mode' );
		}
		if( count($arrOption) < 2 )
		{
			printf("invalid parm.  btscript gamexxx $0 prepareNum teamId\n");
			return;
		}
		
		$prepareNum = $arrOption[0];
		if( $prepareNum > 1000 )
		{
			throw new FakeException( 'too much' );
		}
		$teamId = $arrOption[1];
		
		$offset = 0;
		$limit = 100;
		
		while (true)
		{
			$arrUid = self::selectUsers( 75 , $offset, $limit);
			foreach ( $arrUid as $index => $uidInfo )
			{
 				RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID,  $uidInfo['uid'] );
				MissionLogic::getMissionUserInfo( $uidInfo['uid'] );
				MissionLogic::doMission(false, $uidInfo['uid'], MissionType::ACOPY, 1);
				MissionUserObj::releaseInstance( $uidInfo['uid'] );
				RPCContext::getInstance()->resetSession(); 
				
				MissionDao::updatInnerUserInfo(
				$uidInfo['uid'], 
				array(
				MissionDBField::FAME =>$uidInfo['uid'], 
				MissionDBField::UPDATE_TIME => Util::getTime() ,
				)
				);
				
				MissionDao::updatCrossUserInfo(
				Util::getServerIdOfConnection(),
				 $uidInfo['pid'], 
				 $teamId,  
				 array( 
				 MissionDBField::CROSS_FAME => $uidInfo['uid'], 
				 MissionDBField::CROSS_UPDATE_TIME => Util::getTime(),
				 ) 
				);
				
				/* $data = new CData();
				$data->useDb( MissionUtil::getCrossDbName() )
				->update( MissionUtil::getCrossUserTableName($teamId) )
				->set()
				->where(array( MissionDBField::CROSS_SERVERID ,'>',0) )
				->where(array( MissionDBField::CROSS_PID ,'=',$uidInfo['pid']) )
				->query(); */
			}
			$offset += count( $arrUid );
			if( count( $arrUid ) < $limit )
			{
				break;
			}
			//就拉整百吧 不管了
			if( $offset >= $prepareNum )
			{
				break;
			}
			
		}
		
 		
		
		echo "done \n";
		
	}
	
	
	function selectUsers( $level, $offset, $limit )
	{
		$data = new CData();
		$ret = $data->select( array( 'uid','pid' ) )-> from( 't_user' )-> where(array('level','>=', $level))
		->limit( $offset , $limit)->query(); 

		if( empty( $ret ) )
		{
			return array();
		}
		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */