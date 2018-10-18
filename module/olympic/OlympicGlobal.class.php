<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: OlympicGlobal.class.php 134016 2014-09-23 07:07:17Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/olympic/OlympicGlobal.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-09-23 07:07:17 +0000 (Tue, 23 Sep 2014) $$
 * @version $$Revision: 134016 $$
 * @brief 
 *  
 **/

/**
 * globalInfo => array
 * [
 *  'silver_pool' => int,
 *  'va_data' => array
 *  [
 *      last_campion => int,
 *      win_cont => int
 *  ]
 * ]
 */

/**
 * 已经改成统一管理
 * va字段和silver_pool 字段是分开管理的。这样做会有一些坏处
 * 1、get数据时候，要取多次。
 * 2、不好扩展
 * 3、
 * Class OlympicGlobal
 */
class OlympicGlobal
{
    private $globalInfo = NULL;
    private $globalBuffer = NULL;

    private static $instance = NULL;

    private function __construct()
    {
        $this->loadData();
    }

    /**
     * 
     * @return OlympicGlobal
     */
    public static function getInstance()
    {
        if(empty(self::$instance))
        {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function release()
    {
        self::$instance = NULL;
    }

    private function loadData()
    {
        $globalInfo = OlympicDao::getOlympicGlobal(OlympicGlobalDef::SPECIAL_ID);
        if(empty($globalInfo))
        {
            $globalInfo = array(
                OlympicGlobalDef::SILVER_POOL => 0,
                OlympicGlobalDef::VA_DATA => array(
                    OlympicGlobalDef::LAST_CHAMPION => 0,
                    OlympicGlobalDef::WIN_CONT => 1,
                    OlympicGlobalDef::AVG_LEVEL_OF_ARENA => 0,
                )
            );
            //方便以后去掉，取上届冠军
            $preOlympicStartTime = OlympicLogic::getPreOlympicStartTime();
            $logInst = OlympicLog::getInstance(OlympicLogType::REWARD_POOL, $preOlympicStartTime - SECONDS_OF_DAY);
            $globalInfo[OlympicGlobalDef::VA_DATA][OlympicGlobalDef::LAST_CHAMPION] = $logInst->getLastCampion();

            OlympicDao::insertOlympicGlobal(
                array(
                    OlympicGlobalDef::ID => OlympicGlobalDef::SPECIAL_ID,
                    OlympicGlobalDef::SILVER_POOL => $globalInfo[OlympicGlobalDef::SILVER_POOL],
                    OlympicGlobalDef::VA_DATA => $globalInfo[OlympicGlobalDef::VA_DATA],
                )
            );
        }
        $this->globalInfo = $globalInfo;
        $this->globalBuffer = $globalInfo;
    }

    public function update()
    {
        if($this->globalInfo == $this->globalBuffer)
        {
            return;
        }
        OlympicDao::updOlympicGlobal(
            array(
                OlympicGlobalDef::VA_DATA => $this->globalInfo[OlympicGlobalDef::VA_DATA]
            ),
            OlympicGlobalDef::SPECIAL_ID
        );
        $deltaSilver = $this->globalInfo[OlympicGlobalDef::SILVER_POOL] - $this->globalBuffer[OlympicGlobalDef::SILVER_POOL];
        if($deltaSilver > 0)
        {
            OlympicDao::addSilverPool($deltaSilver, OlympicGlobalDef::SPECIAL_ID);
        }
        $this->globalBuffer = $this->globalInfo;
    }

    public function getLastChampion()
    {
        return $this->globalInfo[OlympicGlobalDef::VA_DATA][OlympicGlobalDef::LAST_CHAMPION];
    }

    public function updLastChampion($champion)
    {
        $this->globalInfo[OlympicGlobalDef::VA_DATA][OlympicGlobalDef::LAST_CHAMPION] = $champion;
    }

    public function getSilverPool()
    {
        return $this->globalInfo[OlympicGlobalDef::SILVER_POOL];
    }

    public function addSilverPool($addSilver)
    {
        $this->globalInfo[OlympicGlobalDef::SILVER_POOL] += $addSilver;
    }

    public function clrSilverPool()
    {
        OlympicDao::updOlympicGlobal(
            array(OlympicGlobalDef::SILVER_POOL => 0),
            OlympicGlobalDef::SPECIAL_ID
        );
    }

    public function getWinCont()
    {
        return $this->globalInfo[OlympicGlobalDef::VA_DATA][OlympicGlobalDef::WIN_CONT];
    }

    public function updWinCont($winCont)
    {
        $this->globalInfo[OlympicGlobalDef::VA_DATA][OlympicGlobalDef::WIN_CONT] = $winCont;
    }

    public function getAvgLvOfArena()
    {
        if(!isset($this->globalInfo[OlympicGlobalDef::VA_DATA][OlympicGlobalDef::AVG_LEVEL_OF_ARENA]))
        {
            return 0;
        }
        return $this->globalInfo[OlympicGlobalDef::VA_DATA][OlympicGlobalDef::AVG_LEVEL_OF_ARENA];
    }

    public function updAvgLvOfArena($avgLevel)
    {
        $this->globalInfo[OlympicGlobalDef::VA_DATA][OlympicGlobalDef::AVG_LEVEL_OF_ARENA] = $avgLevel;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */