<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: TopupRewardManager.class.php 124711 2014-08-05 06:46:01Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/topupreward/TopupRewardManager.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-08-05 06:46:01 +0000 (Tue, 05 Aug 2014) $$
 * @version $$Revision: 124711 $$
 * @brief 
 *  
 **/

/**
 * $topupReward:array
 * [
 *  uid:int 玩家id
 *  va_data:array
 *      [
 *          'topup' =>
 *          [
 *              0(活动天数)=> array
 *              [
 *                  0=>int 是否可领取奖励
 *                  1=>int 奖励是否已领取
 *              ]
 *          ]
 *          'lasttime' => int 活动结束时间
 *      ]
 * ]
 */
class TopupRewardManager
{
    private static $uid = 0;
    private $day = 0; //活动第X天

    private $topupReward = NULL;
    private $topupRewardBuffer = NULL;
    private static $instance = NULL;

    private function __construct($uid=0)
    {
        if(!EnActivity::isOpen(ActivityName::TOPUPREWARD))
        {
            throw new FakeException('activity topupReward not open');
        }
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        self::$uid = $uid;
        $this->day = EnActivity::getActivityDay(ActivityName::TOPUPREWARD);
        $this->init(self::$uid);
        $this->repairData();
    }

    public static function getInstance($uid)
    {
        if(self::$uid != 0 && $uid != self::$uid)
        {
            throw new FakeException(' invalid uid:%d, self::$uid:%d ', $uid, self::$uid);
        }
        if(empty(self::$instance))
        {
            self::$instance = new self($uid);
        }
        return self::$instance;
    }

    private function init($uid)
    {
        $topupReward = TopupRewardDao::loadData($uid);
        //如果数据还是上次活动的就数据, 就清空
        if(isset($topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::LASTTIME]) &&
            $topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::LASTTIME] < TopupRewardLogic::getActStartTime())
        {
            $topupReward = array(
                TopupRewardDef::UID => $uid,
                TopupRewardDef::VA_DATA => $this->initRewardInfo()
            );
            TopupRewardDao::updateData($topupReward, $uid);
        }
        if(empty($topupReward))
        {
            $topupReward = array(
                TopupRewardDef::UID => $uid,
                TopupRewardDef::VA_DATA => $this->initRewardInfo()
            );
            TopupRewardDao::insertData($topupReward);
        }
        $this->topupReward = $topupReward;
        $this->topupRewardBuffer = $topupReward;
    }

    private function initRewardInfo()
    {
        $arrData = array();
        $topup = array();
        for($i = 0; $i <= $this->day; $i++)
        {
            $topup[$i] = array(TopupRewardDef::CANREC => $this->checkIfCanRec($this->day),
                TopupRewardDef::ISREC => TopupRewardDef::ISRECNO);
        }
        $arrData[TopupRewardDef::TOPUP] = $topup;
        $arrData[TopupRewardDef::LASTTIME] = Util::getTime();
        return $arrData;
    }

    private function checkIfCanRec($day)
    {
        $conf = $this->getTopupConf();
        $sumDayPay = $this->getSumDayPay($day);
        if(!isset($conf['data'][$day + 1][ContinuePayCsv::PAYNUM]))
        {
            throw new ConfigException('config error, %d have no data', $day + 1);
        }
        $dayPayNum = $conf['data'][$day + 1][ContinuePayCsv::PAYNUM];
        return $sumDayPay - $dayPayNum >= 0 ? TopupRewardDef::CANRECYES : TopupRewardDef::CANRECNO;
    }

    //某天充了多少钱
    private function getSumDayPay($day)
    {
        $conf = $this->getTopupConf();
        $startTime = $conf['start_time'];
        $firstDayTime = intval(strtotime(date('Y-m-d', $startTime)));
        $dayStartTime = $firstDayTime + $day * 86400;
        $dayEndTime = $dayStartTime + 86400 - 1;
        //比较某天充值是否达标
        $sumDayPay = Enuser::getRechargeGoldByTime($dayStartTime, $dayEndTime, self::$uid);
        return $sumDayPay;
    }

    private function repairData()
    {
        if(empty($this->topupReward))
        {
            throw new InterException(' topupReward is empty ');
        }
        //奖励数组补全
        $num = count($this->topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::TOPUP]);
        Logger::debug('num:%d, day:%d', $num, $this->day);
        if($num < $this->day + 1)
        {
            for($i = $num; $i < $this->day + 1; $i++)
            {
                $this->topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::TOPUP][$i] = array(TopupRewardDef::CANREC => $this->checkIfCanRec($i),
                    TopupRewardDef::ISREC => TopupRewardDef::ISRECNO);
            }
        }
        //修正昨天和今天的是否可购买状态
        if($this->day - 1 >= 0)
        {
            if($this->topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::TOPUP][$this->day - 1][TopupRewardDef::ISREC] == TopupRewardDef::ISRECNO)
            {
                $this->topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::TOPUP][$this->day - 1][TopupRewardDef::CANREC] = $this->checkIfCanRec($this->day - 1);
            }
        }
        if($this->topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::TOPUP][$this->day][TopupRewardDef::ISREC] == TopupRewardDef::ISRECNO)
        {
            $this->topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::TOPUP][$this->day][TopupRewardDef::CANREC] = $this->checkIfCanRec($this->day);
        }

        if(!Util::isSameDay($this->topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::LASTTIME]))
        {
            $this->topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::LASTTIME] = Util::getTime();
        }
    }

    public function noneRecReward2Center()
    {
        if($this->day - 1 >= 0)
        {
            $reward = array();
            for($i = 0; $i < $this->day; $i++)
            {
                if($this->topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::TOPUP][$i][TopupRewardDef::CANREC] == TopupRewardDef::CANRECNO)
                {
                    continue;
                }
                if($this->topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::TOPUP][$i][TopupRewardDef::ISREC] == TopupRewardDef::ISRECYES)
                {
                    continue;
                }
                $this->topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::TOPUP][$i][TopupRewardDef::ISREC] = TopupRewardDef::ISRECYES;
                $conf = $this->getTopupConf();
                $reward[] = $conf['data'][$i+1][ContinuePayCsv::PAYREWARD];

            }
            $this->update();
            Logger::info('send yesterday topup reward to center. uid:%d, reward:%s', self::$uid, $reward);
            RewardUtil::reward3DtoCenter(self::$uid, $reward, RewardSource::TOPUP_REWARD);
        }
    }

    public function update()
    {
        if($this->topupReward != $this->topupRewardBuffer)
        {
            TopupRewardDao::updateData($this->topupReward, self::$uid);
            Logger::trace('TopupReward:%s update', $this->topupReward);
        }
        $this->topupRewardBuffer = $this->topupReward;
        return true;
    }

    //对外
    public function getInfo()
    {
        $info = array();
        $info['data'] = $this->topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::TOPUP];
        $info['day'] = $this->day;
        $info['gold'] = $this->getSumDayPay($this->day);
        return $info;
    }

    public function rec($day)
    {
        if($this->checkIfCanRec($day) == TopupRewardDef::CANRECNO)
        {
            throw new FakeException(' top-up pay not reached ');
        }
        if($this->topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::TOPUP][$day][TopupRewardDef::ISREC] == TopupRewardDef::ISRECYES)
        {
            throw new FakeException(' have rec reward already ');
        }
        $this->topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::TOPUP][$day][TopupRewardDef::ISREC] = TopupRewardDef::ISRECYES;

        $conf = $this->getTopupConf();
        $reward = $conf['data'][$day + 1][ContinuePayCsv::PAYREWARD];
        $res = RewardUtil::reward3DArr(self::$uid, $reward, StatisticsDef::ST_FUNCKEY_TOPUP_REWARD);
        Logger::debug('TopupRewardManager rec reward:%s', $reward);
        return $res;
    }

    private function getTopupConf()
    {
        $conf = EnActivity::getConfByName(ActivityName::TOPUPREWARD);
        if(empty($conf))
        {
           throw new InterException(' activity topupReward is empty ');
        }
        return $conf;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */