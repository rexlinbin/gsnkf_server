<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnDestiny.class.php 107811 2014-05-13 03:47:11Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/destiny/EnDestiny.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-05-13 03:47:11 +0000 (Tue, 13 May 2014) $
 * @version $Revision: 107811 $
 * @brief 
 *  
 **/
class EnDestiny
{
    
    private static $arrUserDestiny = array();
    
    public static function getDestiny($uid=0)
    {
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        if(isset(self::$arrUserDestiny[$uid]))
        {
            return self::$arrUserDestiny[$uid];
        }
        $destinyInst = new MyDestiny($uid);
        self::$arrUserDestiny[$uid] = $destinyInst;
        return $destinyInst;
    }
    
    public static function getActiveDestinyNum($uid=0)
    {
        $destinyInst = self::getDestiny($uid);
        $destinyId = $destinyInst->getCurDestinyId();
        $num = 0;
        while(!empty($destinyId))
        {
            $num++;
            $pre = btstore_get()->DESTINY[$destinyId]['preId'];
            $destinyId = $pre;
        }
        return $num;
    }
    
    public static function getAddAttr($uid=0)
    {
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        $destinyInst = self::getDestiny($uid);
        $curDestinyId = $destinyInst->getCurDestinyId();
        $arrAddAttr = array();
        while(!empty($curDestinyId))
        {
            $addAttr = btstore_get()->DESTINY[$curDestinyId]['addAttr'];
            foreach($addAttr as $key => $value)
            {
                if(!isset($arrAddAttr[$key]))
                {
                    $arrAddAttr[$key] = 0;
                }
                $arrAddAttr[$key] += $value;
            }
            $curDestinyId = btstore_get()->DESTINY[$curDestinyId]['preId'];
        }
        return $arrAddAttr;
    }
    
    public static function getLastBreakId($uid=0)
    {
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        $destinyInst = self::getDestiny($uid);
        $curDestinyId = $destinyInst->getCurDestinyId();
        while(!empty($curDestinyId))
        {
            $breakId = btstore_get()->DESTINY[$curDestinyId]['breakId'];
            if(!empty($breakId))
            {
                return $breakId;
            }
            $curDestinyId = btstore_get()->DESTINY[$curDestinyId]['preId'];
        }
        return 0;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */