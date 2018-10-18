<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroShopDao.class.php 175603 2015-05-29 06:49:10Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/heroshop/HeroShopDao.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-05-29 06:49:10 +0000 (Fri, 29 May 2015) $
 * @version $Revision: 175603 $
 * @brief 
 *  
 **/
class HeroShopDao
{
    private static $tblName = 't_hero_shop';
    
    
    public static function getRankList($startTime)
    {
        $data = new CData();
        $ret = $data->select(array(HeroShopDef::SQL_FIELD_UID,HeroShopDef::SQL_FIELD_SCORE,HeroShopDef::SQL_FIELD_SCORE_TIME))
                    ->from(self::$tblName)
                    ->where(array(HeroShopDef::SQL_FIELD_SCORE,'>',0))
                    ->where(array(HeroShopDef::SQL_FIELD_SCORE_TIME,'>=',$startTime))
                    ->orderBy(HeroShopDef::SQL_FIELD_SCORE, FALSE)
                    ->orderBy(HeroShopDef::SQL_FIELD_SCORE_TIME, TRUE)
                    ->orderBy(HeroShopDef::SQL_FIELD_UID, TRUE)
                    ->limit(0, HeroShopLogic::getSizeOfRealTimeRank())
                    ->query();
        return $ret;
    }
    
    public static function getShopInfoByUid($uid,$arrField)
    {
        $data = new CData();
        $ret = $data->select($arrField)
                    ->from(self::$tblName)
                    ->where(array(HeroShopDef::SQL_FIELD_UID,'=',$uid))
                    ->query();
        if(empty($ret))
        {
            return array();
        }
        return $ret[0];
    }
    
    public static function getShopInfoByScoreAndRank($minScore,$maxRank,$startTime)
    {
        $ret = array();
        $offset = 0;
        $rowNum = self::getUserNumByMinScore($minScore,$startTime);
        if($maxRank > $rowNum)
        {
            $rowNum = $maxRank;
        }
        $limit = DataDef::MAX_FETCH;
        $data = new CData();
        while($rowNum > 0)
        {
            $arrField = array(
                    HeroShopDef::SQL_FIELD_UID,
                    HeroShopDef::SQL_FIELD_SCORE,
                    HeroShopDef::SQL_FIELD_SCORE_TIME,
                    HeroShopDef::SQL_FIELD_REWARD_TIME
                    );
            $tmpRet = $data->select($arrField)
                           ->from(self::$tblName)
                           ->where(array(HeroShopDef::SQL_FIELD_SCORE_TIME,'>=',$startTime))
                           ->where(array(HeroShopDef::SQL_FIELD_SCORE,'>',0))
                           ->orderBy(HeroShopDef::SQL_FIELD_SCORE, FALSE)
                           ->orderBy(HeroShopDef::SQL_FIELD_SCORE_TIME, TRUE)
                           ->orderBy(HeroShopDef::SQL_FIELD_UID, TRUE)
                           ->limit($offset, $limit)
                           ->query();
            $ret = array_merge($ret ,$tmpRet);
            $offset += $limit;
            $rowNum = $rowNum - $limit;
            if(count($tmpRet) < $limit)
            {
                break;
            }
        }
        return $ret;
    }
    
    private static function getUserNumByMinScore($minScore,$startTime)
    {
        $data = new CData();
        $ret = $data->selectCount()
                    ->from(self::$tblName)
                    ->where(array(HeroShopDef::SQL_FIELD_SCORE,'>=',$minScore))
                    ->where(array(HeroShopDef::SQL_FIELD_SCORE_TIME,'>=',$startTime))
                    ->query();
        return $ret[0]['count'];
    }
    
    public static function insertShopInfo($arrField)
    {
        $data = new CData();
        $data->insertInto(self::$tblName)
             ->values($arrField)
             ->query();   
    }
    
    public static function updateShopInfo($arrField)
    {
        $data = new CData();
        $data->insertOrUpdate(self::$tblName)
             ->values($arrField)
             ->query();
    }
    
    public static function updatePartShopInfo($uid,$arrField)
    {
        $data = new CData();
        $data->update(self::$tblName)
             ->set($arrField)
             ->where(array(HeroShopDef::SQL_FIELD_UID,'=',$uid))       
             ->query();
    }
    
    public static function getRankByScoreAndTime($score,$scoreTime,$uid,$startTime)
    {
        $data = new CData();
        $tmpRet = $data->selectCount()
                      ->from(self::$tblName)
                      ->where(array(HeroShopDef::SQL_FIELD_SCORE,'>',$score))
                      ->where(array(HeroShopDef::SQL_FIELD_SCORE_TIME,'>=',$startTime))
                      ->query();
        $tmpRet1 = $data->selectCount()
                       ->from(self::$tblName)
                       ->where(array(HeroShopDef::SQL_FIELD_SCORE_TIME,'BETWEEN',array($startTime,$scoreTime-1)))
                       ->where(array(HeroShopDef::SQL_FIELD_SCORE,'=',$score))
                       ->query();
        $tmpRet2 = $data->selectCount()
                       ->from(self::$tblName)
                       ->where(array(HeroShopDef::SQL_FIELD_SCORE_TIME,'=',$scoreTime))
                       ->where(array(HeroShopDef::SQL_FIELD_SCORE,'=',$score))
                       ->where(array(HeroShopDef::SQL_FIELD_UID,'<',$uid))
                       ->query();
        return $tmpRet[0]['count'] + $tmpRet1[0]['count'] + $tmpRet2[0]['count'] + 1;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */