<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UserDao.class.php 203190 2015-10-19 10:29:06Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/UserDao.class.php $
 * @author $Author: wuqilin $(lanhongyu@babeltime.com)
 * @date $Date: 2015-10-19 10:29:06 +0000 (Mon, 19 Oct 2015) $
 * @version $Revision: 203190 $
 * @brief
 *
 **/



class UserDao
{
	const tblUser = 't_user';
	const tblRandomName = 't_random_name';

	private static $notDel = array('status', '!=', UserDef::STATUS_DELETED);

	const STATUS_SUSPEND = 1;
	const STATUS_DEL = 2;

	public static function getArrUserByPid($pid, $arrField, $serverId = 0)
	{
		$arrField = array_merge($arrField);
		$where = array("pid", "=", $pid);		
		$data = new CData();
		$data->select($arrField)->from(self::tblUser)
				->where($where)->where(self::$notDel);

		if(defined('GameConf::MERGE_SERVER_OPEN_DATE'))
		{
			if($serverId == 0)
			{
				$serverId = Util::getServerId();
			}
			$data->where('server_id', '=', $serverId);
		}
		$arrRet = $data->query();
		return $arrRet;
	}

	public static function getUserByUid($uid, $arrField, $noCache = false)
	{
		$arrField = array_merge($arrField);
		$data = new CData();
		$where = array('uid', '=', $uid);		
		$arrRet = $data->select($arrField)->from(self::tblUser)
				->where($where)->where(self::$notDel);		

		if($noCache)
		{
			$data->noCache();
		}
		
		$arrRet = $data->query();
		if (empty($arrRet))
		{
			return $arrRet;
		}
		return $arrRet[0];
	}

	

	public static function getUsersNumByPid($pid)
	{
		$data = new CData();
		$where = array('pid', '=', $pid);
		
		$data->selectCount()->from(self::tblUser)
					->where($where)->where(self::$notDel);
					
		if(defined('GameConf::MERGE_SERVER_OPEN_DATE'))
		{
			$serverId = Util::getServerId();
			$data->where('server_id', '=', $serverId);
		}
		$arrRet = $data->query();
		
		return $arrRet[0]['count'];
	}

	public static function updateUser($uid, $arrUserInfo)
	{
		$data = new CData();
		$where = array('uid', '=', $uid);		
		$arrRet = $data->update(self::tblUser)->set($arrUserInfo)
					->where($where)->where(self::$notDel)->query();
	}

	
	

	public static function createUser($arrUserInfo)
	{
		$data = new CData();
		if(defined('GameConf::MERGE_SERVER_OPEN_DATE'))
		{
			$arrUserInfo['server_id'] = Util::getServerId();
// 			$arrUserInfo['uname'] .= Util::getSuffixName();
		}		
		
		$arrRet = $data->insertIgnore(self::tblUser)->values($arrUserInfo)->query();
		return $arrRet;
	}

	public static function getRandomName($arrField, $gender, $limit, $offset)
	{
		$data = new CData();
		$where = array('status', '=', UserDef::RANDOM_NAME_STATUS_OK);
		$arrRet = $data->select($arrField)->from(self::tblRandomName)
				->where('gender', '=', $gender)
				->where($where)->limit($offset,$limit)->query();
		return $arrRet;
	}
	
	public static function countRandomName($gender)
	{
		$data = new CData();
		$arrRet = $data->selectCount()->from(self::tblRandomName)->where('gender', '=', $gender)
			->where('status', '=', UserDef::RANDOM_NAME_STATUS_OK)->query();
		return $arrRet[0]['count'];
	}

	public static function setRandomNameStatus($name, $status)
	{
		$data = new CData();
		$where = array('name', '==', $name);
		$arrRet = $data->update(self::tblRandomName)->set(array('status'=>$status))
					->where($where)->query();
		return $arrRet;
	}

	public static function unameToUid($uname)
	{
		$data = new CData();
		$where = array('uname', '==', $uname);
		$ret = $data->select(array('uid','utid'))->from(self::tblUser)->where($where)->
			where(self::$notDel)->query();
		if (!empty($ret))
		{
			return $ret[0];
		}
		return 0;
	}
	
	public static function getUserByUname($uname,$arrField)
	{
	    $data = new CData();
	    $where = array('uname', '==', $uname);
	    $ret = $data->select($arrField)->from(self::tblUser)->where($where)->
	            where(self::$notDel)->query();
	    if (!empty($ret))
	    {
	        return $ret[0];
	    }
	    return array();
	}

	
	public static function getArrUser($offset, $limit, $arrField)
	{
		$arrField = array_merge($arrField);
		$data = new CData();
		$arrRet = $data->select($arrField)->from(self::tblUser)->orderBy('uid', true)
			->limit($offset, $limit)->where('uid', '>', FrameworkConfig::MIN_UID-1)->query();
		return $arrRet;
	}
	
	/**
	 * 根据uid升序返回limit个用户信息， 从$offsetUid（包括）开始.
	 * @param int $offsetUid
	 * @param int $limit
	 * @param int $arrField
	 */
	public static function getArrUserByOffsetUid($offsetUid, $limit, $arrField)
	{
		$arrField = array_merge($arrField);
		$data = new CData();
		$arrRet = $data->select($arrField)->from(self::tblUser)->orderBy('uid', true)
			->limit(0, $limit)->where('uid', '>', $offsetUid-1)->query();
		return $arrRet;
	}
	
	public static function getArrUserByArrPid($arrPid, $arrField, $arrWhere = array())
	{
		$arrField = array_merge($arrField);
		$data = new CData();	
		$data->select($arrField)->from(self::tblUser)->where('pid', 'in', $arrPid)
			->where(self::$notDel);
		
		if( !empty($arrWhere) )
		{
			foreach($arrWhere as $value)
			{
				$data->where($value);
			}
		}
		
		if(defined('GameConf::MERGE_SERVER_OPEN_DATE'))
		{
			$serverId = Util::getServerId();
			$data->where('server_id', '=', $serverId);
		}
		
		$ret = $data->query();
		return $ret;
	}
	
	public static function getByUname($uname, $arrField)
	{
		$arrField = array_merge($arrField);
		$data = new CData();
		$ret = $data->select($arrField)->from(self::tblUser)
			->where('uname', '==', $uname)->where(self::$notDel)->query();
		if (!empty($ret))
		{
			return $ret[0];
		}
		return $ret;
	}
	
	public static function getByFuzzyName($uname, $arrField, $offset, $limit)
	{
		if( empty($uname) )
		{
			throw new InterException('uname cant be empty');
		}
		$arrField = array_merge($arrField);
		$data = new CData();
		$ret = $data->select($arrField)->from(self::tblUser)
				->where('uname', 'LIKE', "%$uname%")->limit($offset, $limit)->query();

		return $ret;
	}
	
	public static function getArrUsersByLevel($level, $num, $op, $arrField,$offset=0, $arrCond = array(), $db = '')
	{
		$arrField = array_merge($arrField);
		$data = new CData();
		
		if( !empty($db) )
		{
			$data->useDb($db);
		}
			
		$data->select($arrField)->from(self::tblUser);
		
		if($op == '>=' || $op == '>')
		{
			$data->orderBy('level', true);
		}
		else if($op == '<='  || ($op == '<'))
		{
			$data->orderBy('level', false);
		}
		
		foreach ($arrCond as $cond)
		{
			$data->where($cond);
		}
		
		$arrRet = $data->limit($offset, $num)->where('level', $op, $level)->query();
		
		return $arrRet;
	}
	
	
	public static function getArrUserByArrUid($arrUid, $arrField, $noCache = false, $db = '')
	{	
		if (empty ( $arrUid ))
		{
			return array ();
		}
			
		$arrField = array_merge ( $arrField );
				
		$data = new CData ();
		
		if( !empty($db) )
		{
			$data->useDb($db);
		}
		
		$data->select ( $arrField )->from ( self::tblUser )
				->where ( self::$notDel )->where('uid', 'IN', $arrUid );
		
		if($noCache)
		{
			$data->noCache();
		}
		
		$arrRet = $data->query();
	
		return $arrRet;
	}
	
	public static function getArrUserByWhereOrder($offset,$limit,$arrField,$arrWhere=array(),$arrOrder=array())
	{
	    $data = new CData ();
	    $data->select ( $arrField )
	         ->from ( self::tblUser );
	    //where
	    foreach($arrWhere as $where)
	    {
	        $data->where($where);
	    }
	    //order
	    foreach($arrOrder as $field => $orderType)
	    {
	        $data->orderBy($field, $orderType);
	    }
	    //limit
	    $data->limit($offset, $limit);
	    
	    $arrRet = $data->query();
	    
	    return $arrRet;
	}
	
	public static function getTopLevel($offset, $limit, $arrField)
	{
		$arrField = array_merge($arrField);
		$data = new CData();
			
		$arrRet = $data->select($arrField)->from(self::tblUser)
			->where ( self::$notDel )->orderBy('level', false)
			->limit($offset, $limit)->query();

		return $arrRet;
	}
	public static function getArrUserEqLevel($level, $arrField, $num)
	{
		$arrField = array_merge($arrField);
		$data = new CData();
			
		$arrRet = $data->select($arrField)->from(self::tblUser)
		->where ( 'level', '=', $level )
		->where ( self::$notDel )
		->orderBy('upgrade_time', true)
		->orderBy('uid', true)
		->limit(0, $num)->query();
		
		return $arrRet;
	}
	
	public static function getUserNumBetweenLevel($lowLevel, $highLevel)
	{
		if($lowLevel > $highLevel)
		{
			Logger::fatal('invalid param. lowLevel:%d, hightLevel:%d', $lowLevel, $highLevel);
			return 0;
		}
		$data = new CData();
		$ret = $data->selectCount()->from(self::tblUser)
				->where('level', 'BETWEEN', array($lowLevel, $highLevel) )
				->query();
		
		return $ret[0]['count'];
	}
	
	public static function getArrUserBetweenLevel($arrField, $lowLevel, $highLevel, $offset, $num)
	{
		if($lowLevel > $highLevel)
		{
			Logger::fatal('invalid param. lowLevel:%d, hightLevel:%d', $lowLevel, $highLevel);
			return 0;
		}
		$data = new CData();
		$arrRet = $data->select($arrField)->from(self::tblUser)
		->where('level', 'BETWEEN', array($lowLevel, $highLevel) )
		->limit($offset, $num)->query();
		
		return $arrRet;
	}

	public static function getRankListByColumn($arrColumn,$offset,$limit)
	{
		$select = array(
					
				'uid','master_hid','uname','level','fight_force','guild_id','vip'
		);
	
		$data = new CData();
	
		$retRankList = $data->select($select)->from('t_user')
		->where( array('uid','!=','0') )
		->orderBy($arrColumn, false)
		->orderBy('exp_num', false)
		->orderBy('uid', true)
		->limit($offset,$limit)
		->query();
	
		return $retRankList;
	}

	public static function getUserNumByCondition($arrColumn,$arrPrivateColumn,$arrExp,$arrUid)
	{
		$arrCondition = array(
				array($arrColumn,'>',$arrPrivateColumn)
		);
		$dataColumn = new CData();
		$dataColumn->selectCount()->from('t_user');
		foreach ($arrCondition as $condition)
		{
			$dataColumn->where($condition);
		}
		$arrRank = $dataColumn->query();
		$rankColumn = $arrRank[0]['count'];
		
		$arrCondition = array(
				array($arrColumn,'=',$arrPrivateColumn),
				array('exp_num','>',$arrExp)
		);
		$dataExp = new CData();
		$dataExp->selectCount()->from('t_user');
		foreach ($arrCondition as $condition)
		{
			$dataExp->where($condition);
		}
		$arrRank = $dataExp->query();
		$rankExp = $arrRank[0]['count'];
		
		$arrCondition = array(
				array($arrColumn,'=',$arrPrivateColumn),
				array('exp_num','=',$arrExp),
				array('uid','<',$arrUid)
		);
		$dataUid = new CData();
		$dataUid->selectCount()->from('t_user');
		foreach ($arrCondition as $condition)
		{
			$dataUid->where($condition);
		}
		$arrRank = $dataUid->query();
		$rankUid = $arrRank[0]['count'];
		
		return array($rankColumn,$rankExp,$rankUid);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
