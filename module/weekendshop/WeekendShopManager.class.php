<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: WeekendShopManager.class.php 137165 2014-10-22 10:26:56Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/weekendshop/WeekendShopManager.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-10-22 10:26:56 +0000 (Wed, 22 Oct 2014) $$
 * @version $$Revision: 137165 $$
 * @brief 
 *  
 **/

/**
 *    protected $dataModify = NULL;
 *    array
 *    [
 *        all:array                        记录物品的购买次数
 *        [
 *            goodsId=>array
 *            [
 *                'num'=>int(兑换次数),
 *                'time'=>int(兑换时间)
 *            ]
 *        ]
 *        goodslist:array                当前的商品列表
 *        [
 *            goodsId:int
 *        ]
 *        weekendshop_time:int           上次进入活动时间 只记每天第一次进入时间（用来计算总次数）
 *        weekendshop_num:int            活动已开总次数（循环显示用）
 *        has_buy_num:int                当天已购买总次数 (每天总购买次数有限制)
 *        rfr_num_by_player:int          玩家当天刷新次数（会影响花费金币） 只在金币刷新的时候增长
 *    ]
 */
class WeekendShopManager extends Mall
{

    public function __construct()
    {
        $uid = RPCContext::getInstance()->getUid();
        if(EnSwitch::isSwitchOpen(SwitchDef::WEEKENDSHOP) == false)
        {
            throw new FakeException('user:%d does not open the weekendshop', $uid);
        }
        parent::__construct($uid, MallDef::MALL_TYPE_WEEKENDSHOP,
            StatisticsDef::ST_FUNCKEY_MALL_WEEKENDSHOP_COST, StatisticsDef::ST_FUNCKEY_MALL_WEEKENDSHOP_GET);
        parent::loadData();
        if (empty($this->dataModify))
        {
            $this->initData();
        }

        $this->rfrDataEveryDay();
        //父类方法，重置购买次数
        $this->refreshData();
    }

    /*
    * 初始化
    */
    private function initData()
    {
        $this->dataModify = array(
            WeekendShopDef::WEEKENDSHOP_ALL => array(),
            WeekendShopDef::WEEKENDSHOP_GOODSLIST => array(),
            WeekendShopDef::WEEKENDSHOP_TIME => 0,
            WeekendShopDef::WEEKENDSHOP_NUM => 0,
            WeekendShopDef::HAS_BUY_NUM => 0,
            WeekendShopDef::RFR_NUM_BY_PLAYER => 0,
        );
    }

    /**
     * 每日刷新
     */
    private function rfrDataEveryDay()
    {
        if ($this->ifCanSysRfrGoodList() == false)
        {
            return;
        }
        //清理当天玩家主动刷新次数 和购买总次数
        $this->dataModify[WeekendShopDef::RFR_NUM_BY_PLAYER] = 0;
        $this->dataModify[WeekendShopDef::HAS_BUY_NUM] = 0;
        //每次构造都调下系统刷新（当天已刷，直接返回）
        $this->rfrGoodList();
        //更新活动累积开启次数
        $this->rfrShopNum();
        //更新时间喽
        $this->dataModify[WeekendShopDef::WEEKENDSHOP_TIME] = Util::getTime();
    }

    /**
     * 刷新活动已开总次数(规则变了，读定义时间，算周数)
     */
    private function rfrShopNum()
    {
        //当天和定义时间之间相差的周数
        $weeksBetween = WeekendShopUtil::getWeeksBetween();
        if ($this->dataModify[WeekendShopDef::WEEKENDSHOP_NUM] == $weeksBetween)
        {
            return;
        }
        $this->dataModify[WeekendShopDef::WEEKENDSHOP_NUM] = $weeksBetween;
    }

    /**
     * 能否系统刷新 因刷新每天重置的条件判断一样，好几个地方都用它
     * @return bool
     */
    public function ifCanSysRfrGoodList()
    {
        if (!empty($this->dataModify[WeekendShopDef::WEEKENDSHOP_TIME])
            && Util::isSameDay($this->dataModify[WeekendShopDef::WEEKENDSHOP_TIME])
        )
        {
            return false;
        }
        return true;
    }

    public function rfrGoodList()
    {
        $idOfWeek = $this->calIdOfWeek();
        $confDataOfShop = WeekendShopUtil::getWeekendShopConf($idOfWeek);
        $arrTeamNum = $confDataOfShop[WeekendShopCsvDef::TEAM_NUMS];
        $confDataOfGoods = WeekendShopUtil::getWeekendGoodsConf();
        $userLv = $this->getUserLv();

        //开刷
        $hasBuyInfo = $this->dataModify[WeekendShopDef::WEEKENDSHOP_ALL];
        $arrGoodList = array();
        foreach ($arrTeamNum as $key => $val)
        {
            $realKey = $key + 1;
            $arrTeamIds = $confDataOfShop["team$realKey"]->toArray();
            $arrTeamTmp = array();
            foreach ($arrTeamIds as $k => $goodId)
            {
                $arrTeamTmp[$k] = array(WeekendShopCsvDef::GOODID => $goodId,
                                        WeekendGoodsCsvDef::WEIGHT => $confDataOfGoods[$goodId][WeekendGoodsCsvDef::WEIGHT]);
                //排除前面组已刷出的goodid
                if (in_array($goodId, $arrGoodList))
                {
                    unset($arrTeamTmp[$k]);
                    continue;
                }

                //排除等级限制不足的
                if ($confDataOfGoods[$goodId][WeekendGoodsCsvDef::LEVEL_LIMIT] > $userLv)
                {
                    unset($arrTeamTmp[$k]);
                    continue;
                }

                //排除超出限购数量的
                $soldNum = 0;
                if (isset($hasBuyInfo[$goodId]))
                {
                    $soldNum = $hasBuyInfo[$goodId][MallDef::NUM];
                }
                if ($soldNum >= $confDataOfGoods[$goodId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM])
                {
                    unset($arrTeamTmp[$k]);
                }
            }

            if (empty($arrTeamTmp))
            {
                continue;
            }
            if (count($arrTeamTmp) < $val)
            {
                $val = count($arrTeamTmp);
                Logger::fatal('this user buy too much goods, shop almost empty');
            }
            $arrKeyTmp = Util::noBackSample($arrTeamTmp, $val, WeekendGoodsCsvDef::WEIGHT);
            $arrGoodList = array_merge($arrGoodList, WeekendShopUtil::extractValue($arrTeamTmp, $arrKeyTmp));
        }

        $this->dataModify[WeekendShopDef::WEEKENDSHOP_GOODSLIST] = $arrGoodList;
    }

    /**
     * 计算本周商店id
     * @return int
     */
    public function calIdOfWeek()
    {
        $defaultConf = WeekendShopUtil::getDefaultConf();
        $arrCircleId = $defaultConf[WeekendShopCsvDef::CIRCLE_ID]->toArray();
        //根据shopnum计算本周商店id
        $idOfWeek = WeekendShopUtil::calCircleId($arrCircleId, $this->dataModify[WeekendShopDef::WEEKENDSHOP_NUM]);
        return $idOfWeek;
    }

    private function getUserLv()
    {
        $user = EnUser::getUserObj($this->uid);
        return $user->getLevel();
    }

    public function getGoodList()
    {
        $hasBuyInfo = $this->dataModify[WeekendShopDef::WEEKENDSHOP_ALL];
        $goodList = $this->dataModify[WeekendShopDef::WEEKENDSHOP_GOODSLIST];
        $confDataOfGoods = WeekendShopUtil::getWeekendGoodsConf();
        $goodListForFront = array();

        foreach ($goodList as $goodId)
        {
            //已购数量
            $soldNum = 0;
            if (isset($hasBuyInfo[$goodId]))
            {
                $soldNum = $hasBuyInfo[$goodId][MallDef::NUM];
            }

            $goodListForFront[$goodId] = $confDataOfGoods[$goodId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] - $soldNum;
        }
        return $goodListForFront;
    }

    public function getWeekendShopNum()
    {
        return $this->dataModify[WeekendShopDef::WEEKENDSHOP_NUM];
    }

    public function getHasBuyNum()
    {
        return $this->dataModify[WeekendShopDef::HAS_BUY_NUM];
    }

    public function getRfrNumByPlayer()
    {
        return $this->dataModify[WeekendShopDef::RFR_NUM_BY_PLAYER];
    }

    public function updHasBuyNum()
    {
        $this->dataModify[WeekendShopDef::HAS_BUY_NUM] += 1;
    }

    public function updRfrNumByPlayer()
    {
        //更新玩家刷新次数
        $this->dataModify[WeekendShopDef::RFR_NUM_BY_PLAYER] += 1;
    }

    public function getExchangeConf($goodId)
    {
        $weekendGoodConf = WeekendShopUtil::getWeekendGoodsConf();
        if (!isset($weekendGoodConf[$goodId]))
        {
            Logger::warning('goodId:%d not found', $goodId);
            return array();
        }
        return $weekendGoodConf[$goodId]->toArray();
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */