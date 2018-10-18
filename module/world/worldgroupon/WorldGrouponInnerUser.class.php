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
 * Class WorldGrouponInnerUser
 * [
 *  uid => int
 *  point => int
 *  coupon => int
 *  optime => int
 *  va_info => array[
 *      his => array[
 *          {goodid, num, gold, coupon, buytime}, ...
 *      ]
 *      point_reward => array[
 *          rewardid, ...
 *      ]
 *  ]
 * ]
 */
class WorldGrouponInnerUser
{
    private $uid = NULL;
    private $innerUser = array();
    private $innerUserBak = array();

    private static $Instance = array();

    public function __construct($uid)
    {
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        $this->uid = $uid;
        $this->loadData();
        $this->refresh();
    }

    public static function getInstance($uid)
    {
        if(empty(self::$Instance[$uid]))
        {
            self::$Instance[$uid] = new self($uid);
        }
        return self::$Instance[$uid];
    }

    public function release()
    {
        if(isset(self::$Instance))
        {
            unset(self::$Instance);
        }
    }

    private function loadData()
    {
        $confObj = WorldGrouponConfObj::getInstance();
        $data = WorldGrouponDao::getInnerUserInfo($this->uid);
        /**
         * 数据empty或者是新活动
         */
        if(empty($data) || ( isset($data[WorldGrouponSqlDef::TBL_FIELD_OPTIME])
                && $confObj->getActivityStartTime() > $data[WorldGrouponSqlDef::TBL_FIELD_OPTIME]) )
        {
            $data = $this->initData();
        }
        $this->innerUser = $this->innerUserBak = $data;
    }

    private function initData()
    {
        return array(
            WorldGrouponSqlDef::TBL_FIELD_UID => $this->uid,
            WorldGrouponSqlDef::TBL_FIELD_POINT => 0,
            WorldGrouponSqlDef::TBL_FIELD_COUPON => 0,
            WorldGrouponSqlDef::TBL_FIELD_OPTIME => Util::getTime(),
            WorldGrouponSqlDef::TBL_FIELD_REWARD_TIME => 0,
            WorldGrouponSqlDef::TBL_FIELD_VA_INFO => array(),
        );
    }

    private function refresh()
    {
        if(!Util::isSameDay($this->innerUser[WorldGrouponSqlDef::TBL_FIELD_OPTIME]))
        {
            /**
             * 不补发奖励了，v9以上才处理
             */
            //$this->reissuePointReward();
            $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_POINT] = 0;
            $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_OPTIME] = Util::getTime();
            if(!empty($this->innerUser[WorldGrouponSqlDef::TBL_FIELD_VA_INFO][WorldGrouponSqlDef::POINT_REWARD_IN_VA_INFO]))
            {
                $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_VA_INFO][WorldGrouponSqlDef::POINT_REWARD_IN_VA_INFO] = array();
            }
        }
    }

    /**
     * 补发积分奖励 --现在不做补发积分奖励了，这个方法没用到
     */
    /*public function reissuePointReward()
    {
        if($this->innerUser[WorldGrouponSqlDef::TBL_FIELD_POINT] == 0)
        {
            Logger::info("WorldGruoponInnerUser::reissuePointReward point:[%d] return.", $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_POINT]);
            return;
        }
        //todo 写错了  奖励全部领取了
        if(empty($this->innerUser[WorldGrouponSqlDef::TBL_FIELD_VA_INFO][WorldGrouponSqlDef::POINT_REWARD_IN_VA_INFO]))
        {
            Logger::info("WorldGruoponInnerUser::reissuePointReward pointReward from VA:[%s] empty return.",
                $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_VA_INFO][WorldGrouponSqlDef::POINT_REWARD_IN_VA_INFO]);
            return;
        }
        $confObj = WorldGrouponConfObj::getInstance();
        $extra = $confObj->getExtraConf();
        $pointRewardConf = $extra[WorldGrouponCsvDef::POINT_REWARD];
        $arrReward = array();
        foreach($pointRewardConf as $point => $eachReward)
        {

            if($this->innerUser[WorldGrouponSqlDef::TBL_FIELD_POINT] >= $point
                && $this->ifPointRewardReceivied($point) == false)
            {
                $arrReward[] = $eachReward;
                $this->recReward($point);
            }
        }
        if(!empty($arrReward))
        {
            //todo 1 发奖update， 2保留到update时候在发奖
            Logger::info("WorldGruoponInnerUser::reissuePointReward sendReward2Senter:[%s] uid:%d", $arrReward, $this->uid);
            RewardUtil::reward3DtoCenter($this->uid, array($arrReward), RewardSource::WORLD_GROUPON_POINT_REWARD);
        }
    }*/

    public function update()
    {
        if($this->innerUser != $this->innerUserBak)
        {
            WorldGrouponDao::updInnerUserInfo($this->innerUser);
        }
        $this->innerUserBak = $this->innerUser;
    }

    public function getPoint()
    {
        return $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_POINT];
    }

    public function getCoupon()
    {
        return $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_COUPON];
    }

    private function updOptime()
    {
        $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_OPTIME] = Util::getTime();
    }

    public function buy()
    {
        $this->updOptime();
    }

    public function getHis()
    {
        if(empty($this->innerUser[WorldGrouponSqlDef::TBL_FIELD_VA_INFO][WorldGrouponSqlDef::HIS_IN_VA_INFO]))
        {
            return array();
        }
        return $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_VA_INFO][WorldGrouponSqlDef::HIS_IN_VA_INFO];
    }

    public function addHis($hisInfo)
    {
        $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_VA_INFO][WorldGrouponSqlDef::HIS_IN_VA_INFO][] = $hisInfo;
    }

    public function getPointReward()
    {
        if(empty($this->innerUser[WorldGrouponSqlDef::TBL_FIELD_VA_INFO][WorldGrouponSqlDef::POINT_REWARD_IN_VA_INFO]))
        {
            return array();
        }
        return $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_VA_INFO][WorldGrouponSqlDef::POINT_REWARD_IN_VA_INFO];
    }

    public function calGoodBuyNumToday($goodId)
    {
        $his = $this->getHis();
        $num = 0;
        foreach($his as $each)
        {
            if($each[WorldGrouponSqlDef::GOOD_ID_IN_VA_INFO] == $goodId
                && Util::isSameDay($each[WorldGrouponSqlDef::BUY_TIME_IN_VA_INFO]))
            {
                $num += $each[WorldGrouponSqlDef::NUM_IN_VA_INFO];
            }
        }
        return $num;
    }

    public function addPoint($addPoint)
    {
        $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_POINT] += $addPoint;
    }

    public function addCoupon($addCoupon)
    {
        $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_COUPON] += $addCoupon;
    }

    public function subCoupon($subCoupon)
    {
        if($this->innerUser[WorldGrouponSqlDef::TBL_FIELD_COUPON] - $subCoupon < 0)
        {
            throw new FakeException("not enough coupon");
        }
        $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_COUPON] -= $subCoupon;
    }

    public function ifPointRewardReceivied($rewardId)
    {
        return in_array($rewardId, $this->getPointReward());
    }

    public function recReward($rewardId)
    {
        $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_VA_INFO][WorldGrouponSqlDef::POINT_REWARD_IN_VA_INFO][] = $rewardId;
        $this->updOptime();
    }

    /**
     * 补发金币时间
     * @param int $time
     */
    public function setRewardTime($time = 0)
    {
        if(empty($time))
        {
            $time = Util::getTime();
        }
        $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_REWARD_TIME] = $time;
    }

    /**
     * 获取补发金币时间
     */
    public function getRewardTime()
    {
        return $this->innerUser[WorldGrouponSqlDef::TBL_FIELD_REWARD_TIME];
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */