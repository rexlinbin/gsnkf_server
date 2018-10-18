<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: MyActExchange.class.php 252566 2016-07-20 10:09:31Z BaoguoMeng $$
 *
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/actexchange/MyActExchange.class.php $$
 * @author $$Author: BaoguoMeng $$(hoping@babeltime.com)
 * @date $$Date: 2016-07-20 10:09:31 +0000 (Wed, 20 Jul 2016) $$
 * @version $$Revision: 252566 $$
 * @brief
 *
 **/

/**
 * protected $dataModify = NULL;
 * array
 * [
 *      all:array               记录物品的购买记录
 *      [
 *          goodsId => array 已兑换记录
 *          [
 *              num:int 已兑换次数
 *              time:int 上次兑换时间
 *          ]
 *
 *      ]
 *      goodsList:array 当前等式列表
 *      [
 *          goodsId: => array 等式编号
 *          [
 *              req: 材料部分
 *              acq: 所得商品部分
 *              refresh_num:int 用户刷新次数 每天重置
 *              free_refresh_num:int 免费刷新次数
 *          ]
 *      ]
 *      refresh_time:int 用户最后一次主动刷新时间
 *      sys_refresh_time:int 最后一次系统刷新时间
 *      last_time:int 上次玩家进入活动时间
 * ]
 */
class MyActExchange extends Mall
{
    public function __construct($uid)
    {
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        parent::__construct($uid, MallDef::MALL_TYPE_ACTEXCHANGE, StatisticsDef::ST_FUNCKEY_MALL_ACTEXCHANGE_COST, StatisticsDef::ST_FUNCKEY_MALL_ACTEXCHANGE_GET);
        $this->loadData();  //加载已购买数据
        if(empty($this->dataModify))    //如果是首次购买
        {
            $this->dataModify = array(
                ActExchangeDef::TBL_FIELD_VA_ALL => array(),    //已购买物品次数和时间
                ActExchangeDef::TBL_FIELD_VA_GOODSLIST => array(),  //当前商品列表
                ActExchangeDef::TBL_FIELD_VA_REFRESH_TIME => 0,
                ActExchangeDef::TBL_FIELD_VA_SYS_REFRESH_TIME => $this->getMidNightTime(),  //最后一次系统刷新时间
                'last_time' => 0,
            );
            $this->sysRfrGoodsList();   //刷新商品列表
        }

        $conf = EnActivity::getConfByName(ActivityName::ACT_EXCHANGE);
        if(empty($this->dataModify['last_time']))
        {
            if($this->dataModify[ActExchangeDef::TBL_FIELD_VA_SYS_REFRESH_TIME] < $conf['start_time'])
            {
                $this->sysRfrGoodsList();   //刷新商品列表
            }
        }
        else
        {
            if($this->dataModify['last_time'] < $conf['start_time'])
            {
                $this->sysRfrGoodsList();   //刷新商品列表
            }
        }
        $this->dataModify['last_time'] = Util::getTime();

        $this->resetRefreshNum();   //重置玩家主动刷新次数和商品列表
        $this->refreshData(); //重置购买次数
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

    public function sysRfrGoodsList($refreshGoodList = true)
    {
        $sysRefrCd = 86400;  //商品刷新间隔时间
        /*
        $sysRefTime = $this->dataModify[ActExchangeDef::TBL_FIELD_VA_SYS_REFRESH_TIME]; //最后一次系统刷新时间
        $now = Util::getTime();
        if($now < $sysRefTime)
        {
            return FALSE;
        }
        */
        //$newRefrTime = $sysRefTime + (intval(($now - $sysRefTime)/$sysRefrCd)+1) * $sysRefrCd;  //计算下次系统刷新时间
        $newRefrTime = $this->getMidNightTime() + $sysRefrCd;
        $this->dataModify[ActExchangeDef::TBL_FIELD_VA_SYS_REFRESH_TIME] = $newRefrTime;
        //需要刷新商品列表
        if($refreshGoodList)
        {
        	$this->refreshGoodsList(3); //刷新方式3
        }
    }

    //重置刷新次数
    private function resetRefreshNum()
    {
        $refrTime = $this->dataModify[ActExchangeDef::TBL_FIELD_VA_REFRESH_TIME]; //玩家最后一次主动刷新时间
        if(Util::isSameDay($refrTime) == FALSE)
        {
            $goodsList = $this->getGoodslist();
            foreach($goodsList as $id => $val)
            {
                $this->clrRfrTimes($id);
                $this->updFreeFreshNum($id, 1 );
            }
        }
    }

    public function canSysRfrGoodList()
    {
    	return false;//现在只有活动第一次进入才会刷新，本接口已经没有意义
        $sysRefrCd = 86400;
        $sysRefrTime = $this->dataModify[ActExchangeDef::TBL_FIELD_VA_SYS_REFRESH_TIME];
        $now = Util::getTime();
        if($now - $sysRefrTime < $sysRefrCd)
        {
            return FALSE;
        }
        return TRUE;
    }

    //判断兑换活动是否开启
    public static function isActExchangeOpen()
    {
        $user = EnUser::getUserObj();
        //活动配置
        $actConf = self::getActivityConf();
        if($user->getLevel() >= $actConf[ActExchangeDef::ACTEXCHANGE_LEVEL]);  //玩家等级是否超过开启等级
        {
            return TRUE;
        }
        return FALSE;
    }


    /**
     * 刷新商品列表
     * @param int $type 刷新方式 1：只刷材料部分；刷新方式2：只刷目标部分；刷新方式3：全部刷新
     * @throws FakeException
     */
    public function refreshGoodsList($type = 1, $goodsId = 0)
    {
        if($type == 1)
        {
            $this->refreshLeft($goodsId);
            $this->refreshRight($goodsId);
        }
        elseif($type == 3)
        {
            $this->refreshBoth();
        }
        else
        {
            Logger::fatal('invalid refresh type:%d', $this);
        }
    }

    /**
     * 刷新公式左右两边
     */
    public function refreshBoth()
    {
    	$arrGoodConf = self::getGoodConf();
    	$actConf = self::getActivityConf();

    	$day = EnActivity::getActivityDay(ActivityName::ACT_EXCHANGE);
    	if( !isset( $actConf[ActExchangeDef::ACTEXCHANGE_CONVERSION_FORMULA][$day] )  )
    	{
    		throw new ConfigException('not found conf for day:%d', $day);
    	}

    	//获取今天的公式id组
    	$arrIdToday = $actConf[ActExchangeDef::ACTEXCHANGE_CONVERSION_FORMULA][$day];

    	$goodsList = array();
    	foreach( $arrIdToday as $id )
    	{
    		if( empty($id) )
    		{
    			throw new ConfigException('invalid id:%s', $id);
    		}

    		$goodsList[$id] = array(
    			'req' => array(),
    			'acq' => array(),
                'refresh_num' => 0,
                'free_refresh_num' => 0,
    		);
    	}

    	$this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST] = $goodsList;

    	$this->refreshLeft();
    	$this->refreshRight();

    }


    /**
     * 刷新公式左边
     * @param int $speciId 需要刷哪个公式, 如果$speciId＝0表示刷所有
     * @param
     */
    public function refreshLeft($speciId = 0)
    {
        //活动配置
        $arrGoodConf = self::getGoodConf();

        $goodsList = $this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST];

        if( $speciId > 0 && !isset( $goodsList[$speciId] ) )
        {
	        throw new FakeException('id:%d not exist. cant just refresh it', $speciId);
        }

        foreach($goodsList as $id => $formula)
        {
        	if( $speciId > 0 && $id != $speciId )
        	{
        		Logger::debug('ignore id:%d, speciId:%d', $id, $speciId);
        		continue;
        	}

            $req = array(
            	'item' => array(),
            );

            $excludeArrItemTpl = array();
            if( !empty( $formula['acq']['item'] ) )
            {
            	$excludeArrItemTpl = array_keys( $formula['acq']['item'] );
            }
            $seq = array();
            $exchangeMaterialQuantity = $arrGoodConf[$id][ActExchangeDef::ACTEXCHANGE_MATERIA_QUANTITY]; //兑换材料数量
            for($i = 1; $i <= $exchangeMaterialQuantity; $i++)
            {
                $exchangeMaterial = $arrGoodConf[$id]['material'."$i"];
                foreach($exchangeMaterial as $key => $val)
                {
                    if( isset( $val['gold'] )  )
                    {
                        if( isset($req['gold']) ) //如果需要的材料中已经有金币了，就不能再随出金币来
                        {
                            unset( $exchangeMaterial[$key] );
                        }
                    }
                    else if( isset( $val['item'] )  )
                    {
                        list($itemTplId, $num) = each( $val['item'] );
                        if( isset( $req['item'][$itemTplId] ) || in_array($itemTplId, $excludeArrItemTpl)  )
                        {
                            unset($exchangeMaterial[$key]);
                        }
                    }
                    else if(isset( $val['drop'] ))
                    {
                        Logger::trace('ignore drop');
                    }
                    else
                    {
                        throw new ConfigException('invalid conf:%s', $exchangeMaterial);
                    }
                }
                if(count($exchangeMaterial) == 0)
                {
                    throw new ConfigException('error config material is empty');
                }
                $arrRet = Util::noBackSample($exchangeMaterial, 1, ActExchangeDef::ACTEXCHANGE_SPEND_FIELD_WEIGHT);
                $material = $exchangeMaterial[$arrRet[0]];

                if(isset($material['gold']))
                {
                    $req['gold'] = $material['gold'];
                    $seq[] = '0';
                }
                else if( isset($material['drop']) || isset($material['item']) )
                {
                	if( isset($material['drop'])  )
                	{
                   		$dropExclude = array_merge( array_keys($req['item']), $excludeArrItemTpl);
                    	$material['item'] = Drop::dropItem($material['drop'], dropdef::DROP_TYPE_ITEM, $dropExclude);
                	}

                    foreach( $material['item'] as $tplId => $num )
                    {
                    	if( isset( $req['item'][$tplId] ) )
                    	{
                    		throw new InterException('drop same item. req:%s, material:%s', $req, $material);
                    	}
                    	$req['item'][$tplId] = $num;
                    }
                    $seq[] = $tplId;
                }
                else
                {
                    throw new InterException('not found any req. material:%s', $material);
                }

            }


            $goodsList[$id]['req'] = $req;
            $goodsList[$id]['seq'] = $seq;
        }

        Logger::debug('refreshLeft. before:%s, after:%s', $this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST] , $goodsList);

        $this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST] = $goodsList;
    }

    public function refreshRight($speciId = 0)
    {
        $arrGoodConf = self::getGoodConf();

        $goodsList = $this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST];

        if( $speciId > 0 && !isset( $goodsList[$speciId] ) )
        {
        	throw new FakeException('id:%d not exist. cant just refresh it', $speciId);
        }

        foreach($goodsList as $id => $formula)
        {
        	if( $speciId > 0 && $id != $speciId )
        	{
        		Logger::debug('ignore id:%d, speciId:%d', $id, $speciId);
        		continue;
        	}

            $excludeArrItemTpl = array();
            if( !empty( $formula['req']['item'] ) )
            {
            	$excludeArrItemTpl = array_keys( $formula['req']['item'] );
            }

            $arrTargetOption = $arrGoodConf[$id][ActExchangeDef::ACTEXCHANGE_TARGET_ITEMS];
            foreach( $arrTargetOption as $key => $val)
            {
            	if( isset( $val['gold'] )  )
            	{
  					Logger::debug('ignore gold');
            	}
            	else if( isset( $val['item'] )  )
            	{
            		list($itemTplId, $num) = each( $val['item'] );
            		if( in_array($itemTplId, $excludeArrItemTpl)  )
            		{
            			unset($arrTargetOption[$key]);
            		}
            	}
            	else if(isset( $val['drop'] ))
            	{
            		Logger::trace('ignore drop');
            	}
            	else
            	{
            		throw new ConfigException('invalid conf:%s', $arrTargetOption);
            	}
            }

            $arrRet = Util::noBackSample($arrTargetOption, 1, ActExchangeDef::ACTEXCHANGE_SPEND_FIELD_WEIGHT);
            $target = $arrTargetOption[$arrRet[0]];

            if(isset($target['drop']) )
            {
                $target['item'] = Drop::dropItem($target['drop'], dropdef::DROP_TYPE_ITEM, $excludeArrItemTpl );
                unset( $target['drop'] );
            }
            unset( $target[ActExchangeDef::ACTEXCHANGE_SPEND_FIELD_WEIGHT]  );

            $goodsList[$id]['acq'] = $target;
        }

        Logger::debug('refreshRight. before:%s, after:%s', $this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST] , $goodsList);

        $this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST] = $goodsList;

    }

    //获取商品列表
    public function getGoodslist()
    {
        return $this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST];
    }

    /**
     * 获取某一条的商品信息
     * @param $id商品id
     */
    public function getInfoById($id)
    {
        $goodInfo = array();
        $goodsList = $this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST]; //当前商品列表
        $buyNum = $this->dataModify[ActExchangeDef::TBL_FIELD_VA_ALL]; //已购买商品

        $arrGoodConf = self::getGoodConf();
        if(!isset($arrGoodConf[$id]))
        {
            Logger::info('goods %d has beed deleted or can not be sold',$id);
            unset($this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST][$id]);
            return;
        }
        $goodInfo[$id] = $goodsList[$id];
        $soldNum = 0; //已购次数
        if(isset($buyNum[$id]))
        {
            $soldNum = $buyNum[$id]['num'];
        }
        $goodInfo[$id]['soldNum'] = $soldNum;
        return $goodInfo;
    }

    public function getShopInfo()
    {
        $shopInfo = array();
        $shopInfo['day'] = EnActivity::getActivityDay(ActivityName::ACT_EXCHANGE);
        $shopInfo[ActExchangeDef::TBL_FIELD_VA_GOODSLIST] = array();
        $goodsList = $this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST]; //当前商品列表
        $buyNum = $this->dataModify[ActExchangeDef::TBL_FIELD_VA_ALL]; //已购买商品

        $arrGoodConf = self::getGoodConf();

        foreach($goodsList as $index => $val)
        {
            if(!isset($arrGoodConf[$index]))
            {
                Logger::info('goods %d has beed deleted or can not be sold',$index);
                unset($this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST][$index]);
                continue;
            }
            $saleNum = $arrGoodConf[$index][ActExchangeDef::ACTEXCHANGE_CHANGE_NUM]; //限购兑换次数
            $soldNum = 0; //已购次数
            if(isset($buyNum[$index]))
            {
                $soldNum = $buyNum[$index]['num'];
            }
            $val['soldNum'] = $soldNum;

            $shopInfo[ActExchangeDef::TBL_FIELD_VA_GOODSLIST][$index] = $val;
        }
        $shopInfo['sys_refresh_cd'] = $this->dataModify[ActExchangeDef::TBL_FIELD_VA_SYS_REFRESH_TIME];

        //$shopInfo['refresh_num'] = $this->dataModify[ActExchangeDef::TBL_FIELD_VA_REFRESH_NUM];
        return $shopInfo;
    }

    //获取玩家主动刷新次数
    public function getPlayerRfrNum($goodId)
    {
        if(!isset($this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST][$goodId]['refresh_num'])){
            throw new FakeException('cannot find this goodid:%d refrsh num', $goodId);
        }
        return $this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST][$goodId]['refresh_num'];
    }

    public function getFreeRfrNum($goodId)
    {
        if(!isset($this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST][$goodId]['free_refresh_num'])){
            throw new FakeException('cannot find this goodid:%d free_refresh_num ', $goodId);
        }
        return $this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST][$goodId]['free_refresh_num'];
    }

    public function canBuy($id, $num = 1)
    {
        $buyNum = $this->dataModify[ActExchangeDef::TBL_FIELD_VA_ALL]; //已购买商品
        $arrGoodConf = self::getGoodConf();
        if(!isset($arrGoodConf[$id]))
        {
            Logger::info('goods %d has beed deleted or can not be sold',$id);
            unset($this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST][$id]);
            return;
        }
        $soldNum = 0; //已购次数
        if(isset($buyNum[$id]))
        {
            $soldNum = $buyNum[$id]['num'];
        }
        $saleNum = $arrGoodConf[$id][ActExchangeDef::ACTEXCHANGE_CHANGE_NUM]; //限购兑换次数
        return ($soldNum + $num) <= $saleNum ;
    }

    public function playerRfrGoodsListByGold($goodId)
    {
        $this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST][$goodId]['refresh_num'] += 1; //当天玩家主动刷新次数+1
        $this->dataModify[ActExchangeDef::TBL_FIELD_VA_REFRESH_TIME] = Util::getTime();
        $this->refreshGoodsList(1, $goodId); //刷新方式1
    }

    public function playerRfrGoodsListByFreeNum($goodId)
    {
        $this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST][$goodId]['free_refresh_num'] -= 1; //当天玩家主动刷新次数+1
        $this->dataModify[ActExchangeDef::TBL_FIELD_VA_REFRESH_TIME] = Util::getTime();
        $this->refreshGoodsList(1, $goodId); //刷新方式1
    }

    public function clrRfrTimes($goodId)
    {
        $this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST][$goodId]['refresh_num'] = 0; //当天玩家主动刷新次数重置
    }

    public function updFreeFreshNum($goodId, $freeRefreshNum)
    {
        $this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST][$goodId]['free_refresh_num'] = $freeRefreshNum; //当天玩家主动刷新次数重置
    }

    //todo 这个方法要重写 符合exchange方法的格式
    public function getExchangeConf($goodsId)
    {
        $arrGoodConf = self::getGoodConf();

        $goodsList = $this->dataModify[ActExchangeDef::TBL_FIELD_VA_GOODSLIST];
        $keys = array_keys($goodsList);
        if(!in_array($goodsId, $keys))
        {
            Logger::warning('actexchange goodsid not found goodsid:%d', $goodsId);
            return array();
        }
        $retconf = $goodsList[$goodsId];
        $retconf['req'][MallDef::MALL_EXCHANGE_NUM] = $arrGoodConf[$goodsId][ActExchangeDef::ACTEXCHANGE_CHANGE_NUM];

        if(isset($retconf['req'][MallDef::MALL_EXCHANGE_ITEM]))
        {
            foreach($retconf['req'][MallDef::MALL_EXCHANGE_ITEM] as $itemTpId => $itemNum)
            {
                $type = ItemManager::getInstance()->getItemType($itemTpId);
                if($type == ItemDef::ITEM_TYPE_TREASURE)
                {
                    $retconf['req'][MallDef::MALL_EXCHANGE_EXTRA][$itemTpId] = $itemNum;
                    unset($retconf['req'][MallDef::MALL_EXCHANGE_ITEM][$itemTpId]);
                }
            }
        }

        return $retconf;
    }

    public static function getDrop()
    {
        $actConf = self::getActivityConf();
        return $actConf[ActExchangeDef::ACTEXCHANGE_REWARD_NORMAL];
    }

    public static function getDropForSdcj()
    {
        $actConf = self::getActivityConf();
        if(empty($actConf[ActExchangeDef::ACTEXCHANGE_SDCJ]))
        {
            return array();
        }
        return $actConf[ActExchangeDef::ACTEXCHANGE_SDCJ];
    }

    public static function getDropForSmsd()
    {
        $actConf = self::getActivityConf();
        if(empty($actConf[ActExchangeDef::ACTEXCHANGE_SMSD]))
        {
            return array();
        }
        return $actConf[ActExchangeDef::ACTEXCHANGE_SMSD];
    }

    public static function getDropForSmsr()
    {
        $actConf = self::getActivityConf();
        if(empty($actConf[ActExchangeDef::ACTEXCHANGE_SMSR]))
        {
            return array();
        }
        return $actConf[ActExchangeDef::ACTEXCHANGE_SMSR];
    }

    public static function getGoodConf()
    {
    	$conf = EnActivity::getConfByName(ActivityName::ACT_EXCHANGE);
    	return $conf['data'];
    }

    /**
     * 获取这个活动除商品列表之外的配置
     */
    public static function getActivityConf()
    {
    	$conf = EnActivity::getConfByName(ActivityName::ACT_EXCHANGE);

    	return $conf['data'][ActExchangeDef::ACTEXCHANGE_GOODS_DEFAULT_ID];
    }

    public function subExtra($exchangeId, $num)
    {
        Logger::trace('MyActExchange::subExtra start.');

        if($exchangeId <= 0 || $num <= 0)
        {
            throw new FakeException(' param is error, exchangeId:%d, num:%d ', $exchangeId, $num);
        }

        $retconf = $this->getExchangeConf($exchangeId);
        if(!isset($retconf['req'][MallDef::MALL_EXCHANGE_EXTRA]))
        {
            throw new FakeException('extra is empty %s', $retconf['req'][MallDef::MALL_EXCHANGE_EXTRA]);
        }

        $bag = BagManager::getInstance()->getBag($this->uid);
        foreach($retconf['req'][MallDef::MALL_EXCHANGE_EXTRA] as $itemTpId => $itemNum)
        {
        	$itemNum = $itemNum * $num;
            $type = ItemManager::getInstance()->getItemType($itemTpId);
            if($type != ItemDef::ITEM_TYPE_TREASURE)
            {
                throw new FakeException(' fake error type ');
            }

            $itemIds = $bag->getItemIdsByTemplateID($itemTpId);
            $arrDelItemId = array();
            foreach($itemIds as $itemId)
            {
                $treasure = ItemManager::getInstance()->getItem($itemId);
                if($treasure->getLevel() >= 1)
                {
                    continue;
                }
                $arrDelItemId[] = $itemId;
            }
            if(empty($arrDelItemId))
            {
                throw new FakeException('have no treasure with 0 level');
            }
            if(count($arrDelItemId) < $itemNum)
            {
                throw new FakeException('have no enough treasure with 0 level');
            }
            for($i = 0; $i < $itemNum; $i++)
            {
                if(!$bag->deleteItem($arrDelItemId[$i]))
                {
                    return false;
                }
            }
        }

        Logger::trace('MyActExchange::subExtra end.');
        return true;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */