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
class WorldGrouponUtil
{

    /**
     * 根据serverId获得teamId
     * @param $serverId
     * @return array
     */
    public static function getTeamIdByServerId($serverId)
    {
        $confObj = WorldGrouponConfObj::getInstance();
        $periodBgnTime = $confObj->getPeriodBgnTime();

        $arrField = array(
            WorldGrouponSqlDef::TBL_FIELD_TEAM_ID,
            WorldGrouponSqlDef::TBL_FIELD_SERVER_ID,
        );
        $arrConf = array(
            array(WorldGrouponSqlDef::TBL_FIELD_SERVER_ID, '=', $serverId),
            array(WorldGrouponSqlDef::TBL_FIELD_UPDATE_TIME, '>=', $periodBgnTime),
        );
        $teamInfo = WorldGrouponDao::selectTeamInfo($arrConf, $arrField);
        return empty($teamInfo) ? 0 : $teamInfo[0][WorldGrouponSqlDef::TBL_FIELD_TEAM_ID];
    }

    /**
     * 得到所有的teamId
     * @return int
     */
    public static function getAllTeamId()
    {
        $confObj = WorldGrouponConfObj::getInstance();
        $periodBgnTime = $confObj->getPeriodBgnTime();

        $arrField = array(
            WorldGrouponSqlDef::TBL_FIELD_TEAM_ID,
            WorldGrouponSqlDef::TBL_FIELD_SERVER_ID,
        );
        $arrConf = array(
            array(WorldGrouponSqlDef::TBL_FIELD_UPDATE_TIME, '>=', $periodBgnTime),
        );
        $teamInfo = WorldGrouponDao::selectTeamInfo($arrConf, $arrField);
        return Util::arrayIndex($teamInfo, WorldGrouponSqlDef::TBL_FIELD_SERVER_ID);
    }

    /**
     * 获得跨服库的db名称
     *
     * @return string
     */
    public static function getCrossDbName()
    {
        return WorldGrouponConf::WORLD_GROUPON_CROSS_DB_PREFIX . PlatformConfig::PLAT_NAME;
    }

    /**
     * 获得分组信息
     *
     * @return array
     */
    public static function getAllTeamInfo()
    {
        $confObj = WorldGrouponConfObj::getInstance(WorldGrouponField::CROSS);
        $periodBgnTime = $confObj->getPeriodBgnTime();
        $arrField = array
        (
            WorldGrouponSqlDef::TBL_FIELD_TEAM_ID,
            WorldGrouponSqlDef::TBL_FIELD_SERVER_ID,
        );
        $arrCond = array
        (
            array(WorldGrouponSqlDef::TBL_FIELD_UPDATE_TIME, '>=', $periodBgnTime),
        );
        $allTeamInfo = WorldGrouponDao::selectTeamInfo($arrCond, $arrField);

        $arrRet = array();
        foreach ($allTeamInfo as $aInfo)
        {
            $aTeamId = $aInfo[WorldGrouponSqlDef::TBL_FIELD_TEAM_ID];
            $aServerId = $aInfo[WorldGrouponSqlDef::TBL_FIELD_SERVER_ID];
            if (!isset($arrRet[$aTeamId]))
            {
                $arrRet[$aTeamId] = array();
            }
            $arrRet[$aTeamId][] = $aServerId;
        }

        return $arrRet;
    }

}


class WorldGrouponConfObj
{
    /**
     * 唯一实例
     */
    private static $Instance = NULL;

    /**
     * 结束时间
     * @var int
     */
    private $mEndTime;

    /**
     * 开始时间
     * @var int
     */
    private $mStartTime;

    /**
     * 具体配置数据
     * @var array
     */
    private $mConf;

    public static function getInstance($field = WorldGrouponField::INNER)
    {
        if(empty(self::$Instance))
        {
           self::$Instance = new self($field);
        }
        return self::$Instance;
    }

    /**
     * 释放实例
     */
    public static function release()
    {
        if(isset(self::$Instance))
        {
            unset(self::$Instance);
        }
    }

    public function __construct($field)
    {
        if ($field == WorldArenaField::CROSS)
        {
            $activityConf = ActivityConfLogic::getConf4Backend(ActivityName::WORLDGROUPON, 0);
        }
        else
        {
            $activityConf = EnActivity::getConfByName(ActivityName::WORLDGROUPON);
        }
        Logger::trace('WorldGrouponConfObj raw conf[%s]', $activityConf);

        $this->mStartTime = floor($activityConf['start_time'] / 60) * 60;
        $this->mEndTime = floor($activityConf['end_time'] / 60) * 60;

        if (empty($activityConf['data']))
        {
            if ($this->mStartTime > 0)
            {
                throw new ConfigException('WorldGrouponConfObj.construct failed, no data in activityConf[%s]', $activityConf);
            }
            Logger::info('WorldGrouponConfObj.construct failed, empty activityConf[%s]', $activityConf);
        }
        else
        {
            $this->mConf = $activityConf['data'];
        }
        Logger::trace('WorldGrouponConfObj cur conf[%s]', $this->mConf);
    }

    /**
     * 活动开始时间
     * @return int
     */
    public function getActivityStartTime()
    {
        return $this->mStartTime;
    }

    /**
     * 活动结束时间
     */
    public function getActivityEndTime()
    {
        return $this->mEndTime;
    }

    /**
     * 当前是活动的第几天 从0开始
     */
    public function getDayOfActivity($time = 0)
    {
        if(empty($time))
        {
            $time = Util::getTime();
        }

        $startTime = $this->getActivityStartTime();
        $endTime = $this->getActivityEndTime();
        if($time < $startTime || $time > $endTime)
        {
            return -1;
        }

        $firstDayTime = intval(strtotime(date('Y-m-d', $startTime)));

        $secondsDuration = $time - $firstDayTime;
        $days = intval( $secondsDuration / 86400 );

        return $days;
    }

    public function isValid($time = 0)
    {
        if($time == 0)
        {
            $time = Util::getTime();
        }

        if(Util::isInCross() == false && EnActivity::isOpen(ActivityName::WORLDGROUPON) == false)
        {
            return false;
        }

        if($time < $this->mStartTime || $time > $this->mEndTime
            || $this->mStartTime == 0 || $this->mEndTime == 0)
        {
            return false;
        }

        return true;
    }

    /**
     * 获取每一个字段的配置
     * @param $field
     * @return mixed
     */
    public function getConfField($field)
    {
        return $this->mConf[$field];
    }

    /**
     * 获取某一个商品的配置
     */
    public function getGoodConfById($goodId)
    {
        $arrGoodConf = $this->getConfField(WorldGrouponCsvDef::ARR_GOOD);
        if(empty($arrGoodConf[$goodId]))
        {
            throw new ConfigException("no conf for goodId:[%d]", $goodId);
        }
        return $arrGoodConf[$goodId];
    }

    public function getExtraConf()
    {
        return $this->getConfField(WorldGrouponCsvDef::EXTRA);
    }

    public function getNeedOpenDays()
    {
        $extra = $this->getExtraConf();
        return $extra[WorldGrouponCsvDef::NEED_DAY];
    }

    /**
     * 根据参数给出的时间，获取每个活动周期开始的时间
     * @param int $time
     * @return int
     */
    public function getPeriodBgnTime($time = 0)
    {
        if(WorldGrouponConf::$TEST_MODE > 0)
        {
            $hour = date("H", empty($time) ? Util::getTime() : $time);
            return strtotime(date('Y-m-d H:', (empty($time) ? Util::getTime() : $time)) . '00:00')
                - ((WorldGrouponConf::$TEST_MODE + $hour % 2) % 2 * 3600);
        }

        return $this->mStartTime;
    }

    /**
     * 根据参数给出的时间，获取每个活动周期结束时间
     * @param int $time
     * @return int
     */
    public function getPeriodEndTime($time = 0)
    {
        if (WorldGrouponConf::$TEST_MODE > 0)
        {
            return $this->getPeriodBgnTime($time) + 2 * 3600; // 120分钟
        }

        return $this->mEndTime;
    }

    /**
     * 获取购买阶段开始时间
     * @param int $time
     * @return int
     */
    public function getBuyBgnTime($time = 0)
    {
        if (WorldGrouponConf::$TEST_MODE > 0)
        {
            return $this->getPeriodBgnTime($time) + WorldGrouponConf::$TEST_OFFSET[0];
        }

        $extra = $this->getConfField(WorldGrouponCsvDef::EXTRA);
        return $this->getPeriodBgnTime($time) + $extra[WorldGrouponCsvDef::TIME_CFG][0];
    }

    /**
     * 获取购买阶段结束时间
     * @param int $time
     * @return int
     */
    public function getBuyEndTime($time = 0)
    {
        if (WorldGrouponConf::$TEST_MODE > 0)
        {
            return $this->getPeriodBgnTime($time) + WorldGrouponConf::$TEST_OFFSET[1];
        }

        $extra = $this->getConfField(WorldGrouponCsvDef::EXTRA);
        return $this->getPeriodBgnTime($time) + $extra[WorldGrouponCsvDef::TIME_CFG][1];
    }

    public function getStage($time = 0)
    {
        if (!$this->isValid($time))
        {
            return WorldGrouponDef::STAGE_INVALID;
        }

        if($time == 0)
        {
            $time = Util::getTime();
        }

        if($time < $this->getBuyBgnTime($time))
        {
            return WorldGrouponDef::STAGE_TEAM;
        }
        if($time < $this->getBuyEndTime($time))
        {
            return WorldGrouponDef::STAGE_BUY;
        }

        return WorldGrouponDef::STAGE_REWARD;
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */