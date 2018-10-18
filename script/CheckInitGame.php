<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(wuqilin@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/

class CheckInitGame extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript ($arrOption)
	{
		//user
		$data = new CData();

		$arrRet = $data->select(array('pid', 'uid'))
					->from('t_user')
					->where('uid', 'BETWEEN', array(SPECIAL_UID::MIN_ROBOT_UID, SPECIAL_UID::MAX_ROBOT_UID) )
					->query();
		
		if ( count($arrRet) != ( SPECIAL_UID::MAX_ROBOT_UID - SPECIAL_UID::MIN_ROBOT_UID + 1) )
		{
			Logger::warning('init user wrong %s', $arrRet);
			exit(1);
		}
		
		//mineral
		$ret = $data->selectCount()
					->from('t_mineral')
					->where('domain_id', '>=', 0 )
					->query();
		$mineralNum = 0;
		foreach( btstore_get()->MINERAL as $domainId => $domainInfo)
		{
			$mineralNum += count($domainInfo['pits']);
		}
		if ( $mineralNum != $ret[0]['count'] )
		{
			Logger::warning('init mineral wrong %s', $ret);
			exit(1);
		}
		
		//arena lucky
		$arrRet = $data->select( array('begin_date') )
					->from('t_arena_lucky')
					->where('begin_date', '>=', 0 )
					->query();
		if ( empty($arrRet) )
		{
			Logger::warning('init t_arena_lucky wrong');
			exit(1);
		}
		
		//arena_history
		$ret = $data->selectCount()
					->from('t_arena_history')
					->where('uid', '>=', 0 )
					->query();
		if (  $ret[0]['count'] <= 0 )
		{
			Logger::warning('init arena_history wrong %s', $ret);
			exit(1);
		}
		
		
		//boss
		$ret = $data->selectCount()
					->from('t_boss')
					->where('boss_id', '>=', 0 )
					->query();
		$conf = btstore_get()->BOSS;
		if ( count($conf) != $ret[0]['count'] )
		{
			Logger::warning('init boss wrong %s', $ret);
			exit(1);
		}
		
		//ChargeDart road
		$ret = $data->selectCount()
		            ->from('t_charge_dart_road')
		            ->where('stage_id', '>=', 0)
		            ->query();
		$conf = btstore_get()->CHARGEDART_RULE;
		$count = $conf[ChargeDartDef::CSV_ALL_PAGE_NUM]*$conf[ChargeDartDef::CSV_ALL_ROAD_NUM]*ChargeDartDef::DEFAULT_MAX_STAGE;
		if($count != $ret[0]['count'])
		{
		    Logger::warning('init t_charge_dart wrong');
		    exit(1);
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */