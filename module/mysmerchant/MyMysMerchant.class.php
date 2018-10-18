<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: MyMysMerchant.class.php 123766 2014-07-31 02:54:24Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mysmerchant/MyMysMerchant.class.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2014-07-31 02:54:24 +0000 (Thu, 31 Jul 2014) $$
 * @version $$Revision: 123766 $$
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
 *        merchant_end_time:int             神秘商人促发状态截止时间(消失时间)
 *        refresh_num:int            用户主动刷新次数  每天重置一次
 *        refresh_time:int   用户最后一次主动刷新时间  根据此时间重置refresh_num
 *        sys_refresh_time:int            最后一次系统刷新时间
 *    ]
 */
class MyMysMerchant extends Mall
{
    public function __construct($uid)
    {
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        parent::__construct($uid, MallDef::MALL_TYPE_MYSMERCHANT,
		StatisticsDef::ST_FUNCKEY_MALL_MYSMERCHANT_COST,
		StatisticsDef::ST_FUNCKEY_MALL_MYSMERCHANT_GET);
        $this->loadData();
        if(empty($this->dataModify)) {
            $this->dataModify = array(
                MysMerchantDef::TBL_FIELD_VA_ALL => array(),  //记录物品的购买次数
                MysMerchantDef::TBL_FIELD_VA_GOODSLIST => array(),  //当前的商品列表
                MysMerchantDef::TBL_FIELD_VA_REFRNUM_BYPLAYER => 0,  //当日玩家主动刷新次数
                MysMerchantDef::TBL_FIELD_VA_REFRTIME_BYPLAYER => 0, //玩家最后一次主动刷新时间
                MysMerchantDef::TBL_FIELD_VA_MERCHANT_END_TIME => 0, //神秘商人触发状态截止时间
                MysMerchantDef::TBL_FIELD_VA_SYS_REFRTIME => $this->getMidNightTime()  //最后一次系统刷新时间
            );
            //$this->refreshGoodsList();
            $this->sysRfrGoodsList();
        }
        $this->resetRefreshNum();//重置玩家主动刷新次数
        $this->refreshData();//父类方法，重置购买次数
        //修复数据库有dataModify的玩家 非0点刷新
        $this->adjustSysRfrTime();
    }

    //矫正系统刷新时间
    public function adjustSysRfrTime()
    {
        // 一天的秒数
        $SECONDS_OF_DAY = 86400;
        $tmp = abs($this->getMidNightTime()-$this->dataModify[MysMerchantDef::TBL_FIELD_VA_SYS_REFRTIME]);
        if($tmp%$SECONDS_OF_DAY != 0)
        {
            $this->dataModify[MysMerchantDef::TBL_FIELD_VA_SYS_REFRTIME] = $this->getMidNightTime();  //最后一次系统刷新时间
        }
    }

    /**
     * 得到当天午夜的时间戳
     */
    public function getMidNightTime()
    {
        $now = Util::getTime();
        $date = date("Y-m-d",$now);
        $openTime = strtotime($date." "."00:00:00");
        return $openTime;
    }

    /**
     * 1.初次进入神秘商店，getShopInfo会刷新商品列表
     * 2.前端根据冷却时间，刷新商品列表  也是调用getShopInfo
     *
     * 如果前端在CD时间过了之后，没有自动调此接口  前端可能显示有错误
     */
    public function sysRfrGoodsList()
    {
        $sysRefrCd = btstore_get()->MYSMERCHANT['refresh_cd_time'];  //商品刷新间隔时间
        $sysRefrTime = $this->dataModify[MysMerchantDef::TBL_FIELD_VA_SYS_REFRTIME];  //系统最后一次自动刷新时间
        $now = Util::getTime();
        if($now < $sysRefrTime)
        {
            return FALSE;
        }
        $newRefrTime = $sysRefrTime + (intval(($now - $sysRefrTime)/$sysRefrCd)+1) * $sysRefrCd;  //计算下次系统刷新时间
        $this->dataModify[MysMerchantDef::TBL_FIELD_VA_SYS_REFRTIME] = $newRefrTime;
        //需要刷新商品列表
        $this->refreshGoodsList();
    }

    public function canSysRfrGoodsList()
    {
        $sysRefrCd = btstore_get()->MYSMERCHANT['refresh_cd_time'];  //商品刷新时间间隔
        $sysRefrTime = $this->dataModify[MysMerchantDef::TBL_FIELD_VA_SYS_REFRTIME];  //最后一次系统刷新时间
        $now = Util::getTime();
        if($now - $sysRefrTime < $sysRefrCd)
        {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * 触发神秘商人， 神秘商人持续时间增加
     */
    public function trigMysMerchant()
    {
        $sysRefrCd = btstore_get()->MYSMERCHANT['disappear_cd_time'];  //神秘商人持续时间
        $merchant_end_time = $this->dataModify[MysMerchantDef::TBL_FIELD_VA_MERCHANT_END_TIME];  //神秘商人副本结束时间
        $now = Util::getTime();  //当前时间
        if($this->checkMysMerchantState())
        {
            $this->dataModify[MysMerchantDef::TBL_FIELD_VA_MERCHANT_END_TIME] = $merchant_end_time + $sysRefrCd;  //重置 神秘商人促发状态截止时间(消失时间)
        }else
        {
            $this->dataModify[MysMerchantDef::TBL_FIELD_VA_MERCHANT_END_TIME] = $now + $sysRefrCd;  //重置 神秘商人促发状态截止时间(消失时间)
        }
    }

    public function merchantForever()
    {
        $this->dataModify[MysMerchantDef::TBL_FIELD_VA_MERCHANT_END_TIME] = MysMerchantDef::MYSMERCHANT_OPEN_FOREVER;
    }

    public function checkIfForever()
    {
        return $this->dataModify[MysMerchantDef::TBL_FIELD_VA_MERCHANT_END_TIME] == MysMerchantDef::MYSMERCHANT_OPEN_FOREVER;
    }

    //刷新商品列表
    public function refreshGoodsList()
    {
        $userObj = EnUser::getUserObj($this->uid);
        $userLv = $userObj->getLevel();
        $goodsList = array();  //商品列表
        $times = btstore_get()->MYSMERCHANT['refresh_goods_num'];  //每一组刷新出的物品数量
        for($i = 1; $i <= count($times); $i++ )
        {
            $goodsNum = $times[$i-1];  //每组需要随机出的数量
            $goodsListCnf = btstore_get()->MYSMERCHANT["team$i"]->toArray();  //每一组的物品选项

            //7夕活动新增
            $dropForSmsr = EnActExchange::getDropForSmsr();
            foreach($dropForSmsr as $smsrId)
            {
                $goodsListCnf[$smsrId] = array(
                    MysMerchantConf::$MYSMERCHANT_GOODS_BTSTORE_FIELD_WEIGHT =>
                        btstore_get()->MYSMERGOODS[$smsrId][MysMerchantConf::$MYSMERCHANT_GOODS_BTSTORE_FIELD_WEIGHT]
                );
            }

            //计算 $goodsListCnf - $goodsList 的差集
            //$goodsListCnf = array_diff($goodsListCnf, $goodsList);
            foreach($goodsList as $goodid){
                unset($goodsListCnf[$goodid]);
            }

            $buyNum = $this->dataModify[MysMerchantDef::TBL_FIELD_VA_ALL];  //已购商品购买信息
            foreach($goodsListCnf as $goodsId => $goodsconf)
            {
                $saleNum = btstore_get()->MYSMERGOODS[$goodsId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM];  //限购数量
                $soldNum = 0;  //已购数量
                if(isset($buyNum[$goodsId]))
                {
                    $soldNum = $buyNum[$goodsId]['num'];
                }
                if($soldNum >= $saleNum)
                {
                    unset($goodsListCnf[$goodsId]);  //如果已经大于限购数量 从随机库中去掉该商品
                    continue;
                }

                $needLv = btstore_get()->MYSMERGOODS[$goodsId]['need_level'];
                if($needLv > $userLv)
                {
                    unset($goodsListCnf[$goodsId]);
                    continue;
                }
                $goodsListCnf[$goodsId][MysMerchantConf::$MYSMERCHANT_GOODS_BTSTORE_FIELD_WEIGHT] =
                    btstore_get()->MYSMERGOODS[$goodsId][MysMerchantConf::$MYSMERCHANT_GOODS_BTSTORE_FIELD_WEIGHT];
            }

            if(count($goodsListCnf) < $goodsNum)
            {
                $goodsNum = count($goodsListCnf);
                Logger::fatal('@cehua:oh mygod.this user buy too many goods.the remaining goods num is %d less than %s.',count($goodsListCnf),$goodsNum);
            }
            $goodsList = array_merge($goodsList, Util::noBackSample($goodsListCnf, $goodsNum, MysMerchantConf::$MYSMERCHANT_GOODS_BTSTORE_FIELD_WEIGHT));
        }
        $this->dataModify[MysMerchantDef::TBL_FIELD_VA_GOODSLIST] = $goodsList;  //当前商品列表
    }

    //重置刷新次数
    private function resetRefreshNum()
    {
        $refrTime = $this->dataModify[MysMerchantDef::TBL_FIELD_VA_REFRTIME_BYPLAYER];  //玩家最后一次主动刷新时间
        if(Util::isSameDay($refrTime) == FALSE)
        {
            $this->dataModify[MysMerchantDef::TBL_FIELD_VA_REFRTIME_BYPLAYER] = Util::getTime();
            $this->dataModify[MysMerchantDef::TBL_FIELD_VA_REFRNUM_BYPLAYER] = 0;
        }
    }

    //获取商品列表
    public function getGoodsList()
    {
        return $this->dataModify[MysMerchantDef::TBL_FIELD_VA_GOODSLIST];
    }

    public function getShopInfo()
    {
        $shopInfo = array();
        $shopInfo['goods_list'] = array();
        $goodsList = $this->dataModify[MysMerchantDef::TBL_FIELD_VA_GOODSLIST];  //当前商品列表
        $buyNum = $this->dataModify[MysMerchantDef::TBL_FIELD_VA_ALL];  //已买商品信息
        foreach($goodsList as $index => $goodsId)
        {
            if(!isset( btstore_get()->MYSMERGOODS[$goodsId]))
            {
                Logger::info('goods %d has beed deleted or can not be sold',$goodsId);
                unset($this->dataModify[MysMerchantDef::TBL_FIELD_VA_GOODSLIST][$index]);
                continue;
            }
            $saleNum = btstore_get()->MYSMERGOODS[$goodsId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM];  //限购数量
            $soldNum = 0;  //已购数量
            if(isset($buyNum[$goodsId]))
            {
                $soldNum = $buyNum[$goodsId]['num'];
            }
            $shopInfo['goods_list'][$goodsId] = $saleNum - $soldNum;  //可购数量
        }
        $shopInfo['refresh_num'] = $this->dataModify[MysMerchantDef::TBL_FIELD_VA_REFRNUM_BYPLAYER];  //玩家当天刷新次数
        $shopInfo['merchant_end_time'] = $this->dataModify[MysMerchantDef::TBL_FIELD_VA_MERCHANT_END_TIME];  // 神秘商人促发状态截止时间(消失时间)
        $shopInfo['refresh_cd'] = $this->dataModify[MysMerchantDef::TBL_FIELD_VA_SYS_REFRTIME];  //最后一次系统刷新时间
        Logger::trace('getShopInfo refresh_cd is %s,now is %s.',$this->dataModify[MysMerchantDef::TBL_FIELD_VA_SYS_REFRTIME],Util::getTime());
        Logger::trace('getShopInfo merchant_end_time is %s,now is %s.',$this->dataModify[MysMerchantDef::TBL_FIELD_VA_MERCHANT_END_TIME],Util::getTime());
        return $shopInfo;
    }

    //获取玩家主动刷新次数
    public function getPlayerRfrNum()
    {
        return $this->dataModify[MysMerchantDef::TBL_FIELD_VA_REFRNUM_BYPLAYER];
    }

    /**
     * 如果当前时间可以进行系统刷新怎么办？  前端的问题？前端没有及时进行系统刷新？
     */
    public function playerRfrGoodsListByGold()
    {
        $this->dataModify[MysMerchantDef::TBL_FIELD_VA_REFRNUM_BYPLAYER] += 1;  //玩家当天主动刷新次数+1
        $this->dataModify[MysMerchantDef::TBL_FIELD_VA_REFRTIME_BYPLAYER] = Util::getTime();  //玩家最后刷新时间重置
        $this->refreshGoodsList();
    }

    //检测神秘商人副本开启状态 true 开启 false 关闭
    public function checkMysMerchantState()
    {
        $merchant_end_time = $this->dataModify[MysMerchantDef::TBL_FIELD_VA_MERCHANT_END_TIME];
        $now = Util::getTime();
        return $merchant_end_time > $now;
    }

    //检测玩家等级是否 超过神秘商人限制
    public static function checkUserLevelLimit()
    {
        $userLv = EnUser::getUserObj()->getLevel();
        return $userLv >= MysMerchantDef::MYSMERCHANT_OPEN_LEVEL;
    }

    public function getExchangeConf($goodsId)
    {
        if(!isset(btstore_get()->MYSMERGOODS[$goodsId]))
        {
            Logger::warning('mysmergoods not found id:%d', $goodsId);
            return array();
        }
        $conf = btstore_get()->MYSMERGOODS[$goodsId]->toArray();
        return $conf;
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */