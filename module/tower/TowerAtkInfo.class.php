<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TowerAtkInfo.class.php 86278 2014-01-13 06:15:53Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/tower/TowerAtkInfo.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-01-13 06:15:53 +0000 (Mon, 13 Jan 2014) $
 * @version $Revision: 86278 $
 * @brief 
 *  
 **/
class TowerAtkInfo extends NCopyAtkInfo
{
 
    protected function __construct($atkInfo=NULL)
    {
        if($atkInfo != NULL)
        {
            $this->atkInfo = $atkInfo;
        }
        else
        {
            parent::__construct();
        }
    }
    
    /**
     * @return TowerAtkInfo
     */
    public static function getInstance()
    {
        if (!self::$_instance  instanceof self)
        {
            if(self::$_instance != NULL)
            {
                self::$_instance = new self(self::$_instance->getAtkInfo());
            }
            else
            {
                self::$_instance = new self();
            }
        }
        return self::$_instance;
    }
    
//     public function addFailNum()
//     {
//         $atkInfo = self::getAtkInfo();
//         if(!isset($atkInfo[ATK_INFO_FIELDS::FAILNUM]))
//         {
//             $atkInfo[ATK_INFO_FIELDS::FAILNUM] = 0;
//         }
//         $atkInfo[ATK_INFO_FIELDS::FAILNUM]++;
//         $this->atkInfo = $atkInfo;
//     }
    
//     public function getFailNum()
//     {
//         $atkInfo = self::getAtkInfo();
//         if(!isset($atkInfo[ATK_INFO_FIELDS::FAILNUM]))
//         {
//             $this->atkInfo[ATK_INFO_FIELDS::FAILNUM] = 0;
//         }
//         return $this->atkInfo[ATK_INFO_FIELDS::FAILNUM];
//     }
    
//     public function getLotteryNum()
//     {
//         $atkInfo = self::getAtkInfo();
//         if(!isset($atkInfo[ATK_INFO_FIELDS::LOTTERYNUM]))
//         {
//             $atkInfo[ATK_INFO_FIELDS::LOTTERYNUM] = -1;
//         }
//         return $atkInfo[ATK_INFO_FIELDS::LOTTERYNUM];
//     }
    
//     public function addLotteryNum()
//     {
//         $atkInfo = self::getAtkInfo();
//         if(!isset($atkInfo[ATK_INFO_FIELDS::LOTTERYNUM]))
//         {
//             $atkInfo[ATK_INFO_FIELDS::LOTTERYNUM] = -1;
//         }
//         $atkInfo[ATK_INFO_FIELDS::LOTTERYNUM]++;
//         $this->atkInfo = $atkInfo;
//     }
    
    public function getTowerLv()
    {
        if(!isset($this->atkInfo[ATK_INFO_FIELDS::COPYID]))
        {
            throw new InterException('no towerlv in atkinfo.');
        }
        return $this->atkInfo[ATK_INFO_FIELDS::COPYID];
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */