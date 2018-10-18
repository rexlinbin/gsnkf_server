<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BattleTest.php 156295 2015-01-31 09:18:07Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/battle/test/BattleTest.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2015-01-31 09:18:07 +0000 (Sat, 31 Jan 2015) $
 * @version $Revision: 156295 $
 * @brief 
 *  
 **/



class BattleTest extends PHPUnit_Framework_TestCase
{

	protected static $pid1 = 0;
	protected static $pid2 = 0;
	protected static $uid1 = 0;
	protected static $uid2 = 0;

	public static function setUpBeforeClass()
	{
		$data = new CData();
		$arrRet = $data->select(array('pid', 'uid') )->from('t_user')
					->where( 'pid', '>', UserConf::PID_MAX_RETAIN )
					->limit(0, 2)->query();
		if( count($arrRet) > 1 )
		{
			self::$pid1 = $arrRet[0]['pid'];
			self::$uid1 = $arrRet[0]['uid'];
			
			self::$pid2 = $arrRet[1]['pid'];
			self::$uid2 = $arrRet[1]['uid'];
		}
		else 
		{
			self::$pid1 = time();
			$str = strval(self::$pid1);
			$uname = substr($str, strlen($str) - UserConf::MAX_USER_NAME_LEN);				
			$ret = UserLogic::createUser(self::$pid1, 1, $uname);			
			if($ret['ret'] != 'ok')
			{
				echo "create use failed\n";
				exit();
			}
			self::$uid1 = $ret['uid'];
			
			
			self::$pid2 = time()+1;
			$str = strval(self::$pid2);
			$uname = substr($str, strlen($str) - UserConf::MAX_USER_NAME_LEN);		
			$ret = UserLogic::createUser(self::$pid2, 1, $uname);			
			if($ret['ret'] != 'ok')
			{
				echo "create use failed\n";
				exit();
			}
			self::$uid2 = $ret['uid'];
		}
		
		$arrUid = array(self::$uid1, self::$uid2);
		foreach($arrUid as $uid)
		{
			RPCContext::getInstance()->resetSession();
			RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
			$userObj = EnUser::getUserObj($uid);
			$heroMgr = $userObj->getHeroManager();
			if( count( $heroMgr->getAllHeroObjInSquad() )  < 2 )
			{
				$hid = $heroMgr->addNewHero(20003);
				$formationObj = EnFormation::getFormationObj($uid);
				
				$index = count($formationObj->getFormation());
				$formationObj->addHero($hid, $index);
			}
			$userObj->update();
		}
		
	}


	protected function setUp()
	{		
	}

	protected function tearDown()
	{
	}
	
	/*
	public function testPvp()
	{
		$userObj1 = EnUser::getUserObj(self::$uid1);	
		$userObj2 = EnUser::getUserObj(self::$uid2);
		
		$battleInfo1 = $userObj1->getBattleFormation();
		$battleInfo2 = $userObj2->getBattleFormation();
		
		$battle = new Battle();
		
		$ret = $battle->doHero($battleInfo1, $battleInfo2);
		var_dump($ret);
	}
	*/
	
	
	public function testMultiBattle()
	{
		$userObj1 = EnUser::getUserObj(self::$uid1);
		$battleInfo1 = $userObj1->getBattleFormation();


		$arrFormationList1 = array(
				'server_id' => 1,
				'guild_id' => 10,
				'name' => 'team1',
				'level' => 1,
				'members' => array(),
		);
		$arrFormationList2 = array(
				'server_id' => 2,
				'guild_id' => 20,
				'name' => 'team2',
				'level' => 2,
				'members' => array(),
		);
		
		$mapFightForce = array(
			array(1000,5000,1000),
			array(3000,1000,2000)
		);
		
		for($i = 0; $i < 3; $i++)
		{
			$arrInfo = $battleInfo1;
			$arrInfo['uid'] = 100 + $i;
			$arrInfo['name'] = sprintf('user_%d', $arrInfo['uid']);
			$arrInfo['maxWin'] = 4;
			$arrFormationList1['members'][] = self::modifyBattleData($arrInfo, $mapFightForce[0][$i], array(10,1) );
			
			$arrInfo = $battleInfo1;
			$arrInfo['uid'] = 200 + $i;
			$arrInfo['name'] = sprintf('user_%d', $arrInfo['uid']);
			$arrInfo['maxWin'] = 4;
			$arrFormationList2['members'][] = self::modifyBattleData($arrInfo, $mapFightForce[1][$i], array(10,2) );
		}

		
		
		$arrExtra = array(
			'arrNeedResult' => array(
						),
			'isGuildWar' => true,
			'db' => GuildWarUtil::getCrossDbName(),
			'mapUidInitWin' => array(101=>1,100=>2 ),
		);
		
		
		$ret = BattleLogic::doMultiHero($arrFormationList1, $arrFormationList2, 1, 3, $arrExtra );
		
		
		var_dump($ret);
		
		
		$ret = EnBattle::getArrRecord(array($ret['server']['brid']));
		$ret = BattleManager::genBattleProcess( $ret  );
		var_dump($ret);
		
	}
	
	
	protected static function modifyBattleData($battleFormation, $value, $modifyHid = array() )
	{

		$battleFormation['fight_force'] = $value;
		foreach ($battleFormation['arrHero'] as & $hero)
		{
			if ( !empty($modifyHid) )
			{
				$hero[PropertyKey::HID] = $hero[PropertyKey::HID] * $modifyHid[0] + $modifyHid[1];
			}
			
			$hero[PropertyKey::MAX_HP] = 10000;
			$hero[PropertyKey::CURR_HP] = 10000;
			
			$hero[PropertyKey::PHYSICAL_ATTACK_BASE] = $value;
			$hero[PropertyKey::PHYSICAL_ATTACK_ADDITION] = 0;
			$hero[PropertyKey::PHYSICAL_DEFEND_BASE] = 0;
			$hero[PropertyKey::PHYSICAL_DEFEND_ADDITION] = 0;
			$hero[PropertyKey::PHYSICAL_ATTACK_RATIO] = 10000;
			$hero[PropertyKey::PHYSICAL_DAMAGE_IGNORE_RATIO] = 0;
			
			$hero[PropertyKey::MAGIC_ATTACK_BASE] = $value;
			$hero[PropertyKey::MAGIC_ATTACK_ADDITION] = 0;
			$hero[PropertyKey::MAGIC_DEFEND_BASE] = 0;
			$hero[PropertyKey::MAGIC_DEFEND_ADDITION] = 0;
			$hero[PropertyKey::MAGIC_ATTACK_RATIO] = 10000;
			$hero[PropertyKey::MAGIC_DAMAGE_IGNORE_RATIO] = 0;
			
			$hero[PropertyKey::GENERAL_ATTACK_BASE] = 0;
			$hero[PropertyKey::GENERAL_ATTACK_ADDITION] = 0;
		}
	
		return $battleFormation;
	}

}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */