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

/**
 * Class WorldGrouponCrossInfo
 * $crossInfo
 * [
 *  $teamId => [
 *      $goodId => [
 *          teamId, goodId, goodNum, forgeNum, updTime
 *      ]
 *  ],...
 * ]
 */
class WorldGrouponCrossInfo
{
    private static $crossInfo = array();
    private static $crossInfoBak = array();

    private static $Instance;

    public function __construct()
    {
        Logger::trace("WorldGrouponCrossInfo __construct ok.");
    }

    public static function getInstance()
    {
        if(empty(self::$Instance))
        {
            self::$Instance = new self();
        }
        return self::$Instance;
    }

    public static function release()
    {
        if(isset(self::$Instance))
        {
            unset(self::$Instance);
        }
    }

    /**
     * 内部方法，拉取数据的口
     * @param $teamId
     * @return mixed
     */
    private function getTeamInfo($teamId)
    {
        $confObj = WorldGrouponConfObj::getInstance(WorldGrouponField::CROSS);

        if(isset(self::$crossInfo[$teamId]))
        {
            return self::$crossInfo[$teamId];
        }

        $arrGoodConf = $confObj->getConfField(WorldGrouponCsvDef::ARR_GOOD);
        $arrGoodId = array_keys($arrGoodConf);
        self::$crossInfo[$teamId] = WorldGrouponDao::getCrossInfo($teamId, $arrGoodId);

        /**
         * 刷新旧数据
         */
        foreach(self::$crossInfo as $teamId => $tmpTeamInfo)
        {
            foreach($tmpTeamInfo as $tmpGoodId => $tmpGoodInfo)
            {
                if($confObj->getActivityStartTime() > $tmpGoodInfo[WorldGrouponSqlDef::TBL_FIELD_UPD_TIME])
                {
                    Logger::trace("actStartTime:[%d] updTime[%d]", $confObj->getActivityStartTime(), $tmpGoodInfo[WorldGrouponSqlDef::TBL_FIELD_UPD_TIME]);
                    self::$crossInfo[$teamId][$tmpGoodId] = self::getGoodInitInfo($teamId, $tmpGoodId);
                    WorldGrouponDao::rfrOldCrossInfo(self::$crossInfo[$teamId][$tmpGoodId]);
                    Logger::info("WorldGrouponCrossInfo rfrOldCrossInfo teamId:[%d] goodId:[%d] goodNum:[%d] forgeNum:[%d]",
                        $teamId, $tmpGoodId, self::$crossInfo[$teamId][$tmpGoodId][WorldGrouponSqlDef::TBL_FIELD_GOOD_NUM],
                        self::$crossInfo[$teamId][$tmpGoodId][WorldGrouponSqlDef::TBL_FIELD_FORGE_NUM]);
                }
            }
        }

        /**
         * 如果某商品数据是空，初始化数据
         */
        foreach($arrGoodConf as $tmpGoodId => $tmpGoodConf)
        {
            if(empty(self::$crossInfo[$teamId][$tmpGoodId]))
            {
                self::$crossInfo[$teamId][$tmpGoodId] = self::getGoodInitInfo($teamId, $tmpGoodId);
            }
        }

        self::$crossInfoBak = self::$crossInfo;

        return self::$crossInfo[$teamId];
    }

    private function getGoodInitInfo($teamId, $goodId)
    {
        return array(
            WorldGrouponSqlDef::TBL_FIELD_TEAM_ID => $teamId,
            WorldGrouponSqlDef::TBL_FIELD_GOOD_ID => $goodId,
            WorldGrouponSqlDef::TBL_FIELD_GOOD_NUM => 0,
            WorldGrouponSqlDef::TBL_FIELD_FORGE_NUM => 0,
            WorldGrouponSqlDef::TBL_FIELD_UPD_TIME => Util::getTime(),
        );
    }

    public function getTeamInfo4Front($teamId)
    {
        $teamInfo = $this->getTeamInfo($teamId);
        $ret = array();
        foreach($teamInfo as $index => $goodInfo)
        {
            $ret[$index][WorldGrouponSqlDef::TBL_FIELD_GOOD_ID] = $goodInfo[WorldGrouponSqlDef::TBL_FIELD_GOOD_ID];
            $ret[$index][WorldGrouponSqlDef::TBL_FIELD_GOOD_NUM] = $goodInfo[WorldGrouponSqlDef::TBL_FIELD_GOOD_NUM]
                + $goodInfo[WorldGrouponSqlDef::TBL_FIELD_FORGE_NUM];
        }
        return $ret;
    }

    public function getGoodInfoOfTeam($teamId, $goodId)
    {
        $this->getTeamInfo($teamId);
        return self::$crossInfo[$teamId][$goodId];
    }

    public function update()
    {
        foreach(self::$crossInfo as $teamId => $teamInfo)
        {
            foreach($teamInfo as $goodId => $goodInfo)
            {
                if($goodInfo != self::$crossInfoBak[$teamId][$goodId])
                {
                    $incGoodNum = $goodInfo[WorldGrouponSqlDef::TBL_FIELD_GOOD_NUM]
                        - self::$crossInfoBak[$teamId][$goodId][WorldGrouponSqlDef::TBL_FIELD_GOOD_NUM];
                    $incForgeNum = $goodInfo[WorldGrouponSqlDef::TBL_FIELD_FORGE_NUM]
                        - self::$crossInfoBak[$teamId][$goodId][WorldGrouponSqlDef::TBL_FIELD_FORGE_NUM];
                    WorldGrouponDao::updCrossInfo($teamId, $goodId, $incGoodNum, $incForgeNum);
                    Logger::info("WorldGrouponCrossInfo update updCrossInfo teamId:[%d] goodId:[%d] incGoodNum:[%d] incForgeNum:[%d]",
                        $teamId, $goodId, $incGoodNum, $incForgeNum);
                }
            }
        }
        self::$crossInfoBak = self::$crossInfo;
    }

    /**
     * 购买
     * @param $teamId
     * @param $goodId
     * @param $num
     */
    public function buy($teamId, $goodId, $num)
    {
        self::$crossInfo[$teamId][$goodId][WorldGrouponSqlDef::TBL_FIELD_GOOD_NUM] += $num;
    }

    /**
     * 商品数量造假
     * @param $teamId
     * @param $goodId
     * @param $num
     */
    public function forge($teamId, $goodId, $num)
    {
        self::$crossInfo[$teamId][$goodId][WorldGrouponSqlDef::TBL_FIELD_FORGE_NUM] += $num;
    }

    public function getGoodNum($teamId, $goodId)
    {
        $goodInfoOfTeam = $this->getGoodInfoOfTeam($teamId, $goodId);
        return $goodInfoOfTeam[WorldGrouponSqlDef::TBL_FIELD_GOOD_NUM];
    }

    public function getForgeNum($teamId, $goodId)
    {
        $goodInfoOfTeam = $this->getGoodInfoOfTeam($teamId, $goodId);
        return $goodInfoOfTeam[WorldGrouponSqlDef::TBL_FIELD_FORGE_NUM];
    }

    /**
     * 获得当前某个物品的价格
     * @param $teamId
     * @param $goodId
     * @return int
     */
    public function getCurPriceOfGoodId($teamId, $goodId)
    {
        $goodNum = $this->getGoodNum($teamId, $goodId);
        $forgeNum = $this->getForgeNum($teamId, $goodId);
        Logger::trace("WorldGrouponCrossInfo getCurPriceOfGoodId goodNum:[%d] forgeNum:[%d]", $goodNum, $forgeNum);
        $goodNum += $forgeNum;

        $confObj = WorldGrouponConfObj::getInstance();
        $goodConf = $confObj->getGoodConfById($goodId);

        $price = $goodConf[WorldGrouponCsvDef::PRICE];
        //折扣
        $discount = UNIT_BASE;
        $discountConf = $goodConf[WorldGrouponCsvDef::DISCOUNT];
        foreach($discountConf as $dis => $groupNum)
        {
            if($goodNum >= $groupNum && $discount >= $dis)
            {
                $discount = $dis;
            }
        }
        $curPrice = intval($price * $discount / UNIT_BASE);
        Logger::trace("curPrice:[%d]", $curPrice);

        return $curPrice;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */