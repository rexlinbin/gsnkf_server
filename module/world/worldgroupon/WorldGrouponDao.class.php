<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(ShijieHan@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
class WorldGrouponDao
{
    /**
     * 获得所有分组信息
     * @param $arrCond
     * @param $arrField
     * @return array
     */
    public static function selectTeamInfo($arrCond, $arrField)
    {
        $arrRet = array();

        $offset = 0;
        for ($i = 0; $i < 1024; ++$i)
        {
            $data = new CData();
            $data->useDb(WorldGrouponUtil::getCrossDbName());
            $data->select($arrField)
                ->from(WorldGrouponSqlDef::WORLD_GROUPON_CROSS_TEAM)
                ->limit($offset, DataDef::MAX_FETCH);
            foreach ($arrCond as $cond)
            {
                $data->where($cond);
            }
            $ret = $data->query();
            $arrRet = array_merge($arrRet, $ret);
            if (count($ret) < DataDef::MAX_FETCH)
            {
                break;
            }
            $offset += DataDef::MAX_FETCH;
        }

        return $arrRet;
    }

    /**
     * 更新分组
     * @param $arrField
     * @return bool
     */
    public static function insertTeamInfo($arrField)
    {
        $data = new CData();
        $data->useDb(WorldGrouponUtil::getCrossDbName());
        $ret = $data->insertOrUpdate(WorldGrouponSqlDef::WORLD_GROUPON_CROSS_TEAM)
            ->values($arrField)
            ->query();
        if($ret['affected_rows'] == 0)
        {
            return false;
        }
        return true;
    }

    /**
     * 获取跨服团购数据
     * @param $teamId int 组id
     * @param $arrGoodId array 商品id数组
     * @return array
     */
    public static function getCrossInfo($teamId, $arrGoodId)
    {
        $data = new CData();
        $data->useDb(WorldGrouponUtil::getCrossDbName());
        $data->select(WorldGrouponSqlDef::$CROSS_INFO_ALL_FIELD)
            ->from(WorldGrouponSqlDef::WORLD_GROUPON_CROSS_INFO)
            ->where(array(WorldGrouponSqlDef::TBL_FIELD_TEAM_ID, '=', $teamId));
        if(!empty($arrGoodId))
        {
            $data->where(array(WorldGrouponSqlDef::TBL_FIELD_GOOD_ID, 'IN', $arrGoodId));
        }
        $ret = $data->query();
        return Util::arrayIndex($ret, WorldGrouponSqlDef::TBL_FIELD_GOOD_ID);
    }

    public static function getCrossInfo4Plat()
    {
        $confObj = WorldGrouponConfObj::getInstance();
        $startTime = $confObj->getActivityStartTime();

        $data = new CData();
        $data->useDb(WorldGrouponUtil::getCrossDbName());
        $ret = $data->select(WorldGrouponSqlDef::$CROSS_INFO_4_PLAT)
            ->from(WorldGrouponSqlDef::WORLD_GROUPON_CROSS_INFO)
            ->where(array(WorldGrouponSqlDef::TBL_FIELD_UPD_TIME, '>=', $startTime))
            ->query();
        return $ret;
    }

    public static function updCrossInfo4Plat($teamId, $goodId, $forgeNum)
    {
        self::updCrossInfo($teamId, $goodId, 0, $forgeNum);
    }

    public static function updCrossInfo($teamId, $goodId, $incGoodNum, $incForgeNum)
    {
        $data = new CData();
        $data->useDb(WorldGrouponUtil::getCrossDbName());
        $arrInc = array(
            WorldGrouponSqlDef::TBL_FIELD_TEAM_ID => $teamId,
            WorldGrouponSqlDef::TBL_FIELD_GOOD_ID => $goodId,
            WorldGrouponSqlDef::TBL_FIELD_GOOD_NUM => new IncOperator($incGoodNum),
            WorldGrouponSqlDef::TBL_FIELD_FORGE_NUM => new IncOperator($incForgeNum),
            WorldGrouponSqlDef::TBL_FIELD_UPD_TIME => Util::getTime(),
        );
        $arrRet = $data->insertOrUpdate(WorldGrouponSqlDef::WORLD_GROUPON_CROSS_INFO)
            ->values($arrInc)
            ->query();
        if ($arrRet['affected_rows'] == 0)
        {
            return false;
        }
        return true;
    }

    public static function rfrOldCrossInfo($arrColumn)
    {
        $data = new CData();
        $data->useDb(WorldGrouponUtil::getCrossDbName());
        $arrRet = $data->insertOrUpdate(WorldGrouponSqlDef::WORLD_GROUPON_CROSS_INFO)
            ->values($arrColumn)
            ->query();
        if ($arrRet['affected_rows'] == 0)
        {
            return false;
        }
        return true;
    }

    /**
     *拉取玩家个人团购数据
     */
    public static function getInnerUserInfo($uid)
    {
        $data = new CData();
        $ret = $data->select(WorldGrouponSqlDef::$INNER_USER_ALL_FIELD)
            ->from(WorldGrouponSqlDef::WORLD_GROUPON_INNER_USER)
            ->where(array(WorldGrouponSqlDef::TBL_FIELD_UID, '=', $uid))
            ->query();
        if(empty($ret))
        {
            return array();
        }
        return $ret[0];
    }

    /**
     * 更新玩家团购数据
     */
    public static function updInnerUserInfo($arrField)
    {
        $data = new CData();
        $arrRet = $data->insertOrUpdate(WorldGrouponSqlDef::WORLD_GROUPON_INNER_USER)
            ->values($arrField)
            ->query();
        if ($arrRet['affected_rows'] == 0)
        {
            return false;
        }
        return true;
    }

    /**
     * 查询出所有参加本次活动的玩家
     * @return array
     */
    public static function selectAllInnerUserInfo()
    {
        $confObj = WorldGrouponConfObj::getInstance();
        $startTime = $confObj->getActivityStartTime();

        $arrRet = array();
        $offset = 0;
        for($i = 0; $i < 1024; ++$i)
        {
            $data = new CData();
            $ret = $data->select(WorldGrouponSqlDef::$INNER_USER_ALL_FIELD)
                ->from(WorldGrouponSqlDef::WORLD_GROUPON_INNER_USER)
                ->where(array(WorldGrouponSqlDef::TBL_FIELD_OPTIME, '>=', $startTime))
                ->limit($offset, DataDef::MAX_FETCH)
                ->orderBy(WorldGrouponSqlDef::TBL_FIELD_UID, true)
                ->query();
            $arrRet = array_merge($arrRet, $ret);
            if(count($ret) < DataDef::MAX_FETCH)
            {
                break;
            }
            $offset += DataDef::MAX_FETCH;
        }

        return $arrRet;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */