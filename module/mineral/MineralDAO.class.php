<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MineralDAO.class.php 135462 2014-10-09 03:16:48Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mineral/MineralDAO.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-10-09 03:16:48 +0000 (Thu, 09 Oct 2014) $
 * @version $Revision: 135462 $
 * @brief 
 *  
 **/
class MineralDAO
{
	private static $tblMineral = 't_mineral';
    private static $tblMineralGuards = 't_mineral_guards';
    private static $tblMineralRobLog = 't_mineral_roblog';
	/**
	 * 根据页号获取矿坑，获取某一页所有的矿坑
	 */
	public static function getPitsByDomain($domainId)
	{
		$data = new CData();
		$ret = $data->select(TblMineralField::$FIELDS)
			 		->from(self::$tblMineral)
			 		->where(array(TblMineralField::DOMAINID,'=',$domainId))
			 		->query();
		if(empty($ret))
		{
			throw new FakeException('no resource with domainid %s.',$domainId);
		}
		return $ret;
	}

    /**
     * 根据资源区id和矿坑id查找 资源矿数据
     * @param $domainId 资源区id
     * @param $pitId    矿坑id
     * @return array
     */
    public static function getPitById($domainId,$pitId)
	{
		$data = new CData();
		$ret = $data->select(TblMineralField::$FIELDS)
					->from(self::$tblMineral)
					->where(array(TblMineralField::DOMAINID,'=',$domainId))
					->where(array(TblMineralField::PITID,'=',$pitId))
					->query();
		if(empty($ret[0]))
		{
			return array();
		}
		return $ret[0];
	}
	
	public static function getPitsByUid($uid)
	{
		$data = new CData();
		$ret  = $data->select(TblMineralField::$FIELDS)
					 ->from(self::$tblMineral)
					 ->where(array(TblMineralField::UID,'=',$uid))
                     ->orderBy(TblMineralField::OCCUPYTIME, TRUE)
					 ->query();		
		return $ret;		
	}
	
    /**
     * 根据uid 查询某个玩家 占领的资源矿数
     * @param $uid
     * @return mixed
     */
    public static function getPitNumByUid($uid)
	{
		$data = new CData();
		$ret  = $data->selectCount()
					 ->from(self::$tblMineral)
					 ->where(array(TblMineralField::UID,'=',$uid))
					 ->where(array(TblMineralField::DOMAINTYPE,'IN',array(MineralType::NORMAL,MineralType::SENIOR)))
					 ->query();
		return $ret[0]['count'];
	}
	
	public static function getGoldPitNumByUid($uid)
	{
	    $data = new CData();
	    $ret  = $data->selectCount()
	    ->from(self::$tblMineral)
	    ->where(array(TblMineralField::UID,'=',$uid))
	    ->where(array(TblMineralField::DOMAINTYPE,'IN',array(MineralType::GOLD)))
	    ->query();
	    return $ret[0]['count'];
	}
	
	
	public static function savePitInfo($domainId,$pitId,$pitInfo)
	{
		$data	=	new CData();
		$data->update(self::$tblMineral)
		     ->set($pitInfo)
		     ->where(array(TblMineralField::DOMAINID,'=',$domainId))
		     ->where(array(TblMineralField::PITID,'=',$pitId))
		     ->query();
	}
	
	public static function insertPitInfo($pitInfo)
	{
	    $data	=	new CData();
	    $data->insertInto(self::$tblMineral)
    	    ->values($pitInfo)
    	    ->query();
	}
	
	public static function explorePit($domainType, $pitType)
	{
		$data = new CData();
		$data->select(array(TblMineralField::DOMAINID,TblMineralField::PITID))
					->from(self::$tblMineral)
					->where(TblMineralField::UID,'=',0)
					->where(TblMineralField::DOMAINTYPE,'=',$domainType);
		if(!empty($pitType))
		{
			$data->where(TblMineralField::PITTYPE, '=', $pitType);
		}
		$data->orderBy(TblMineralField::DOMAINID, TRUE)
				->limit(0, 1);
		$ret = $data->query();
		if(empty($ret))
		{
			return array();
		}
		return $ret[0];
	}
	
	public static function getDomainIdOfUser($uid,$domainType)
	{
	    $data = new CData();
	    $data->select(array(TblMineralField::DOMAINID))
            	    ->from(self::$tblMineral)
            	    ->where(TblMineralField::UID,'=',$uid);
        if($domainType == MineralType::GOLD)
        {
            $data->where(TblMineralField::DOMAINTYPE,'=',MineralType::GOLD);
        }    	    
        else
        {
            $data->where(TblMineralField::DOMAINTYPE,'!=',MineralType::GOLD);
        }
	    $ret = $data->orderBy(TblMineralField::DOMAINID, TRUE)
        	         ->limit(0, 1)
        	         ->query();
	    if(empty($ret) || empty($ret[0]))
	    {
	        return 0;
	    }
	    return $ret[0][TblMineralField::DOMAINID];
	}
	
	public static function getArrPit($domainId,$arrPitId=array())
	{
	    $data = new CData();
	    $data->select(TblMineralField::$FIELDS)
             ->from(self::$tblMineral)
             ->where(array(TblMineralField::DOMAINID,'=',$domainId));
	    if(!empty($arrPitId))
	    {
	        $data->where(array(TblMineralField::PITID,'IN',$arrPitId));
	    }
        $ret = $data->query();
	    return $ret;
	}
	
	public static function getArrPitByUid($uid)
	{
	    $data = new CData();
	    $ret  = $data->select(TblMineralField::$FIELDS)
            	    ->from(self::$tblMineral)
            	    ->where(array(TblMineralField::UID,'=',$uid))
            	    ->orderBy(TblMineralField::OCCUPYTIME, TRUE)
            	    ->query();
	    return $ret;
	}
	
	
	public static function getArrGuard($domainId,$arrPitId=array())
	{
	    $data = new CData();
	    $data->select(TblMineralGuards::$GUARDFIELDS)
    	     ->from(self::$tblMineralGuards)
    	     ->where(TblMineralGuards::DOMAINID, '=', $domainId);
	    if(!empty($arrPitId))
	    {
	        $data->where(TblMineralGuards::PITID, 'IN', $arrPitId);
	    }
        $ret = $data->where(TblMineralGuards::STATUS, '=', GuardType::ISGUARD)
                    ->orderBy(TblMineralGuards::GUARDTIME, false)
                    ->query();
	    return $ret;
	}

    public static function getArrGuards($domainId, $pitId, $offset, $limit)
    {
        $data = new CData();
        $ret = $data->select(TblMineralGuards::$GUARDFIELDS)
                    ->from(self::$tblMineralGuards)
                    ->where(TblMineralGuards::DOMAINID, '=', $domainId)
                    ->where(TblMineralGuards::PITID, '=', $pitId)
                    ->where(TblMineralGuards::STATUS, '=', GuardType::ISGUARD)
                    ->orderBy(TblMineralGuards::GUARDTIME, false)
                    ->limit($offset, $limit)
                    ->query();
        return $ret;
    }

    public static function getGuardInfoByUid($uid)
    {
        $data = new CData();
        $ret = $data->select(TblMineralGuards::$GUARDFIELDS)
                    ->from(self::$tblMineralGuards)
                    ->where(TblMineralGuards::UID, '=', $uid)
                    ->where(TblMineralGuards::STATUS, '=', GuardType::ISGUARD)
                    ->query();
        if(empty($ret[0]))
        {
            return array();
        }
        return $ret[0];
    }
    
    /**
     * 更新某矿的守卫军
     * @param $fields
     */
    public static function updateGuards($arrConf, $arrField)
    {
        $data = new CData();
        $data->update(self::$tblMineralGuards)
             ->set($arrField);
        foreach($arrConf as $conf)
        {
            $data->where($conf);
        }
        $arrRet = $data->query();
        if ($arrRet['affected_rows'] == 0)
        {
            return false;
        }
        return true;
    }

    public static function insertUpdateGuard($arrField)
    {
        $data = new CData();
        $data->insertOrUpdate(self::$tblMineralGuards)
             ->values($arrField)
             ->query();
    }
    
    public static function getRobLog()
    {
        $data = new CData();
        $ret = $data->select(array('va_log'))
                    ->from(self::$tblMineralRobLog)
                    ->where(array('id','=',0))
                    ->query();
        if(empty($ret))
        {
            return array();
        }
        return $ret[0]['va_log'];
    }
    
    public static function updateRobLog($arrRobLog)
    {
        $data = new CData();
        $data->insertOrUpdate(self::$tblMineralRobLog)
             ->values(array('va_log'=>$arrRobLog,'id'=>0))
             ->query();
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */