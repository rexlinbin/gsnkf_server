<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyMysteryShop.class.php 123775 2014-07-31 03:33:26Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mysteryshop/MyMysteryShop.class.php $
 * @author $Author: ShijieHan $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-07-31 03:33:26 +0000 (Thu, 31 Jul 2014) $
 * @version $Revision: 123775 $
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
 *        sys_refresh_time:int            最后一次系统刷新时间
 *        refresh_num:int            用户主动刷新次数  每天重置一次
 *        refresh_time:int   用户最后一次主动刷新时间  根据此时间重置refresh_num
 *    ]
 */
class MyMysteryShop extends Mall
{
    public function __construct($uid=0)
    {
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        parent::__construct($uid, MallDef::MALL_TYPE_MYSTERY,
		StatisticsDef::ST_FUNCKEY_MALL_MYSTERY_COST,
		StatisticsDef::ST_FUNCKEY_MALL_MYSTERY_GET);
        $this->loadData();
        if(empty($this->dataModify))
        {
            $sysRefrCd = btstore_get()->MYSTERYSHOP['refresh_cd_time'];
            $this->dataModify = array(
                    MysteryShopDef::TBL_FIELD_VA_ALL => array(),//物品购买次数记录
                    MysteryShopDef::TBL_FIELD_VA_GOODSLIST => array(),//商店的商品列表
                    MysteryShopDef::TBL_FIELD_VA_SYS_REFRTIME =>  intval((Util::getTime()/$sysRefrCd+1))*$sysRefrCd,
                    MysteryShopDef::TBL_FIELD_VA_SYS_RFRNUM => 1,
                    MysteryShopDef::TBL_FIELD_VA_REFRNUM_BYPLAYER => 0,
                    MysteryShopDef::TBL_FIELD_VA_REFRTIME_BYPLAYER => 0
                    );
            $this->sysRfrGoodsList();
        }
        $this->refreshSysRfrNum();
        $this->resetRefreshNum();//重置玩家主动刷新次数
        $this->refreshData();//父类方法，重置购买次数
    }
    
    public function getShopInfo()
    {
         $shopInfo = array();
         $goodsList = $this->dataModify[MysteryShopDef::TBL_FIELD_VA_GOODSLIST];
         $buyNum = $this->dataModify[MysteryShopDef::TBL_FIELD_VA_ALL];
         $shopInfo['goods_list'] = array();
         foreach($goodsList as $index => $goodsId)
         {
             if(!isset( btstore_get()->MYSTERYGOODS[$goodsId]))
             {
                 Logger::fatal('goods %d has beed deleted or can not be sold',$goodsId);
                 unset($this->dataModify[MysteryShopDef::TBL_FIELD_VA_GOODSLIST][$index]);
                 continue;
             }
             $saleNum = btstore_get()->MYSTERYGOODS[$goodsId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM];
             $soldNum = 0;
             if(isset($buyNum[$goodsId]))
             {
                 $soldNum = $buyNum[$goodsId]['num'];
             }
             $shopInfo['goods_list'][$goodsId] = $saleNum - $soldNum;
         }   
         $shopInfo['refresh_cd'] = $this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_REFRTIME];
         $shopInfo['refresh_num'] = $this->dataModify[MysteryShopDef::TBL_FIELD_VA_REFRNUM_BYPLAYER];
         $shopInfo['sys_refresh_num'] = $this->getSysRfrNum();
         Logger::trace('getShopInfo refresh_cd is %s,now is %s.',$this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_REFRTIME],Util::getTime());
         return $shopInfo;
    }
    /**
     * 1.初次进入神秘商店，getShopInfo会刷新商品列表
     * 2.前端根据冷却时间，刷新商品列表  也是调用getShopInfo
     * 
     * 如果前端在CD时间过了之后，没有自动调此接口  前端可能显示有错误
     */
    public function sysRfrGoodsList()
    {
        $sysRfrNum = $this->getSysRfrNum();
        if($sysRfrNum < 1)
        {
            return FALSE;
        }
        //需要刷新商品列表
        $this->refreshGoodsList();
        $this->subSysRfrNum();
        return TRUE;
    }
    
    public function refreshSysRfrNum()
    {
        $sysRefrCd = btstore_get()->MYSTERYSHOP['refresh_cd_time'];
        $sysRefrTime = $this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_REFRTIME];
        $now = Util::getTime();
        if($now < $sysRefrTime)
        {
            return;
        }
        $accumRfrNum = intval(($now - $sysRefrTime)/$sysRefrCd) + 1;
        $newRefrTime = $sysRefrTime + $accumRfrNum * $sysRefrCd;
        $this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_REFRTIME] = $newRefrTime;
        if(!isset($this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_RFRNUM]))
        {
            $this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_RFRNUM] = 0;
        }
        $this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_RFRNUM] += $accumRfrNum;
        $maxNum = MysteryShop::getMaxFreeSysRfrNum($this->uid);
        if($this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_RFRNUM] > $maxNum)
        {
            $this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_RFRNUM] = $maxNum;
        }
    }
    
    public function subSysRfrNum()
    {
        if(!isset($this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_RFRNUM]))
        {
            $this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_RFRNUM] = 0;
        }
        if($this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_RFRNUM] < 1)
        {
            return FALSE;
        }
        $this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_RFRNUM]--;
        return TRUE;
    }
    
    public function getSysRfrNum()
    {
        if(!isset($this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_RFRNUM]))
        {
            $this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_RFRNUM] = 0;
        }
        return $this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_RFRNUM];
    }
    
    public function canSysRfrGoodsList()
    {
        $sysRefrCd = btstore_get()->MYSTERYSHOP['refresh_cd_time'];
        $sysRefrTime = $this->dataModify[MysteryShopDef::TBL_FIELD_VA_SYS_REFRTIME];
        $now = Util::getTime();
        if($now  < $sysRefrTime)
        {
            return FALSE;
        }
        return TRUE;
    }

    public function playerRfrGoodsListByGold()
    {
        $this->dataModify[MysteryShopDef::TBL_FIELD_VA_REFRNUM_BYPLAYER] += 1;
        $this->dataModify[MysteryShopDef::TBL_FIELD_VA_REFRTIME_BYPLAYER] = Util::getTime();
        $this->refreshGoodsList();
    }
    
    public function getPlayerRfrNum()
    {
        return $this->dataModify[MysteryShopDef::TBL_FIELD_VA_REFRNUM_BYPLAYER];
    }
    
    public function refreshGoodsList()
    {
        $userObj = EnUser::getUserObj($this->uid);
        $userLv = $userObj->getLevel();
        $goodsNum = btstore_get()->MYSTERYSHOP['refresh_goods_num'];
        $goodsListCnf = btstore_get()->MYSTERYGOODS->toArray();
        //7夕活动新增
        $dropForSmsd = EnActExchange::getDropForSmsd();

        $buyNum = $this->dataModify[MysteryShopDef::TBL_FIELD_VA_ALL];
        foreach($goodsListCnf as $goodsId => $goodsConf)
        {
            $saleNum = btstore_get()->MYSTERYGOODS[$goodsId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM];
            $soldNum = 0;
            if(isset($buyNum[$goodsId]))
            {
                $soldNum = $buyNum[$goodsId]['num'];
            }
            if($soldNum >= $saleNum)
            {
                unset($goodsListCnf[$goodsId]);
            }
            //如果商品id是7夕活动的， 不加等级限制
            if(in_array($goodsId, $dropForSmsd))
            {
                continue;
            }
            $needLv = btstore_get()->MYSTERYGOODS[$goodsId]['need_level'];
            if($needLv > $userLv)
            {
                unset($goodsListCnf[$goodsId]);
            }
        }

        if(count($goodsListCnf) < $goodsNum)
        {
            $goodsNum = count($goodsListCnf);
            Logger::fatal('@cehua:oh mygod.this user buy too many goods.the remaining goods num is %d less than %s.',count($goodsListCnf),$goodsNum);
        }
        $goodsList = Util::noBackSample($goodsListCnf, $goodsNum, MysteryShopConf::$MYSTERY_GOODS_BTSTORE_FIELD_WEIGHT);
        $this->dataModify[MysteryShopDef::TBL_FIELD_VA_GOODSLIST] = $goodsList;
    }
    
    private function resetRefreshNum()
    {
        $refrTime = $this->dataModify[MysteryShopDef::TBL_FIELD_VA_REFRTIME_BYPLAYER];
        if(Util::isSameDay($refrTime) == FALSE)
        {
            $this->dataModify[MysteryShopDef::TBL_FIELD_VA_REFRTIME_BYPLAYER] = Util::getTime();
            $this->dataModify[MysteryShopDef::TBL_FIELD_VA_REFRNUM_BYPLAYER] = 0;
            $this->refreshGoodsList();
        }
    }
    
    public function getGoodsList()
    {
        return $this->dataModify[MysteryShopDef::TBL_FIELD_VA_GOODSLIST];
    }
    
    public function getExchangeConf($goodsId)
    {
    	if( !isset(  btstore_get()->MYSTERYGOODS[$goodsId] )  )
    	{
    		Logger::warning('not found id:%d', $goodsId);
    		return array();
    	}
        $conf = btstore_get()->MYSTERYGOODS[$goodsId]->toArray();
        return $conf;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */