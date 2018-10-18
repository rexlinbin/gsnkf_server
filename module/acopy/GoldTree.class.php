<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GoldTree.class.php 159827 2015-03-03 06:34:29Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/acopy/GoldTree.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-03-03 06:34:29 +0000 (Tue, 03 Mar 2015) $
 * @version $Revision: 159827 $
 * @brief 
 *  
 **/
class GoldTree extends ACopyObj
{
    
    public function refreshDefeatNum()
    {
        parent::refreshDefeatNum();
		//刷新金币攻击次数
		$this->refreshGoldAtkNum();
		$this->rfrLevel();
		return $this->copyInfo['can_defeat_num'];
    }
    
    public function rfrBattleInfo()
    {
        $uid = $this->copyInfo['uid'];
        $this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_BATTLEINFO]
            = EnUser::getUserObj($uid)->getBattleFormation();
    }
    
    public function setBattleInfoValid($isValid)
    {
        $this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_BATTLEINFO_VALID] = $isValid;
    }
    
    public function isBattleInfoValid()
    {
        if(!isset($this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_BATTLEINFO_VALID]))
        {
            return FALSE;
        }
        return $this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_BATTLEINFO_VALID];
    }
    
    public function getBattleInfo()
    {
        if(!isset($this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_BATTLEINFO]))
        {
            return array();
        }
        return $this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_BATTLEINFO];
    }
	
	public static function getSilverReward($atkRet)
	{
	    $team2 = $atkRet['team2'];
	    $costHp = 0;
		if(!isset($team2[0]))
		{
		    throw new InterException('return of dohero is wrong!');
		}
		foreach($team2 as $index => $mstInfo)
		{
		    $costHp += $mstInfo['costHp'];
		}
		$level = EnUser::getUserObj()->getLevel();
		$reward['silver'] = self::getSilverByHurt($costHp);
		$uid = $atkRet['uid1'];
		$addition = EnCityWar::getCityEffect($uid, CityWarDef::GOLDTREE);
		Logger::info('EnCityWar::getCityEffect act. addition is %d',$addition);
		$reward['silver'] = intval($reward['silver'] * (1 + $addition/UNIT_BASE));
		$reward['hurt'] = $costHp;
		EnAchieve::updateGoldTree($uid, $costHp);
		return $reward;
	}
	
	/**
	 *	当伤害值<5000 时 银币=in（伤害值*0.1）
		当伤害值<10000 时 银币=in（伤害值*0.1+15000）
		当伤害值<20000 时 银币=in（伤害值*0.1+35000）
		当伤害值<50000 时 银币=in（伤害值*0.1+55000）
		当伤害值<100000 时 银币=in（伤害值*0.1+85000）
		当伤害值<200000 时 银币=in（伤害值*0.1+135000）
		当伤害值<300000 时 银币=in（伤害值*0.1+180000）
		当伤害值<400000 时 银币=in（伤害值*0.1+235000）
		当伤害值<500000 时 银币=in（伤害值*0.1+285000）
		当伤害值<600000 时 银币=in（伤害值*0.1+335000）
		当伤害值<800000 时 银币=in（伤害值*0.1+385000）
		当伤害值<1000000 时 银币=in（伤害值*0.1+435000）
		否则 银币=in（伤害值*0.1+4850000）
		(注意int为向下取整 ，还有判断条件里都是"<")

	 * @param int $hurt
	 * @return int
	 */
	private static function getSilverByHurt($hurt)
	{
	    $silver = intval($hurt*0.1);
	    foreach(ACopyConf::$HURT_TO_ADDTIONAL_SILVER as $hurtConf => $silverAddtion)
	    {
	        if($hurt >= $hurtConf)
	        {
	            continue;
	        }
	        $silver += $silverAddtion;
	        break;
	    }
	    $goldTree = MyACopy::getInstance()->getActivityCopyObj(ACT_COPY_TYPE::GOLDTREE_COPYID);
	    $silver += ($goldTree->getLevel() - 1) * 2500;
	    return intval($silver);
	}
	
	public function addExp($num)
	{
	    $exp = $this->getExp();
	    $this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_EXP] = $exp + $num;
	    $this->rfrLevel();
	}
	
	public function getExpTblId()
	{
	    $expTblId = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_GOLD_TREE_EXP_TBL];
	    return $expTblId;
	}
	
	private function rfrLevel()
	{
	    $exp = $this->getExp();
	    $expTblId = $this->getExpTblId();
	    $expTable = btstore_get()->EXP_TBL[$expTblId];
	    $lv = $this->getLevel();
	    while( isset( $expTable[$lv+1] ) && $exp >= $expTable[$lv+1])
	    {
	        $lv++;
	    }
	    $this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_LEVEL] = $lv;
	}
	
	public function getExp()
	{
	    if(!isset($this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_EXP]))
	    {
	        return 0;
	    }
	    return $this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_EXP];
	}
	
	public function getLevel()
	{
	    if(!isset($this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_LEVEL]))
	    {
	        return 1;
	    }
	    return $this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_LEVEL];
	}
	
	public function atkTreeByGold()
	{
	    $this->refreshGoldAtkNum();
	    $this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_GOLD_ATKNUM] += 1;
	}
	
	private function refreshGoldAtkNum()
	{
	    if(!isset($this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_GOLD_ATKTIME]) ||
	            (!Util::isSameDay($this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_GOLD_ATKTIME])))
	    {
	        $this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_GOLD_ATKTIME] = Util::getTime();
	        $this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_GOLD_ATKNUM] = 0;
	    }
	}
	
	public function getGoldAtkNum()
	{
	    if(!isset($this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_GOLD_ATKNUM]))
	    {
	        return 0;
	    }
	    return $this->copyInfo['va_copy_info'][ACT_COPY_FIELD::VA_GOLD_TREE_GOLD_ATKNUM];
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */