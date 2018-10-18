<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ArenaDao.class.php 151540 2015-01-10 06:45:18Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/arena/ArenaDao.class.php $
 * @author $Author: BaoguoMeng $(lanhongyu@babeltime.com)
 * @date $Date: 2015-01-10 06:45:18 +0000 (Sat, 10 Jan 2015) $
 * @version $Revision: 151540 $
 * @brief 
 *  
 **/




class ArenaDao
{
	const tblName = 't_arena';
	const tblNameMsg = 't_arena_msg';
	const tblNameHis = 't_arena_history';
	const tblNameFmt = 't_arena_fmt';
	
	public static function insert($arrField)
	{
		$data = new CData();
		$data->insertInto(self::tblName)->values($arrField)->query();
	}
	
	public static function get($uid, $arrField)
	{
		$data = new CData();
		$ret = $data->select($arrField)
					->from(self::tblName)
					->where('uid', '=', $uid)
					->query();
		if (!empty($ret))
		{
			return $ret[0];
		}
		return $ret;
	}
	
	public static function getArrInfo($arrUid, $arrField)
	{
		if (!in_array('uid', $arrField))
		{
			$arrField[] = 'uid';
		}
		$data = new CData();
		$arrRet = $data->select($arrField)
					   ->from(self::tblName)
					   ->where('uid', 'IN', $arrUid)
					   ->query();
		return Util::arrayIndex($arrRet, 'uid');
	}
	
	public static function getByPos($pos, $arrField)
	{
		$data = new CData();
		$ret = $data->select($arrField)
					->from(self::tblName)
					->where('position', '=', $pos)
					->query();
		if (!empty($ret))
		{
			return $ret[0];
		}
		//npc特殊处理 . pos不是npc默认位置时，返回空
		return ArenaLogic::getNpcDefValue($pos, $arrField);		
	}
	
	public static function getByArrPos($arrPos, $arrField)
	{
		if (!in_array('position', $arrField))
		{
			$arrField[] = 'position';
		}
		
		$data = new CData();
		$ret = $data->select($arrField)
					->from(self::tblName)
					->where('position', 'IN', $arrPos)
					->query();
		
		$npcPos = array_diff($arrPos, Util::arrayExtract($ret, 'position'));
		foreach ($npcPos as $aPos)
		{
			$ret[] = ArenaLogic::getNpcDefValue($aPos, $arrField);
		}
		
		return $ret;
	}
	
	public static function getByPosInRange($uid, $min, $max, $arrField)
	{
		$data = new CData();
		if ($max <= ArenaConf::NPC_NUM) 
		{
			$ret = $data->select(array('position'))
						->from(self::tblName)
						->where('uid', '=', $uid)
						->query();
			$pos = empty($ret) ? 0 : $ret[0]['position'];
			$rand = $pos;
			while ($rand == $pos)
			{
				$rand = rand($min, $max);
			}
			$info = self::getByPos($rand, $arrField);
		}
		else 
		{
			$ret = $data->selectCount()
						->from(self::tblName)
						->where('position', 'between', array($min, $max))
						->where('uid', '!=', $uid)
						->query();
			$count = $ret[0]['count'];
			$ret = $data->select($arrField)
						->from(self::tblName)
						->where('position', 'between', array($min, $max))
						->where('uid', '!=', $uid)
						->limit(rand(0, $count-1), 1)
						->query();
			$info = $ret[0];
		}
		
		return $info;
	}
	
	public static function getArrByPos($arrPos, $arrField)
	{
		$data = new CData();
		$arrRet = $data->select($arrField)
					   ->from(self::tblName)
					   ->where('position', 'in', $arrPos)
					   ->query();
		return $arrRet;
	}
	
	public static function getCount()
	{
		/*
		$data = new CData();
		$arrRet = $data->selectCount()
					   ->from(self::tblName)
					   ->where('uid', '>', '0')
					   ->query();
		return $arrRet[0]['count'];
		*/
		//npc特殊处理
		$data = new CData();
		$ret = $data->select( array('max(position)') )
					->from(self::tblName)
					->where('uid', '>', '0')
					->query();
		$maxPos = 0;
		if (!empty($ret))
		{
			$maxPos = current($ret[0]);
		}
		if( $maxPos < ArenaConf::NPC_NUM  )
		{
			$maxPos = ArenaConf::NPC_NUM;
		}
		return $maxPos;
		
	}
	
	public static function getMinRewardTime()
	{
		$data = new CData();
		$arrRet = $data->select(array('min(reward_time)'))
					   ->from(self::tblName)
					   ->where('uid', '>', 0)
					   ->query();
		return $arrRet[0]['min(reward_time)'];
	} 
	
	/**
	 * 得到名次区间, 用于发奖
	 * 奖励时间小于reward_time
	 * 
	 * @param int $pos1
	 * @param int $pos2
	 * @param int $arrField
	 */
	public static function getPosRange4Reward($pos1, $pos2, $rewardTime, $arrField)
	{
		$data = new CData();
		$arrPos = range($pos1, $pos2);
		$arrRet = $data->select($arrField)
					   ->from(self::tblName)
					   ->where('position', 'in', $arrPos)
					   ->where(array('reward_time', '<', $rewardTime))			
            		   ->orderBy('position', false)
					   ->query();
		return $arrRet;
	}
	
	public static function update($uid, $arrField)
	{
		$data = new CData();
		$data->update(self::tblName)->set($arrField)->where('uid', '=', $uid)->query();
	}
	
	public static function insertOrUpdate($arrField)
	{
		$data = new CData();
		$data->insertOrUpdate(self::tblName)->values($arrField)->query();
	}

    public static function updateByPos($pos, $arrField)
	{
		$data = new CData();
		$data->update(self::tblName)->set($arrField)->where('position', '=', $pos)->query();
	}
	
	public static function updateChallenge($info, $atkedInfo, $oldPos, $oldAtkPos)
	{	
		$roundPos = rand(100000000, 200000000);		
		if ($oldPos > $oldAtkPos)
		{
			$min = $atkedInfo;
			$max = $info;
		}
		else
		{
			$min = $info;
			$max = $atkedInfo;
		}
		
		$batchData = new BatchData();
		
		/*
		//位置小的放到tmp里面
		$tmpData = $batchData->newData();
		$tmpPos = $min['position'];
		$min['position'] = $roundPos;
		$tmpData->update(self::tblName)->set($min)
		  ->where('uid', '=', $min['uid'])->query();
		
		//更新大的
		$dataOther = $batchData->newData();
		$dataOther->update(self::tblName)->set($max)
			->where('uid', '=', $max['uid'])->query();

		//更新小的
		$min['position'] = $tmpPos;		
		$dataInfo = $batchData->newData();
		$dataInfo->update(self::tblName)->set($min)
			->where('uid', '=', $min['uid'])->query();
			*/
		
		//npc特殊处理
		//位置小的放到tmp里面
		$tmpData = $batchData->newData();
		$tmpPos = $min['position'];
		$min['position'] = $roundPos;
		$tmpData->insertOrUpdate(self::tblName)->values($min)->query();
		
		//更新大的
		$dataOther = $batchData->newData();
		$dataOther->insertOrUpdate(self::tblName)->values($max)->query();
		
		//更新小的
		$min['position'] = $tmpPos;
		$dataInfo = $batchData->newData();
		$dataInfo->insertOrUpdate(self::tblName)->values($min)->query();
		
		$batchData->query();
	}
	
	public static function insertMsg($arrField)
	{
		$data = new CData();
		$data->insertInto(self::tblNameMsg)->values($arrField)->query();
	}
	
	public static function getMsg($uid, $arrField, $num)
	{
		if (!isset($arrField['id']))
		{
			$arrField[] = 'id';
		}
		
		//atk msg
		$data = new CData();
		$atkRet = $data->select($arrField)
					   ->from(self::tblNameMsg)
					   ->where('attack_uid', '=', $uid)
					   ->orderBy('attack_time', false)
					   ->limit(0, $num)
					   ->query();
		
		$defRet = $data->select($arrField)
					   ->from(self::tblNameMsg)
					   ->where('defend_uid', '=', $uid)
					   ->orderBy('attack_time', false)
					   ->limit(0, $num)
					   ->query();
		
		//reverse cmp
		$rcmp = function  ($msg1, $msg2)
		{
			if ($msg1['id'] < $msg2['id'])
			{
				return 1;
			}
			//主键没有相当的情况
			return -1;
		};
		
		$arrMsg = array_merge($atkRet, $defRet);
		usort($arrMsg, $rcmp);
		
		$arrRet = array();
		$i = 0;
		$curMsg = current($arrMsg);
		while ($i<$num && $curMsg)
		{		
			$arrRet[] = $curMsg;
			$i++;
			$curMsg = next($arrMsg);
		}
		return $arrRet;
	}
	
	public static function getNewHis($arrField)
	{
		$data = new CData();
		$ret = $data->select($arrField)
					->from(self::tblNameHis)
					->where('update_time', '!=', 0)
					->orderBy('update_time', false)
					->limit(0, 1)
					->query();
		if (!empty($ret))
		{
			return $ret[0];
		}
		return $ret;
	}
	
	public static function updateHis($uid, $pos, $time)
	{
		$data = new CData();
		$arrField = array(
				'uid' => $uid, 
				'position' => $pos, 
				'update_time' => $time
		);
		$data->insertOrUpdate(self::tblNameHis)->values($arrField)->where('position', '=', $pos)->query();
	}
	
	public static function getArrHis($arrCond, $arrFiled)
	{
		$data = new CData();
		$data->select($arrFiled)->from(self::tblNameHis);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
	
		return $data->query();
	}
	
	public static function getArrFmtInfo($arrCond, $arrField)
	{
		$data = new CData();
		$data->select($arrField)->from(self::tblNameFmt);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		
		return $data->query();
	}
	
	public static function insertOrUpdateFmt($values)
	{
		$data = new CData();
		$data->insertOrUpdate(self::tblNameFmt)->values($values)->query();
	}
	
	public static function updateFmt($arrCond, $arrField)
	{
		$data = new CData();
		$data->update(self::tblNameFmt)->set($arrField);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
	
		$ret = $data->query();
		if ($ret[DataDef::AFFECTED_ROWS] == 0)
		{
			throw new InterException('update affected num 0, field: %s, cond: %s', $arrField, $arrCond);
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */