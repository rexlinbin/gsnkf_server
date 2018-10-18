<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(tianming@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/
class ArenaGenerate extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$data = new CData();

		$forceFix = 0;
		if(isset($arrOption[0]))
		{
			$forceFix = intval($arrOption[0]);
		}
		
		$arrRet = $data->selectCount()->from('t_arena')->where('uid','>', 0)->query();
		if ( !empty($arrRet[0]['count']) )
		{
			printf("arena not empty\n");
			Logger::info('arena not empty');
			return;
		}
		
		$arrRet = $data->selectCount()->from('t_user')->where('uid','>=', 20000)->where('level','>=', 14)->query();
		$userNum = $arrRet[0]['count'];
		$arenaNum = ArenaConf::NPC_NUM + $userNum;

		$pos = 0;
		$offset = 0;
		$limit = CData::MAX_FETCH_SIZE;
		while ($forceFix)
		{
			printf("offset:%d, limit:%d\n", $offset, $limit);
			Logger::info('get users. offset:%d, limit:%d', $offset, $limit);
				
			$arrRet = $data->select(array('uid', 'fight_force'))
						   ->from('t_user')
						   ->where('uid','>=',20000)
						   ->where('level','>=', 14)
						   ->orderBy('fight_force', false)
						   ->orderBy('uid', true)
						   ->limit($offset, $limit)
						   ->query();
			
			foreach ($arrRet as $value)
			{
				$pos++;
				$uid = $value['uid'];
				self::initArena($uid, $pos, $arenaNum);
			}
				
			if (count($arrRet) < $limit)
			{
				break;
			}
			$offset += $limit;
		}
		
		//加NPC
		if ($forceFix) 
		{
			printf("add %d NPC", ArenaConf::NPC_NUM);
			for ($i = 1; $i <= ArenaConf::NPC_NUM; $i++)
			{
				$pos++;
				$uid = SPECIAL_UID::MIN_ARENA_NPC_UID + $i - 1;
				self::initArena($uid, $pos, $arenaNum, true);
			}
		}
		
		Logger::info('arena generate done');
		printf("done \n");
	}

	public function initArena($uid, $pos, $arenaNum, $isNPC = FALSE)
	{
		$num = 0;
		$opptPos = array();
		if (!$isNPC) 
		{
			$num = btstore_get()->ARENA_PROPERTIES['challenge_free_num'];
			$opptPos = self::getOpponentPosition($pos, $arenaNum);
		}
		
		$arrField = array(
				'uid' => $uid,
				'position' => $pos,
				'challenge_num' => $num,
				'challenge_time' => 0,
				'cur_suc' => 0,
				'max_suc' => 0,
				'min_position' => $pos,
				'upgrade_continue' => 0,
				'va_opponents' => $opptPos,
				'reward_time' => 0,
				'va_reward' => array()
		);
		if (!$isNPC) 
		{
			$arrField['va_reward']['his'] = self::initPosHis($pos);
		}
		ArenaDao::insert($arrField);
	}
	
	public function initPosHis($pos)
	{
		$his = array();
		$now = Util::getTime();
		$today = intval(strftime("%Y%m%d", $now));
		$rewardTime = strtotime($today . " " . ArenaDateConf::LOCK_START_TIME);
		$shift = $now > $rewardTime ? 1 : 0;
		for ($k = 1, $i = 1 + $shift - ArenaConf::POS_HIS; $k <= ArenaConf::POS_HIS; $k++, $i++)
		{
			$sign = $i > 0 ? "+" : "-";
			$offset =  $sign . abs($i) . " day";
			$date = intval(strftime("%Y%m%d", strtotime($offset, $now)));
			$status = $k == ArenaConf::POS_HIS ? ArenaDef::HAVE : ArenaDef::NONE;
			$his[$date] = array($pos, $status);
		}
		return $his;
	}
	
	public function getOpponentPosition($pos, $count)
	{
		$beforNum = ArenaConf::OPPONENT_BEFOR;
		$afterNum = ArenaConf::OPPONENT_AFTER;
		$opptNum = $beforNum + $afterNum;
	
		$arrRet = array();
	
		//小于100的取前8后2
		if ($pos <= 100)
		{
			$min = $pos - $beforNum;
			$max = $pos + $afterNum;
		}
		else //大于100的从前后10%里面取
		{
			$min = intval($pos * 0.9);
			$max = intval($pos * 1.1);
		}
	
		//不小于1
		if ($min <= 0)
		{
			$min = 1;
		}
		//不超过总数
		if ($max > $count)
		{
			$max = $count;
		}
	
		//前段区间小于需要的数量
		if ($pos <= $beforNum)
		{
			$beforNum = $pos - 1;
			$afterNum = $opptNum - $beforNum;
		}
		//后段区间小于需要的数量
		if ($count - $pos < $afterNum)
		{
			//新用户是最后一个，$pos大于$count.
			$afterNum = $count - $pos;
			if ($afterNum < 0)
			{
				$afterNum = 0;
			}
			$beforNum = $opptNum - $afterNum;
		}
	
		// 如果小于100
		if ($pos <= 100)
		{
			if ($beforNum != 0)
			{
				for ($i = $pos - $beforNum; $i <= $pos - 1; $i++)
				{
					$beforArr[] = $i;
				}
				$arrRet = $beforArr;
			}
			if ($afterNum != 0)
			{
				for ($i = $pos + 1; $i <= $pos + $afterNum; $i++)
				{
					$afterArr[] = $i;
				}
				$arrRet = array_merge($arrRet, $afterArr);
			}
		}
		else
		{
			if ($beforNum != 0)
			{
				$beforArr = self::getRandSeq($min, $pos - 1, $beforNum);
				$arrRet = $beforArr;
			}
			if ($afterNum != 0)
			{
				$afterArr = self::getRandSeq($pos + 1, $max, $afterNum);
				$arrRet = array_merge($arrRet, $afterArr);
			}
		}
		sort($arrRet);
		return $arrRet;
	}
	
	public function getRandSeq($min, $max, $num)
	{
		$arrRet = array();
		for ($i = 0; $i < $num; $i++)
		{
			$x = mt_rand($min, $max);
			//如果这个数取过了，就取紧挨着的下一个数
			while (in_array($x, $arrRet))
			{
				if (++$x > $max)
				{
					$x = $min;
				}
			}
			$arrRet[] = $x;
		}
		return $arrRet;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */