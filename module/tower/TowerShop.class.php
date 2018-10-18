<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TowerShop.class.php 255251 2016-08-09 07:30:26Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/tower/TowerShop.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-08-09 07:30:26 +0000 (Tue, 09 Aug 2016) $
 * @version $Revision: 255251 $
 * @brief 
 *  
 **/
class TowerShop extends Mall
{
    public function __construct()
    {
        $uid = RPCContext::getInstance()->getUid();
        
        parent::__construct($uid, MallDef::MALL_TYPE_HELLTOWER, StatisticsDef::ST_FUNCKEY_MALL_HELLTOWER_COST,StatisticsDef::ST_FUNCKEY_MALL_HELLTOWER_GET);
        
        $this->loadData();
        
        if (empty($this->dataModify))
        {
            $this->dataModify = array(
                MallDef::ALL => array(),
            );
        }
        
        $this->refreshData();
    }
    
    public function getShopInfo()
    {
        $hasBuyInfo = $this->getInfo();
        
        $info = array();
        foreach ($hasBuyInfo as $key => $value)
        {
            $info[$key] = $value['num'];
        }
        
        return $info;
    }
    
    public function buy($goodsId, $num=1)
    {
        $this->exchange($goodsId, $num);
        return 'ok';
    }
    
    public function getExchangeConf($exchangeId)
    {
        $arrTowerGoodsConf = btstore_get()->HELL_TOWER_GOODS->toArray();
        return empty( $arrTowerGoodsConf[$exchangeId] ) ? array() : $arrTowerGoodsConf[$exchangeId];
    }
    
    public function subExtra($exchangeId, $num)
    {
        $uid = RPCContext::getInstance()->getUid();
        
        $conf = self::getExchangeConf($exchangeId);
        if ( empty( $conf ) )
        {
            throw new FakeException("no conf for id:%d.", $exchangeId);
        }
        
        $needTowerNum = intval( $conf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA]['tower'] ) * $num;
        
        $userObj = EnUser::getUserObj($uid);
        
        $hasTowerNum = $userObj->getTowerNum();
        if ( $hasTowerNum < $needTowerNum )
        {
            Logger::warning("tower num not enough. need:%d now:%d.", $needTowerNum, $hasTowerNum);
            return FALSE;
        }
        
        if ( FALSE == $userObj->subTowerNum($needTowerNum) )
        {
            Logger::warning("tower num not enough. need:%d.", $needTowerNum);
            return FALSE;
        }
        
        return TRUE;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */