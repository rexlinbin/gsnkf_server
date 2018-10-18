<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Battle.class.php 104164 2014-04-25 15:46:42Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/battle/Battle.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2014-04-25 15:46:42 +0000 (Fri, 25 Apr 2014) $
 * @version $Revision: 104164 $
 * @brief
 *
 **/

class Battle implements IBattle
{

	/* (non-PHPdoc)
	 * @see IBattle::test()
	*/
	public function test($arrHero1, $arrHero2, $arrExtra = array())
	{
		if (! FrameworkConfig::DEBUG)
		{
			Logger::fatal ( "invalid call for battle.test, not debug mode" );
			throw new Exception ( 'close' );
		}
	
		if (empty ( $arrExtra ))
		{
			$arrExtra = array ();
		}
	
		if (! isset ( $arrExtra ['teamName1'] ))
		{
			$arrExtra ['teamName1'] = '队伍1';
		}
	
		if (! isset ( $arrExtra ['teamName2'] ))
		{
			$arrExtra ['teamName2'] = '队伍2';
		}
	
		if (! isset ( $arrExtra ['teamLevel1'] ))
		{
			$arrExtra ['teamLevel1'] = 100;
		}
	
		if (! isset ( $arrExtra ['teamLevel2'] ))
		{
			$arrExtra ['teamLevel2'] = 100;
		}
		if(empty($arrHero1))
		{
			$arrHero1 = array(
					array(
							'hid' => 10000010,
							'position' => 1,
							'htid' => 10006,
							),					
					);
		}
		if(empty($arrHero2))
		{
			$arrHero2 = array(
					array(
							'hid' => 10000020,
							'position' => 1,
							'htid' => 10006,
							),					
					);
		}
	
	
		$arrDefValue = array ();
		$arrKeyType = array_merge(BattleDef::$ARR_BATTLE_KEY, BattleDef::$ARR_CLIENT_KEY);
		foreach($arrKeyType as $key => $type)
		{
			if( $type == 'int' )
			{
				$arrDefValue[$key] = 0;
			}
		}
		$arrDefValue[PropertyKey::LEVEL] = 1;
		$arrDefValue[PropertyKey::ATTACK_SKILL] = 206;
		$arrDefValue[PropertyKey::MAX_HP] = 1000;
		$arrDefValue[PropertyKey::HIT] = 1000;
		foreach ( $arrHero1 as & $hero )
		{
			foreach ( $arrDefValue as $key => $value)
			{
				if( !isset($hero[$key]) )
				{
					$hero[$key] = $arrDefValue[$key];
				}
			}
		}
		unset ( $hero );
	
		foreach ( $arrHero2 as &$hero )
		{
			foreach ( $arrDefValue as $key => $value)
			{
				if( !isset($hero[$key]) )
				{
					$hero[$key] = $arrDefValue[$key];
				}
			}
		}
		unset ( $hero );
	
		$arrFormation1 = array (
					'uid' => 1, 
					'name' => $arrExtra ['teamName1'],
					'level' => intval ( $arrExtra ['teamLevel1'] ), 					
					'arrHero' => $arrHero1,
					'isPlayer' => true 
				);
		$arrFormation2 = array (
					'uid' => 2, 
					'name' => 'team2',
					'level' => intval ( $arrExtra ['teamLevel2'] ),					
					'arrHero' => $arrHero2,
					'isPlayer' => true );
		
		$arrRet = $this->doHero ( 
					$arrFormation1, 
					$arrFormation2, 0, 
					NULL,
					NULL, $arrExtra );
		
		Logger::debug ( "server:%s", $arrRet ['server'] );
		return $arrRet ['client'];
	}
	
	

	/* (non-PHPdoc)
	 * @see IBattle::pvp()  TODO接口应该删掉，使用EnBattle的接口
	 */
	public function doHero($arrFormation1, $arrFormation2, $type = 0, $callback = null,
			$arrEndCondition = null, $arrExtra = null, $db = null)
	{

		return BattleLogic::doHero ( $arrFormation1, $arrFormation2, $type, $callback,
				$arrEndCondition, $arrExtra, $db );
	}

	/* (non-PHPdoc)
	 * @see IBattle::getRecord()
	 */
	public function getRecord($brid)
	{

		return BattleLogic::getRecord ( $brid );
	}
	
	public function getRecordRaw($brid)
	{
		return BattleLogic::getRecordRaw($brid);
	}
	
	public function getMultiRecord($brid)
	{
		return BattleLogic::getRecord($brid);
	}

	public function setPermanent($brid)
	{

		return BattleLogic::setPermanent ( $brid );
	}


	public function getRecordUrl($brid)
	{

		$group = RPCContext::getInstance ()->getFramework ()->getGroup ();
		$arrRequest = array ('serverid' => $group, 'bid' => BabelCrypt::encryptNumber ( $brid ) );
		$query = http_build_query ( $arrRequest );
		return BattleConf::URL_PREFIX . $query;
	}

	/* (non-PHPdoc)
	 * @see IBattle::getRecordForWeb()
	 */
	public function getRecordForWeb($brid)
	{

		return BattleLogic::getRecordForWeb ( $brid );
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
