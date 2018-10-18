<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NCopyDAO.class.php 131493 2014-09-11 06:24:22Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ncopy/NCopyDAO.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-09-11 06:24:22 +0000 (Thu, 11 Sep 2014) $
 * @version $Revision: 131493 $
 * @brief 
 *  
 **/
class NCopyDAO
{
	private static $tblcopy = 't_copy';
	private static $tblreplay = 't_defeat_replay';
	private static $tblbasefdrank = 't_base_fdrank';
	private static $tblcopyfdrank = 't_copy_fdrank';
	private static $status = array('status','!=',DataDef::DELETED);
	private static $tblusercopy = 't_user_copy';
	
	/**
	 * 获取一个用户的所有普通副本信息
	 * @param unknown_type $uid
	 * @return
	 */
	public static function getAllCopies($uid)
	{
		$ret  = array();
		$data = new CData();
		$ret  = $data->select(CopyConf::$COPY_TBL_ALL_FIELD)
						->from(self::$tblcopy)
						->where(array('uid','=',$uid))
						->where(self::$status)
						->orderBy('copy_id', FALSE)
						->query();
		if(!empty($ret))
		{
			$ret = Util::arrayIndex($ret, 'copy_id');
		}		
		return $ret;
	}
	/**
	 * 获取某个类型的某个副本
	 * @param int $uid
	 * @param int $copy_id
	 * @return array
	 */
	public static function getCopy($uid,$copyId)
	{
		$ret = array();
		$data = new CData();
		$ret = $data->select(CopyConf::$COPY_TBL_ALL_FIELD)
					->from(self::$tblcopy)
					->where(array('uid','=',$uid))
					->where(array('copy_id','=',$copyId))
					->where(self::$status)
					->query();
		if(empty($ret))
		{
		    return array();
		}
		return $ret[0];
	}
	
	public static function saveCopy($copyInfo)
	{
		if(empty($copyInfo))
		{
			Logger::debug('the parameter copyObj is empty');
		}
		//使用副本对象中的方法更新到数据库中
		$copyInfo['status'] = DataDef::NORMAL;
		$data = new CData();
		$ret = $data->insertOrUpdate(self::$tblcopy)
					->values($copyInfo)
					->query();		
		return $ret;
	}
	/**
	 * 获取据点某个难度级别的攻略
	 * @param int $baseId
	 * @param int $baseLv
	 */
	public static function getReplayList($baseId,$baseLv)
	{
		$data = new CData();
		$ret = array();
		$ret = $data ->select(CopyConf::$REPLAY_TBL_ALL_FIELD)
						->from(self::$tblreplay)
						->where(array('base_id','=',$baseId))
						->where(array('base_level','=',$baseLv))
						->query();
		$replayList    =    util::arrayIndex($ret, 'uid');
		return $replayList;
	}
	/**
	 * 获取攻略的个数
	 * @param int $baseId
	 * @param int $baseLv
	 */
	private static function getReplayListNum($baseId,$baseLv)
	{
		$data = new CData();
		$ret = $data -> selectCount()
					-> from(self::$tblreplay)
					-> where(array('base_id','=',$baseId))
					-> where(array('base_level','=',$baseLv))
					-> query();
		return $ret[0]['count'];
	}
	/**
	 * 添加攻略信息
	 * @param int $baseId
	 * @param int $baseLv
	 */
	public static function addReplay($baseId,$baseLv,$fightRecord)
	{
		$data = new CData();
		$user = Enuser::getUserObj();
		$uid = $user->getUid();
		$level = $user->getLevel();
		$replayList = self::getReplayList($baseId, $baseLv);
		if(isset($replayList[$uid]))
		{
		    return;
		}
		$replayInfo = array(
				'uid'=>$uid,
				'level'=>$level,
		        'base_id'=>$baseId,
		        'base_level'=>$baseLv,
				'va_fight_record'=>$fightRecord
		);
		//如果DB中攻略数目小于攻略显示数目  直接将新的攻略插入到数据库
		if(count($replayList) < CopyConf::$REPLAY_NUM)
		{
			$ret = $data -> insertIgnore(self::$tblreplay)
							-> values($replayInfo)
							-> query();
			return $ret;
		}
		else//如果DB中攻略数目大于攻略显示数目   用新的攻略信息替换DB中的第一个攻略
		{
		    $replay    =    current($replayList);
			$ret = $data ->update(self::$tblreplay)
							->set($replayInfo)
							->where(array('uid','=',$replay['uid']))
							->where(array('base_id','=',$baseId))
							->where(array('base_level','=',$baseLv))
							->query();
			return $ret;
		}
	}
	/**
	 * 获取某个据点的前三通关玩家信息
	 * @param unknown_type $baseId
	 */
	public static function getPreBaseAttackPlayers($baseId,$baseLv)
	{
		$data = new CData();
		$ret = array();
		$ret = $data -> select(CopyConf::$BASE_FDRANK_TBL_ALL_FIELD)
						-> from(self::$tblbasefdrank)
						-> where(array('base_id','=',$baseId))
						-> where(array('base_level','=',$baseLv))
						-> query();
		return $ret;
	}
	public static function addPreBaseAttackPlayer($uid,$baseId,$baseLv,$fight_record)
	{
		$data = new CData();
		//获取数据库中据点首杀的数目
		$ret = $data -> selectCount()
					-> from(self::$tblbasefdrank)
					-> where(array('base_id','=',$baseId))
					-> where(array('base_level','=',$baseLv))
					-> query();
		$rankNum = $ret[0]['count'];

		//如果据点首杀数目小于显示的据点首杀数目  将新的首杀记录插入到数据库中
		if($rankNum < CopyConf::$BASE_PRE_NUM)
		{
			$rankNum = $rankNum+1;
			$user = EnUser::getUserObj($uid);
			//获取玩家当前的级别
			$level = $user->getLevel();
			$arr = array(
					'uid'=>$uid,
					'level'=>$level,
					'base_id'=>$baseId,
					'base_level'=>$baseLv,
					'rank'=>$rankNum,
					'va_fight_record'=>$fight_record
			);
			$ret = $data -> insertIgnore(self::$tblbasefdrank)
							-> values($arr)
							-> query();
		}
		return $rankNum;
	}
	public static function getPreCopyPassPlayers($copy_id)
	{
		$data = new CData();
		$ret = array();
		$ret = $data -> select(CopyConf::$COPY_FDRAND_TBL_ALL_FIELD)
						-> from(self::$tblcopyfdrank)
						-> where(array('copy_id','=',$copy_id))
						-> query();
		return $ret;
	}
	public static function addPreCopyPassPlayer($uid,$copyId)
	{
		$data = new CData();
		$ret = $data -> selectCount()
						-> from(self::$tblcopyfdrank)
						-> where(array('copy_id','=',$copyId))
						-> query();
		$rankNum = $ret[0]['count'];
		//如果数据库中副本首杀的数目小于需要显示的数目    将新的首杀记录插入到数据库中
		if($rankNum < CopyConf::$COPY_PRE_NUM)
		{
			$rankNum = $rankNum+1;
			$user = EnUser::getUserObj($uid);
			//获取玩家当前的级别
			$level = $user->getLevel();
			$arr = array(
					'uid'=>$uid,
					'level'=>$level,
					'copy_id'=>$copyId,
					'rank'=>$rankNum
			);
			$data -> insertIgnore(self::$tblcopyfdrank)
						-> values($arr)
						-> query();
		}
		return $rankNum;
	}
	
    public static function getUserCopyInfo($uid,$arrFiled)
	{
	    $data = new CData();
	    $ret = $data->select($arrFiled)
    	         ->from(self::$tblusercopy)
    	         ->where(array('uid','=',$uid))
    	         ->query();
	    if(empty($ret))
	    {
	        return array();
	    }
	    return $ret[0];
	}
	
	public static function saveUserCopyInfo($uid,$userCopyInfo)
	{
	    $data = new CData();
	    $data->update(self::$tblusercopy)
	         ->set($userCopyInfo)
	         ->where(array('uid','=',$uid))
	         ->query();
	}
	
	public static function insertUserCopyInfo($userCopyInfo)
	{
	    $data = new CData();
	    $data->insertOrUpdate(self::$tblusercopy)
	         ->values($userCopyInfo)
	         ->query();
	}
	
	public static function getTopUserByCopy($offset, $limit)
	{
	    $data = new CData();
	    // 获取所有的副本列表， 这里只使用副本ID排序
	    $arrRet = $data->select(array('uid','copy_id', 'last_copy_time'))
                	    ->from(self::$tblusercopy)
                	    ->where(array("uid", ">", 0))
                	    ->where(array("copy_id", "!=", 0))
                	    ->orderBy('copy_id', false)//降序
                	    ->limit(0, min(array($limit+$offset,DataDef::MAX_FETCH)))
                	    ->query();
	    // 查看数组，如果没查询出来东西，则直接返回
	    if (empty($arrRet))
	    {
	        return $arrRet;
	    }
	    // 查看查询结果， 获取最后一名的实际副本进度
	    $copyInfo = end($arrRet);
	    $copyID = $copyInfo["copy_id"];
	    // 遍历所有的查询结果，把和最后一名相等的内容全部扔掉
	    $arrTmp = array();
	    foreach ($arrRet as $v)
	    {
	        if ($v['copy_id'] > $copyID)
	        {
	            $arrTmp[] = $v;
	        }
	    }
	    $arrRet = $arrTmp;
	    
	    // copy_id 降序，按照 last_copy_time 升序 uid 升序
	    $sortCmp = new SortByFieldFunc(array('copy_id' => SortByFieldFunc::DESC,
	            'last_copy_time' => SortByFieldFunc::ASC,
	            'uid' => SortByFieldFunc::ASC));
	    // 不使用数据库，手动排序
	    usort($arrRet, array($sortCmp, 'cmp'));
	    if (($offset + $limit) > count($arrRet))
	    {
	        // 查询所有和最后一名成就点数相同的人，并通过获取时刻和uid进行排序
	        $sameRet = $data->select(array('uid','copy_id'))
                	        ->from(self::$tblusercopy)
                	        ->where(array("copy_id", "=", $copyID))
                	        ->orderBy('last_copy_time', true)//升序
                	        ->orderBy('uid', true)//升序
                	        ->limit(0, ($offset + $limit)-count($arrRet))
                	        ->query();
	        // 第一次查询的去掉最小等级的所有值，然后跟所有最小等级的值合并
	        $arrRet = array_merge($arrRet, $sameRet);
	    }
	    // 切分，只获取需要获取的部分
	    $arrRet = array_slice($arrRet, $offset, $limit);
	    
	    $arrUid = array();
	    foreach($arrRet as $index => $userInfo)
	    {
	        $arrUid[] = $userInfo['uid'];
	    }
	    //拉取用户的utid和uname
	    $arrUser = EnUser::getArrUser($arrUid, array('uid','level','utid','uname'));
	    foreach($arrRet as $index => $userInfo)
	    {
	        $uid = $userInfo['uid'];
	        if(!isset($arrUser[$uid]))
	        {
	            throw new FakeException('no user info.%s.',$uid);
	        }
	        $user = $arrUser[$uid];
	        $arrRet[$index]['utid'] = $user['utid'];
	        $arrRet[$index]['uname'] = $user['uname'];
	        $arrRet[$index]['level'] = $user['level'];
	    }
	    // 返回实际排名
	    return $arrRet;
	}
	
	public static function getCopyRank($rankNum)
	{
	    $rankList = array();
	    $offset = 0;
	    $data = new CData();
	    while(TRUE)
	    {
	        $limit = DataDef::MAX_FETCH;
	        if($limit > $rankNum)
	        {
	            $limit = $rankNum;
	        }
	        $tmpRet = $data->select(array('score','uid','copy_id'))
	                       ->from(self::$tblusercopy)
	                       ->where(array('score','>',0))
	                       ->orderBy('score', FALSE)
	                       ->orderBy('last_score_time', TRUE)
	                       ->orderBy('uid', TRUE)
	                       ->limit($offset, $limit)
	                       ->query();
	        $offset += $limit;
	        $rankNum -= $limit;
	        $rankList = array_merge($rankList,$tmpRet);
	        if(count($tmpRet) < $limit || ($rankNum <= 0))
	        {
	            break;
	        }
	    }
	    return $rankList;
	}
	
	public static function getCopyRankOfUser($uid,$score,$scoreTime)
	{
	    $data = new CData();
	    $ret1 = $data->selectCount()
	                 ->from(self::$tblusercopy)
	                 ->where(array('score','>',$score))
	                 ->query();
	    $ret2 = $data->selectCount()
            	    ->from(self::$tblusercopy)
            	    ->where(array('score','=',$score))
            	    ->where(array('last_score_time','<',$scoreTime))
            	    ->query();
	    $ret3 = $data->selectCount()
            	    ->from(self::$tblusercopy)
            	    ->where(array('score','=',$score))
            	    ->where(array('last_score_time','=',$scoreTime))
            	    ->where(array('uid','<',$uid))
            	    ->query();
	    return $ret1[0]['count'] + $ret2[0]['count'] + $ret3[0]['count'] + 1;
	}
	
	public static function getArrUserCopyInfo($arrUid,$arrField,$copyId=0)
	{
	    $data = new CData();
	    $data->select($arrField)
	                ->from(self::$tblcopy)
	                ->where(array('uid','IN',$arrUid));
	    if(!empty($copyId))
	    {
	        $data->where(array('copy_id','=',$copyId));
	    }
	    $ret = $data->query();
	    return $ret;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */