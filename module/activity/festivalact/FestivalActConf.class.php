<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FestivalManager.class.php 153269 2015-01-19 02:09:56Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/module/activity/festival/FestivalManager.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-01-19 10:09:56 +0800 (星期一, 19 一月 2015) $
 * @version $Revision: 153269 $
 * @brief 
 *  
 **/
class FestivalActConf
{
    // 唯一实例
    private static $sInstance = NULL;

    // 缓存配置
    private $conf = array();
    private $curDay = 0;
    private $curPeriod = 0;

    // 构造函数
    function __construct()
    {
        // 获取数据库中配置
        $activityConf = EnActivity::getConfByName(ActivityName::FESTIVAL_ACT);
        $activityRewardConf = EnActivity::getConfByName(ActivityName::FESTIVALACT_REWARD);

        // 检查活动是否开启 - 这里和FestivalAct.class做了重复的工作 不过保国说保留吧 保险点好
        if (!EnActivity::isOpen(ActivityName::FESTIVAL_ACT))
        {
            throw new FakeException('Act festivalAct is not open.');
        }

        // 两个配置时间需要一致
        if ($activityConf['start_time'] != $activityRewardConf['start_time'])
        {
            throw new FakeException('festivalAct and festivalActReward start time:%s, %s is not consistent.',
                $activityConf['start_time'], $activityRewardConf['start_time']);
        }

        // 将两个表拼成一个
        $this->conf = self::composeConf($activityConf, $activityRewardConf);
        // 当前天数
        $this->curDay = EnActivity::getActivityDay(ActivityName::FESTIVAL_ACT) + 1;
        // 当前季度
        $this->curPeriod = self::countCurPeriod($this->conf);

        Logger::debug("FestivalAct conf is:%s.", $this->conf);
    }

    // 取配置实例
    public static function getInstance()
    {
        if (!isset(self::$sInstance))
        {
            self::$sInstance = new self();
        }
        return self::$sInstance;
    }

    // 计算当前所在季度
    public static function countCurPeriod($conf)
    {
        // 先得到当前时间
        $curTime = Util::getTime();
        foreach ($conf['data'][FestivalActDef::VA_PERIOD] as $periodID => $info)
        {
            if ($curTime >= $info[FestivalActDef::START_TIME] && $curTime <= $info[FestivalActDef::END_TIME])
            {
                return $periodID;
            }
        }
        // 啥也没查找
        return 0;
    }

    // 检查活动是否开启
    public function isValid()
    {
        if (empty($this->conf))
        {
            return false;
        }
        return true;
    }

    // 要是为空就报错
    public function checkConf()
    {
        if (empty($this->conf))
        {
            throw new FakeException('FestivalAct conf is empty!');
        }
    }

    // 取当前活动配置的商品
    public function getConf()
    {
        return $this->conf;
    }

    // 光要后端数据
    public function getBakInfo()
    {
        return $this->conf['data'];
    }

    public function checkOpen()
    {
        // 先看活动是否存在
        if (!$this->isValid())
        {
            throw new FakeException('Act festivalact is not exist.');
        }
        $now = Util::getTime();
        // 和策划确认结束时间
		if( $now < $this->conf['start_time'] || $now > $this->conf['end_time'])
		{
			throw new FakeException('Act festivalact is not open (%s, %s).', $this->conf['start_time'], $this->conf['end_time']);
		}
        else if (strtotime(GameConf::SERVER_OPEN_YMD.GameConf::SERVER_OPEN_TIME) > $this->conf['need_open_time'])
        {
            throw new FakeException('serverOpen %s is not open festivalact.', GameConf::SERVER_OPEN_YMD.GameConf::SERVER_OPEN_TIME);
        }
    }

    public function getActStartTime()
    {
       return $this->conf['start_time'];
    }
    
    public function getActEndTime()
    {
        return $this->conf['end_time'];
    }

    public function getCurDay()
    {
        return $this->curDay;
    }

    // 遍历当前季度的登陆任务，得到今天的登陆任务ID
    public function getCurDayLoginTaskID()
    {
        // 今天是活动开始第几天
        $curDay = $this->curDay;
        // 今天属于哪个季度
        $curPeriod = $this->curPeriod;
        // 按照季度遍历，找到就返回
        foreach ($this->conf['data'][FestivalActDef::VA_PERIOD] as $periodID => $periodInfo)
        {
            // 不是当前季度就不进行遍历啦
            if ($periodID != $curPeriod)
            {
                continue;
            }
            foreach ($periodInfo[FestivalActDef::MISSION] as $misID => $misInfo)
            {
                if ($misInfo[FestivalActDef::BIGTYPE] == FestivalActDef::ACT_TYPE_TASK
                    && $misInfo[FestivalActDef::TYPE_ID] == FestivalActDef::TASK_LOGIN)
                {
                    if ($misInfo[FestivalActDef::NEED] == $curDay)
                    {
                        return $misID;
                    }
                }
            }
        }
        return 0;
    }

    // 得到兑换固定任务ID的特定字段的值
    public function getExchangeMisKey($misID, $key)
    {
        if (!isset($this->conf['data'][FestivalActDef::VA_EXCHANGE][$misID]))
        {
            throw new FakeException('err misID:%s.', $misID);
        }
        if (!isset($this->conf['data'][FestivalActDef::VA_EXCHANGE][$misID][$key]))
        {
            throw new FakeException('err misID:%s key:%s.', $misID, $key);
        }
        return $this->conf['data'][FestivalActDef::VA_EXCHANGE][$misID][$key];
    }

    // 得到某个季度固定任务ID的特定字段的值
    public function getPeriodMisKey($periodID, $misID, $key)
    {
        if (!isset($this->conf['data'][FestivalActDef::VA_PERIOD][$periodID][FestivalActDef::MISSION][$misID]))
        {
            throw new FakeException('err misID:%s.', $misID);
        }
        if (!isset($this->conf['data'][FestivalActDef::VA_PERIOD][$periodID][FestivalActDef::MISSION][$misID][$key]))
        {
            throw new FakeException('err misID:%s key:%s.', $misID, $key);
        }
        return $this->conf['data'][FestivalActDef::VA_PERIOD][$periodID][FestivalActDef::MISSION][$misID][$key];
    }

    // 得到某个任务ID所在的季度
    public function getMisIDOfPeriod($misID)
    {
        foreach ($this->conf['data'][FestivalActDef::VA_PERIOD] as $periodId => $periodInfo)
        {
            if (key_exists($misID, $periodInfo[FestivalActDef::MISSION]))
            {
                return $periodId;
            }
        }
        return 0;
    }

    // 查看所给时间属于哪个季度
    public function getTimeInPeriod($time)
    {
        foreach ($this->conf['data'][FestivalActDef::VA_PERIOD] as $periodId => $periodInfo)
        {
            if ($time >= $periodInfo[FestivalActDef::START_TIME] && $time <= $periodInfo[FestivalActDef::END_TIME])
            {
                return $periodId;
            }
        }
        // 不在任何阶段
        return -1;
    }

    // 将两个配置合成一个
    public function composeConf($activityConf, $activityRewardConf)
    {
        // 存放一共出现过的任务ID
        $missionIDArr = array();
        // 新数据
        $newdata = array();
        // 先按季度
        foreach ($activityConf['data'] as $period => $data)
        {
            $newdata[FestivalActDef::VA_PERIOD][$period] = $data;
            // 新的data
            $newMisArr = array();
            foreach ($data[FestivalActDef::MISSION] as $missionID)
            {
                // 奖励里必须有这个类型
                if (!isset($activityRewardConf['data'][$missionID]))
                {
                    throw new ConfigException('FestivalAct id:%s missionID:%s not reward info.', $period, $missionID);
                }
                // 一个任务不能在两个季度里都有，因为前端红点的数据是只按ID不按季度的
                if (isset($missionIDArr[$missionID])
                    && $activityRewardConf['data'][$missionID][FestivalActDef::BIGTYPE] != FestivalActDef::ACT_TYPE_EXCHANGE)
                {
                    throw new ConfigException('FestivalAct missionID:%s is both in two period, conf:%s.', $missionID, $activityRewardConf['data'][$missionID]);
                }
                $missionIDArr[$missionID] = 0;
                // 兑换的话要单拿出来
                if ($activityRewardConf['data'][$missionID][FestivalActDef::BIGTYPE] == FestivalActDef::ACT_TYPE_EXCHANGE)
                {
                    $newdata[FestivalActDef::VA_EXCHANGE][$missionID] = $activityRewardConf['data'][$missionID];
                }
                else
                {
                    $newMisArr[$missionID] = $activityRewardConf['data'][$missionID];
                }
            }
            $newdata[FestivalActDef::VA_PERIOD][$period][FestivalActDef::MISSION] = $newMisArr;
        }
        $activityConf['data'] = $newdata;
        Logger::debug('composeConf ret activityConf:%s.', $activityConf);
        return $activityConf;
    }

    // 计算当前时间是配置的什么季度
    public function getCurPeriod()
    {
        return $this->curPeriod;
    }

    /**
     * 得到兑换配置信息
     */
    public function getMisArrByExchange()
    {
        $ret = array();
        // 兑换的
        foreach ($this->conf['data'][FestivalActDef::VA_EXCHANGE] as $misID => $misInfo)
        {
            $ret[] = $misID;
        }
        Logger::debug('getMisArrByExchange ret:%s.', $ret);
        return $ret;
    }

    /**
     * 得到任务按季度大类划分的配置信息
     */
    public function getMisArrPartByPeriodBigType()
    {
        $ret = array();
        foreach ($this->conf['data'][FestivalActDef::VA_PERIOD] as $periodID => $info)
        {
            $ret[$periodID] = array();
            // 把任务过一遍
            foreach ($info[FestivalActDef::MISSION] as $misID => $misInfo)
            {
                $big_type = $misInfo[FestivalActDef::BIGTYPE];
                $ret[$periodID][$big_type][] = $misID;               
            }
        }
        Logger::debug('getPeriodBigTypeData ret:%s.', $ret);
        return $ret;
    }

    /**
     * 检查任务是不是当前季度的
     */
    public function checkMisPeriod($bigType, $misID)
    {
        // 检查还在不在季度活动范围
        $periodID = $this->getCurPeriod();
        if ($periodID == 0)
        {
            throw new FakeException('act time is over!');
        }
        // 看看是不是这个季度的任务
        $misIdOfPeriodID = $this->getMisIDOfPeriod($misID);
        if ($misIdOfPeriodID != $periodID)
        {
            throw new FakeException('period:%s time is over, now is %s!', $misIdOfPeriodID, $periodID);
        }
        return $periodID;
    }

    // 更新下session
    public static function refrehSess()
    {
        $actRet = EnActivity::getConfByName(ActivityName::FESTIVAL_ACT);
        $curTime = Util::getTime();
        $setArr = self::setSess($curTime, $actRet['start_time'], $actRet['end_time'], array());
        Logger::debug('setsession not empty:%s', $setArr);
        return $setArr; 
    }

    // 设置session
    public static function setSess($setTime, $startTime, $endTime, $data)
    {
        $setArr = array(
            FestivalActSessionField::SET_TIME => $setTime,
            FestivalActSessionField::START_TIME => $startTime,
            FestivalActSessionField::END_TIME => $endTime,
            FestivalActSessionField::LITTLE_DATA => $data,
        );
        RPCContext::getInstance()->setSession(FestivalActDef::SESSI, $setArr);
        return $setArr;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */