<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NCopyAtkInfo.class.php 87093 2014-01-15 13:04:55Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ncopy/NCopyAtkInfo.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-01-15 13:04:55 +0000 (Wed, 15 Jan 2014) $
 * @version $Revision: 87093 $
 * @brief 
 *  
 **/
//注意：NCopyAtkInfo继承自单例类AtkInfo
//在一个请求内部不能允许多个模块调用AtkInfo
class NCopyAtkInfo extends AtkInfo
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
     * @return NCopyAtkInfo
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
    
    private function setAtkInfo($atkInfo)
    {
        $this->atkInfo = $atkInfo;
    }
    
    private function addRoundNum($num)
    {
        if(!isset($this->atkInfo[ATK_INFO_FIELDS::ROUND_NUM]))
        {
            $this->atkInfo[ATK_INFO_FIELDS::ROUND_NUM] = 0;
        }
        $this->atkInfo[ATK_INFO_FIELDS::ROUND_NUM] += $num;
    }
    
    private function addDeadCardNum($num)
    {
        if(!isset($this->atkInfo[ATK_INFO_FIELDS::DEAD_CARD_NUM]))
        {
            $this->atkInfo[ATK_INFO_FIELDS::DEAD_CARD_NUM] = 0;
        }
        $this->atkInfo[ATK_INFO_FIELDS::DEAD_CARD_NUM] += $num;
    }
    
    private function addHpCost($num)
    {
        if(!isset($this->atkInfo[ATK_INFO_FIELDS::COST_HP]))
        {
            $this->atkInfo[ATK_INFO_FIELDS::COST_HP] = 0;
        }
        $this->atkInfo[ATK_INFO_FIELDS::COST_HP] += $num;
    }
    
    public function getRoundNum()
    {
        if(!isset($this->atkInfo[ATK_INFO_FIELDS::ROUND_NUM]))
        {
            $this->atkInfo[ATK_INFO_FIELDS::ROUND_NUM] = 0;
        }
        return $this->atkInfo[ATK_INFO_FIELDS::ROUND_NUM];
    }
    
    public function getDeadCardNum()
    {
        if(!isset($this->atkInfo[ATK_INFO_FIELDS::DEAD_CARD_NUM]))
        {
            $this->atkInfo[ATK_INFO_FIELDS::DEAD_CARD_NUM] = 0;
        }
        return $this->atkInfo[ATK_INFO_FIELDS::DEAD_CARD_NUM];
    }
    
    public function getCostHp()
    {
        if(!isset($this->atkInfo[ATK_INFO_FIELDS::COST_HP]))
        {
            $this->atkInfo[ATK_INFO_FIELDS::COST_HP] = 0;
        }
        return $this->atkInfo[ATK_INFO_FIELDS::COST_HP];
    }
    
    /**
     * @param array $atkRet
     */
    public function statisticOnDoneBattle($atkRet)
    {
        $roundNum = $atkRet['round'];
        $team1 = $atkRet['team1'];
        $deadNum = 0;
        $costHp = 0;
        foreach($team1 as $index => $cardInfo)
        {
            if($cardInfo['hp'] <= 0)
            {
                $deadNum++;
            }
            $costHp += intval($cardInfo['costHp']);
        }
        $this->addRoundNum($roundNum);
        $this->addDeadCardNum($deadNum);
        $this->addHpCost($costHp);
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */