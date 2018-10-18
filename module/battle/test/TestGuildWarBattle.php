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


class TestGuildWarBattle extends BaseScript
{

	protected function executeScript($arrOption)
	{
		$this->guildBattle(5, 820, 5, 821);
	}
	
	public function guildBattle($serverId1, $guidlId1, $serverId2, $guildId2)
	{
		$infoCur = $this->getGuildBattleInfo($serverId1, $guidlId1);
		$infoObj = $this->getGuildBattleInfo($serverId2, $guildId2);
		
		
		if ($infoCur['members'][0]['fightForce'] >= $infoObj['members'][0]['fightForce'])
		{
			$attackBattleFmt = self::changeIdsForBattleFmt($infoCur, 1);
			$defendBattleFmt = self::changeIdsForBattleFmt($infoObj, 2);
		}
		else
		{
			$attackBattleFmt = self::changeIdsForBattleFmt($infoObj, 1);
			$defendBattleFmt = self::changeIdsForBattleFmt($infoCur, 2);
		}
		

	
		$atkRet = EnBattle::doMultiHero($attackBattleFmt,
				$defendBattleFmt,
				GuildWarConf::MAX_ARENA_COUNT,
				GuildWarConfObj::getInstance(GuildWarField::CROSS)->getDefaultMaxWinTimes(),
				array(
						'arrEndCondition' => 0,
						'mainBgid' => GuildWarConf::BACK_GROUND_M,
						'subBgid' => GuildWarConf::BACK_GROUND_S,
						'mainMusicId' => GuildWarConf::MUSIC_ID_M,
						'subMusicId' => GuildWarConf::MUSIC_ID_S,
						'mainCallback' => NULL,
						'subCallback' => NULL,
						'mainType' => BattleType::GUILD_WAR,
						'subType' => BattleType::GUILD_WAR,
						'isGuildWar' => TRUE,
						'db' => GuildWarUtil::getCrossDbName(),
						'stopWhenBattleFailed' => TRUE,
				));

		$brid = $atkRet['server']['brid'];
		
		$arrBattleRecord = EnBattle::getArrRecord(array($brid));
		$ret = BattleManager::genBattleProcess($arrBattleRecord);
		$info = $ret[$brid];
		printf("atk:%d_%d, def:%d_%d, result:%d\n\n", 
					$info['atk_server_id'], $info['atk_guild_id'],
					$info['def_server_id'], $info['def_guild_id'],
					$info['result']);
		
		foreach ($info['arrProcess'] as $value )
		{
			printf("%s vs %s result:%d\n", 
							$info['userList'][$value['atk_uid']]['name'],
							$info['userList'][$value['def_uid']]['name'],
							$value['result']);
		}
	}
	
	public function getGuildBattleInfo($serverId, $guildId)
	{
		$arrMember = EnGuild::getMemberList($guildId, array(GuildDef::USER_ID, GuildDef::MEMBER_TYPE));
		$candidatesCount = 20;
		$presidentUid = 0;
		$count = 0;
		$arrCandidates = array();
		foreach ($arrMember as $aUid => $aMember)
		{
			if ($aMember[GuildDef::MEMBER_TYPE] == GuildMemberType::PRESIDENT)
			{
				$presidentUid = $aUid;
			}
		
			if (++$count <= $candidatesCount)
			{
				$arrCandidates[] = $aUid;
			}
			else if ($presidentUid != 0)
			{
				if (!empty($arrCandidates) && !in_array($presidentUid, $arrCandidates))
				{
					$arrCandidates[count($arrCandidates) - 1] = $presidentUid;
				}
				break;
			}
			else
			{
				continue;
			}
		}
		
		$serverMgr = ServerInfoManager::getInstance();
		$dbName = $serverMgr->getDbNameByServerId($serverId);
		
		RPCContext::getInstance()->getFramework()->setDb($dbName);
		$guildObj = GuildObj::getInstance($guildId);
		
		
		$formations = array();
		$formations['guild_id'] = $guildId;
		$formations['server_id'] = $serverId;
		$formations['name'] = $guildObj->getGuildName();
		$formations['level'] = $guildObj->getGuildLevel();
		$formations['members'] = array();
		

		foreach ($arrCandidates as $uid)
		{
			$userObj = EnUser::getUserObj($uid);
			$userBattleFormation = $userObj->getBattleFormation();
			$userBattleFormation['maxWin'] = 2;
			$formations['members'][] = $userBattleFormation;
		}
		
		
		return $formations;
	}
	
	public static function changeIdsForBattleFmt($battleFmt, $offset)
	{
		foreach ($battleFmt['members'] as $key => $value)
		{
			$battleFmt['members'][$key]['uid'] = $value['uid'] * 10 + $offset;
			foreach ($value['arrHero'] as $pos => $hero)
			{
				$battleFmt['members'][$key]['arrHero'][$pos]['hid'] = $hero['hid'] * 10 + $offset;
			}
		}
	
		if (isset($battleFmt['mapUidInitWin']))
		{
			foreach ($battleFmt['mapUidInitWin'] as $aUid => $initWin)
			{
				$battleFmt['mapUidInitWin'][$aUid * 10 + $offset] = $initWin;
				unset($battleFmt['mapUidInitWin'][$aUid]);
			}
		}
	
		return $battleFmt;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */