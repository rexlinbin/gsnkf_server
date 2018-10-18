<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BattleLogic.class.php 250333 2016-07-07 03:45:10Z BaoguoMeng $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/battle/BattleLogic.class.php $
 * @author $Author: BaoguoMeng $(hoping@babeltime.com)
 * @date $Date: 2016-07-07 03:45:10 +0000 (Thu, 07 Jul 2016) $
 * @version $Revision: 250333 $
 * @brief
 *
 **/

class BattleLogic
{

	public static function doHero($arrFormation1, $arrFormation2, $type, $callback, $arrEndCondition,
			$arrExtra, $db = null)
	{

		//如果是跨服战，篡改一下hid
		if((isset($arrExtra['isLRD']) && $arrExtra['isLRD'])
			|| (isset($arrExtra['isMotifyId']) && $arrExtra['isMotifyId']))
		{
			foreach($arrFormation1['arrHero'] as &$hero)
			{
				$hero['hid'] = $hero['hid']*10 + 1;
			}
			unset($hero);
			foreach($arrFormation2['arrHero'] as &$hero)
			{
				$hero['hid'] = $hero['hid']*10 + 2;
			}
			unset($hero);
		}
		$arrKey = array ('bgid', 'musicId', 'type' );
		foreach ( $arrKey as $key )
		{
			if (! isset ( $arrExtra [$key] ))
			{
				$arrExtra [$key] = 0;
			}
		}
		$arrHero1 = $arrFormation1 ['arrHero'];
		$arrHero2 = $arrFormation2 ['arrHero'];
		$arrHero1 = BattleUtil::unsetEmpty ( $arrHero1 );
		$arrHero2 = BattleUtil::unsetEmpty ( $arrHero2 );
		$arrFormation1 ['arrHero'] = $arrHero1;
		$arrFormation2 ['arrHero'] = $arrHero2;
		
		if( !empty( $arrFormation1['arrCar'] ) )
		{
			$carIdOffset = BattleDef::$CAR_ID_OFFSET[1];
			foreach ($arrFormation1['arrCar'] as $index => $aCarInfo)
			{
				$arrFormation1['arrCar'][$index]['cid'] = ++$carIdOffset;
			}
		}
		if( !empty( $arrFormation2['arrCar'] ) )
		{
			$carIdOffset = BattleDef::$CAR_ID_OFFSET[2];
			foreach ($arrFormation2['arrCar'] as $index => $aCarInfo)
			{
				$arrFormation2['arrCar'][$index]['cid'] = ++$carIdOffset;
			}
		}

		if (empty ( $arrEndCondition ))
		{
			$arrEndCondition = array ('dummy' => true );
		}
		
		// battle需要的extra，是否是pvp
		$arrBattleExtra = array();
		if (isset($arrFormation1['isPlayer']) 
			&& $arrFormation1['isPlayer']
			&& isset($arrFormation2['isPlayer']) 
			&& $arrFormation2['isPlayer']) 
		{
			$arrBattleExtra['isPvp'] = 1;
		}
		if (isset($arrExtra['isPvp'])) 
		{
			$arrBattleExtra['isPvp'] = $arrExtra['isPvp'];
		}
		
		// battle需要的extra，伤害随着回合相应增加
		if (isset($arrExtra['damageIncreConf'])) 
		{
			$arrBattleExtra['damageIncreConf'] = $arrExtra['damageIncreConf'];
		}
		
		$needRecord = true;
		if ( isset($arrExtra['needRecord']) )
		{
			$needRecord = $arrExtra['needRecord'];
			unset($arrExtra['needRecord']);
		}
		
		// 防止解析成数组
		if (empty($arrBattleExtra)) 
		{
			$arrBattleExtra = array('dummy' => true);
		}
		
		$proxy = new PHPProxy ( 'battle' );
		$arrRet = $proxy->doHero ( BattleUtil::prepareBattleFormation ( $arrFormation1 ),
				BattleUtil::prepareBattleFormation ( $arrFormation2 ), $type, $arrEndCondition, $arrBattleExtra );
		
		
		Logger::debug('The dohero use db is %s.', $db);
		if($needRecord)
		{
			$brid = IdGenerator::nextId ( "brid", $db );
		}
		else 
		{
			$brid = 0;
		}
		$arrRet ['server'] ['uid1'] = $arrFormation1 ['uid'];
		$arrRet ['server'] ['uid2'] = $arrFormation2 ['uid'];
		$arrRet ['server'] ['brid'] = $brid;

		$arrClient = $arrRet ['client'];
		if (! empty ( $callback ))
		{

			$arrReward = call_user_func ( $callback, $arrRet ["server"] );
			$arrClient ['reward'] = $arrReward;
			$arrRet ['server'] ['reward'] = $arrReward;
		}

		if (isset ( $arrExtra ['dlgId'] ))
		{
			$arrClient ['dlgId'] = $arrExtra ['dlgId'];
			$arrClient ['dlgRound'] = $arrExtra ['dlgRound'];
		}
		$arrClient ['bgId'] = $arrExtra ['bgid'];
		$arrClient ['type'] = $arrExtra ['type'];
		$arrClient ['musicId'] = $arrExtra ['musicId'];
		$arrClient ['brid'] = $brid;
		$arrClient ['url_brid'] = BabelCrypt::encryptNumber ( $brid );
		if($db != null)
		{
			$prefix = '';
			if( isset($arrExtra['isLRD']) && $arrExtra['isLRD'] )
			{
				$prefix = RecordType::LRD_PREFIX;
			}
			if( isset($arrExtra['isWAN']) && $arrExtra['isWAN'] )
			{
				$prefix = RecordType::WAN_PREFIX;
			}
			if( !empty($prefix) )
			{
				$arrClient ['brid'] = $prefix.$arrClient ['brid'];
				$arrClient ['url_brid'] = $prefix.$arrClient ['url_brid'];
					
				Logger::debug('The dohero url brid is %s, brid is %s',
								$arrClient ['url_brid'], $arrClient ['brid']);
			}
		}
		$arrClient ['team1'] = BattleUtil::prepareClientFormation ( $arrFormation1,
				$arrRet ['server'] ['team1'] );
		$arrClient ['team2'] = BattleUtil::prepareClientFormation ( $arrFormation2,
				$arrRet ['server'] ['team2'] );
		
		$arrClient['firstAttack'] = EnBattle::isLeftFirstAtk($type) ? 1 : 2;
		
		$compressed = true;
		Logger::trace('arrClient %s.',$arrClient);
		$data = Util::amfEncode ( $arrClient, $compressed, 0,
				BattleDef::BATTLE_RECORD_ENCODE_FLAGS );
		if($needRecord)
		{
			BattleDao::addRecord ( $brid, $data, $db );
		}
		$arrRet ['client'] = base64_encode ( $data );

		return $arrRet;
	}

	public static function doMultiHero($arrFormationList1, $arrFormationList2, $arenaCount, $maxWin, $arrExtra)
	{

		$arrKey = array ('mainBgid', 'subBgid', 'mainMusicId', 'subMusicId', 'mainCallback',
				'subCallback', 'arrEndCondition', 'mainType', 'subType');
		foreach ( $arrKey as $key )
		{
			if (! isset ( $arrExtra [$key] ))
			{
				$arrExtra [$key] = 0;
			}
		}

		$manager = new BattleManager ( $arrFormationList1, $arrFormationList2, $arenaCount, $maxWin, $arrExtra );
		return $manager->start ();
	}

	public static function getRecord($brid)
	{
		if(self::isKfzBattle($brid, RecordType::LRD_PREFIX))
		{
			$brid = substr($brid, strlen(RecordType::LRD_PREFIX));
			$data = BattleDao::getKfzRecord ( $brid, LordwarUtil::getCrossDbName() );
		}
		else if(self::isKfzBattle($brid, RecordType::GDW_PREFIX))
		{
			$brid = substr($brid, strlen(RecordType::GDW_PREFIX));
			$data = BattleDao::getKfzRecord ( $brid, GuildWarUtil::getCrossDbName() );
		}
		else if(self::isKfzBattle($brid, RecordType::WAN_PREFIX))
		{
			$brid = substr($brid, strlen(RecordType::WAN_PREFIX));
			$data = BattleDao::getKfzRecord ( $brid, WorldArenaUtil::getCrossDbName() );
		}
		else if(self::isKfzBattle($brid, RecordType::WCN_PREFIX))
		{
			$brid = substr($brid, strlen(RecordType::WCN_PREFIX));
			$data = BattleDao::getKfzRecord ( $brid, WorldCarnivalUtil::getCrossDbName() );
		}
		else
		{
			$data = BattleDao::getRecord ( $brid );
		}

		return base64_encode ( $data );
	}
	
	public static function getRecordRaw($brid)
	{
		$arrRet = self::getArrRecordRaw( array($brid) );
		return $arrRet[$brid];
	}
	
	public static function getArrRecordRaw($arrBrid)
	{
		$realDb = null;
		
		$mapBrid = array();
		foreach($arrBrid as $brid)
		{
			if(self::isKfzBattle($brid, RecordType::LRD_PREFIX))
			{
				$intBrid = substr($brid, strlen(RecordType::LRD_PREFIX));
				$mapBrid[$brid] = $intBrid;
				$db = LordwarUtil::getCrossDbName();
			}
			else if(self::isKfzBattle($brid, RecordType::GDW_PREFIX))
			{
				$intBrid = substr($brid, strlen(RecordType::GDW_PREFIX));
				$mapBrid[$brid] = $intBrid;
				$db = GuildWarUtil::getCrossDbName();
			}
			else
			{
				if( ! is_numeric($brid) )
				{
					throw new InterException('invalid brid:%s', $brid);
				}
				$mapBrid[$brid] = intval($brid);
				$db = '';
			}
			if ( $realDb !== null && $realDb != $db )
			{
				throw new InterException('cant batch get different type battle record');
			}
			$realDb = $db;
		}
		
		$arrRet = BattleDao::getArrRecord( array_values($mapBrid), array('record_data', 'brid'), $realDb);
		$arrRet = Util::arrayIndex($arrRet, 'brid');
		$arrRecord = array();
		foreach ($mapBrid as $strBrid => $intBrid )
		{
			if ( !isset( $arrRet[$intBrid] ) )
			{
				throw new InterException('not found brid:%s', $strBrid);
			}
			$arrRecord[$strBrid] = $arrRet[$intBrid];
		}
		
		return $arrRecord;
	}

	public static function getRecordForWeb($brid)
	{
		if(self::isKfzBattle($brid, RecordType::LRD_PREFIX))
		{
			$brid = substr($brid, strlen(RecordType::LRD_PREFIX));
			$data = BattleDao::getKfzRecord ( $brid, LordwarUtil::getCrossDbName() );
			return base64_encode ( $data );
		}
		
		$arrRecord = BattleDao::getFullRecord ( $brid );
		if ($arrRecord ['record_type'] != RecordType::PERM)
		{
			self::setPermanent ( $brid );
		}
		return base64_encode ( $arrRecord ['record_data'] );
	}

	public static function setPermanent($brid)
	{

		$arrBody = array ('record_type' => RecordType::PERM );
		return BattleDao::updateRecord ( $brid, $arrBody );
	}

	public static function addRecord($brid, $data)
	{

		BattleDao::addRecord ( $brid, $data );
		return;
	}
	

	private static function isKfzBattle($brid, $recordType)
	{
		$kfzStr = '';
		if(strlen($brid) >= 4)
		{
			$kfzStr = substr($brid, 0, strlen($recordType));
		}
		return $kfzStr == $recordType ? true : false;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
