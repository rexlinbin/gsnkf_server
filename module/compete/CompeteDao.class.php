<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CompeteDao.class.php 110178 2014-05-22 06:52:42Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/compete/CompeteDao.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-05-22 06:52:42 +0000 (Thu, 22 May 2014) $
 * @version $Revision: 110178 $
 * @brief 
 *  
 **/
/**********************************************************************************************************************
 * Class       : CompeteDao
 * Description : 比武系统的数据库操作类
 * Inherit     :
 **********************************************************************************************************************/
class CompeteDao
{
	/**
	 * 插入新的用户数据
	 *
	 * @param array $arrField
	 */
	public static function insertOrUpdate($arrField)
	{
		$data = new CData();
		//以下的字段无论是否有值都会更新,其他字段则不会更新
		$arrKey = array(
				CompeteDef::COMPETE_NUM,
				CompeteDef::LAST_POINT,
				CompeteDef::COMPETE_TIME,
				CompeteDef::REFRESH_TIME,
				CompeteDef::REWARD_TIME,
				CompeteDef::VA_COMPETE
		);
		$data->insertOrUpdate(CompeteDef::COMPETE_TABLE)
			 ->values($arrField)
			 ->onDuplicateUpdateKey($arrKey)
			 ->query();
	}
	
	/**
	 * 获取用户信息
	 *
	 * @param int $uid							用户id
	 * @return array $arrRet                	结果集或false
	 */
	public static function select($uid)
	{
		//使用uid作为检索条件
		$data = new CData();
		$ret = $data->select(CompeteDef::$COMPETE_FIELDS)
					->from(CompeteDef::COMPETE_TABLE)
					->where(array(CompeteDef::COMPETE_UID, '=', $uid))
					->query();
		// 检查返回值
		if (!empty($ret[0]))
		{
			return $ret[0];
		}
		// 没检索结果的时候，直接返回false
		return false;
	}
	
	/**
	 * 更新用户信息
	 *
	 * @param string $uid						用户ID
	 * @param array $arrField					更新项目
	 */
	public static function update($uid, $arrField)
	{
		$data = new CData();
		$data->update(CompeteDef::COMPETE_TABLE)
			 ->set($arrField)
			 ->where(array(CompeteDef::COMPETE_UID, '=', $uid))
			 ->query();
	}
	
	/**
	 * 更新所有用户信息
	 *
	 * @param array $arrField					更新项目
	 */
	public static function updateAll($arrField)
	{
		$data = new CData();
		$data->update(CompeteDef::COMPETE_TABLE)
			 ->set($arrField)
			 ->where(array(CompeteDef::COMPETE_UID, '>', SPECIAL_UID::MAX_ROBOT_UID))
			 ->query();
	}
	
	/**
	 * 获取积分值小于等于定值的用户信息
	 * 
	 * @param int $point
	 * @param int $num
	 * @param int $offset
	 * @return array $arrRet                	结果集
	 */
	public static function getUsersWithLessPoint($point, $num, $offset = 0)
	{
		$data = new CData();
		$arrRet = $data->select(array(CompeteDef::COMPETE_UID, CompeteDef::COMPETE_POINT))
					   ->from(CompeteDef::COMPETE_TABLE)
					   ->where(array(CompeteDef::COMPETE_POINT, '<=', $point))
					   ->orderBy(CompeteDef::COMPETE_POINT, false)
					   ->limit($offset, $num)
					   ->query();
	
		return 	Util::arrayIndexCol($arrRet, CompeteDef::COMPETE_UID, CompeteDef::COMPETE_POINT);
	}
	
	/**
	 * 获取积分在[$minPoint, $maxPoint]内的用户
	 * 
	 * @param int $minPoint
	 * @param int $maxPoint
	 * @param int $num
	 * @param int $offset
	 * @return array $arrRet                	结果集
	 */
	public static function getUsersBetweenPoint($minPoint, $maxPoint, $num, $offset = 0)
	{
		$data = new CData();
		
		$ret = $data->selectCount()->from(CompeteDef::COMPETE_TABLE )
					->where(CompeteDef::COMPETE_POINT, 'BETWEEN', array($minPoint, $maxPoint) )
					->query();
				
		$allNum = $ret[0]['count'];
		
		if($allNum <= 0)
		{
			Logger::trace('not found user between point[%d, %d]', $minPoint, $maxPoint);
			return array();
		}
		
		$offset = 0;
		if($allNum > $num)
		{
			$offset = rand(0, $allNum - $num);
		}
		
		$arrRet = $data->select(array(CompeteDef::COMPETE_UID, CompeteDef::COMPETE_POINT))
						->from(CompeteDef::COMPETE_TABLE)
						->where(array(CompeteDef::COMPETE_POINT, 'BETWEEN', array($minPoint, $maxPoint) ))
						->limit($offset, $num)
						->query();
		
		return 	Util::arrayIndexCol($arrRet, CompeteDef::COMPETE_UID, CompeteDef::COMPETE_POINT);
	}
	
	/**
	 * 获取积分值大于等于定值的用户信息
	 *
	 * @param int $point
	 * @param int $num
	 * @param int $offset
	 * @return array $arrRet                	结果集
	 */
	public static function getUsersWithMorePoint($point, $num, $offset = 0)
	{
		$data = new CData();
		$arrRet = $data->select(array(CompeteDef::COMPETE_UID, CompeteDef::COMPETE_POINT))
					   ->from(CompeteDef::COMPETE_TABLE)
					   ->where(array(CompeteDef::COMPETE_POINT, '>=', $point))
					   ->orderBy(CompeteDef::COMPETE_POINT, true)
					   ->limit($offset, $num)
					   ->query();
	
		return 	Util::arrayIndexCol($arrRet, CompeteDef::COMPETE_UID, CompeteDef::COMPETE_POINT);
	}
	
	/**
	 * 获取符合条件的用户数量
	 *
	 * @param array $arrCond
	 * @return int $num
	 */
	public static function getUserNum($arrCond)
	{
		$data = new CData();
		$data->selectCount()->from(CompeteDef::COMPETE_TABLE);
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		$ret = $data->query();
	
		return 	$ret[0]['count'];
	}
	
	/**
	 * 获取符合条件的用户信息
	 * @param array $arrField
	 * @paramarray $arrCond
	 * @return array $arrRet                	结果集
	 */
	public static function getUsers($arrField, $arrCond)
	{
		$data = new CData();
		$arrRet = array();
		$count = CData::MAX_FETCH_SIZE;
		$i = 0;
		
		while($count >= CData::MAX_FETCH_SIZE)
		{
			$data->select($arrField)->from(CompeteDef::COMPETE_TABLE);
			foreach ($arrCond as $cond)
			{
				$data->where($cond);
			}
			$ret = $data->orderBy(CompeteDef::COMPETE_UID, true)->limit($i * CData::MAX_FETCH_SIZE, CData::MAX_FETCH_SIZE)->query();
			$count = count($ret);
			$i++;
			$arrRet = array_merge($arrRet, $ret);
		}
		
		return $arrRet;
	}
	
	/**
	 * 拉取用户排行榜信息
	 * 
	 * @param int $offset
	 * @param int $limit
	 * @param array $arrField
	 * @return array $arrRet                	结果集
	 */
	public static function getRankList($offset, $limit, $order = CompeteDef::COMPETE_POINT, $arrField = array())
	{
		$arrField[] = $order;
		if (!in_array(CompeteDef::COMPETE_UID, $arrField)) 
		{
			$arrField[] = CompeteDef::COMPETE_UID;
		}
		$data = new CData();
		$arrRet = $data->select($arrField)
					   ->from(CompeteDef::COMPETE_TABLE)
					   ->where(array(CompeteDef::COMPETE_UID, '>', 0))
					   ->orderBy($order, false)
					   ->orderBy(CompeteDef::POINT_TIME, true)
					   ->orderBy(CompeteDef::COMPETE_UID, true)
					   ->limit($offset, $limit)
					   ->query();
		return 	Util::arrayIndex($arrRet, CompeteDef::COMPETE_UID);
	}
	
	public static function getMinRewardTime()
	{
		$data = new CData();
		$arrRet = $data->select(array('min(reward_time)'))
					   ->from(CompeteDef::COMPETE_TABLE)
					   ->where(array(CompeteDef::COMPETE_UID, '>', SPECIAL_UID::MAX_ROBOT_UID))
					   ->where(array(CompeteDef::REWARD_TIME, '>', 0))
					   ->query();
		return $arrRet[0]['min(reward_time)'];
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */