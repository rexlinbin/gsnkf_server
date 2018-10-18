<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DesactTaskObj.class.php 205616 2015-10-28 11:01:30Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/desact/DesactTaskObj.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-10-28 11:01:30 +0000 (Wed, 28 Oct 2015) $
 * @version $Revision: 205616 $
 * @brief 
 *  
 **/
class DesactTaskObj
{
    private static $_instance = NULL;
    
    private $dataModify = NULL;
    
    private $field = NULL;
    
    public function __construct()
    {
        $confList = DesactDao::getLastCrossConfig(array('sess', 'update_time', 'version', 'va_config'));
        
        $this->dataModify = empty($confList[0]) ? array() : $confList[0];
        
        if (empty($this->dataModify))
        {
            $this->dataModify = $this->init();
        }
        
        $this->refreshConf();
    }
    
    public static function getInstance()
    {
        if (empty(self::$_instance))
        {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    public static function release()
    {
        if (!empty(self::$_instance))
        {
            self::$_instance = NULL;
        }
    }
    
    public function init()
    {
        if (FALSE == EnActivity::isOpen(ActivityName::DESACT))
        {
            Logger::info('act desact is not open.');
            return array();
        }
        
        $initArr = array();
        
        $newConf = EnActivity::getConfByName(ActivityName::DESACT);
        
        $now = Util::getTime();
        
        $initArr = array(
            DesactCrossDef::SQL_SESS => 1,
            DesactCrossDef::SQL_UPDATE_TIME => $now,
            DesactCrossDef::SQL_VERSION => $newConf['version'],
            DesactCrossDef::SQL_VA_CONFIG => array('config'=>array()),
        );
        
        $seed = intval( strtotime( date("Y-m-d", $now) ) );
        
        $arrRandData = DesactLogic::getRandList($newConf['data'], $seed, 0);
        
        $initArr[DesactCrossDef::SQL_VA_CONFIG]['config'] = $arrRandData;
        
        $ret = DesactDao::insertCrossConfig($initArr);
        
        $this->checkSqlConflict($ret);
        
        return $initArr;
    }
    
    public function refreshConf()
    {
        if (empty($this->dataModify))
        {
            return ;
        }
        
        if (empty($this->dataModify[DesactCrossDef::SQL_VA_CONFIG]['config']))
        {
            Logger::warning('config in va_config is empty. Skip refresh. Maybe closed desact,check it!');
            return ;
        }
        
        $now = Util::getTime();
        
        $roundDay = 0;
        
        foreach ($this->dataModify[DesactCrossDef::SQL_VA_CONFIG]['config'] as $key => $value)
        {
            $roundDay += $value[DesactDef::LAST_DAY];
        }
        
        $startTime = intval( strtotime( date( "Y-m-d", $this->dataModify[DesactCrossDef::SQL_UPDATE_TIME] ) ) );
        $endTime = $startTime + $roundDay * SECONDS_OF_DAY - 1;
        
        if ($now <= $endTime)
        {
            return ;
        }
        
        $newConf = EnActivity::getConfByName(ActivityName::DESACT);
        
        $version = 0;
        $conf = array();
        
        if ($newConf['start_time'] > $now || $newConf['end_time'] < $now)
        {
            $version = $this->dataModify['version'];
            
            foreach ($this->dataModify[DesactCrossDef::SQL_VA_CONFIG]['config'] as $key => $value)
            {
                $conf[$value[DesactDef::ID]] = $value;
            }
            
            ksort($conf);
        }
        else 
        {
            $conf = $newConf['data'];
            $version = $newConf['version'];
        }
        
        $arrOriRandData = $this->dataModify[DesactCrossDef::SQL_VA_CONFIG]['config'];
        
        $lastConf = end($arrOriRandData);
        $lastTid = $lastConf[DesactDef::ID];
        
        $seed = intval( strtotime( date( "Y-m-d", $now ) ) );
        
        $arrRandData = DesactLogic::getRandList($conf, $seed, $lastTid);
        
        $this->dataModify[DesactCrossDef::SQL_SESS] += 1;
        $this->dataModify[DesactCrossDef::SQL_UPDATE_TIME] = $now;
        $this->dataModify['version'] = $version;
        $this->dataModify[DesactCrossDef::SQL_VA_CONFIG]['config'] = $arrRandData;
        
        $ret = DesactDao::insertCrossConfig($this->dataModify);
        $this->checkSqlConflict($ret);
    }
    
    public function checkSqlConflict($arrRet)
    {
        if( $arrRet[DataDef::AFFECTED_ROWS] > 0 )
        {
            Logger::info("desact new conf:%s.",$this->dataModify);
            return ;
        }
        
        $curConf = DesactDao::getLastCrossConfig(array('sess', 'update_time', 'va_config'));
        
        $curConf = empty($curConf[0]) ? array() : $curConf[0];
        
        $dataZeroTime = intval( strtotime( date( "Y-m-d", $this->dataModify[DesactCrossDef::SQL_UPDATE_TIME] ) ) );
        $curConfZeroTime = intval( strtotime( date( "Y-m-d", $curConf[DesactCrossDef::SQL_UPDATE_TIME]) ) );
        
        if ( ($dataZeroTime != $curConfZeroTime)
            || ($this->dataModify[DesactCrossDef::SQL_SESS] != $curConf[DesactCrossDef::SQL_SESS])
            || ($this->dataModify[DesactCrossDef::SQL_VA_CONFIG] != $curConf[DesactCrossDef::SQL_VA_CONFIG]))
        {
            Logger::info('desact insert or update failed. data:%s, curConf:%s.',$this->dataModify,$curConf);
        }
        
        $this->dataModify = $curConf;
    }
    
    public function getConf()
    {
        return $this->dataModify[DesactCrossDef::SQL_VA_CONFIG]['config'];
    }
    
    public function getUpdateTime()
    {
        return $this->dataModify[DesactDef::SQL_UPDATE_TIME];
    }
    
    public function getSess()
    {
        return $this->dataModify[DesactCrossDef::SQL_SESS];
    }
    
    public function getInfo()
    {
        return $this->dataModify;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */