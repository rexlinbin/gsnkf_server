<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MysteryShop.class.php 246749 2016-06-17 03:18:43Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mysteryshop/MysteryShop.class.php $
 * @author $Author: QingYao $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-06-17 03:18:43 +0000 (Fri, 17 Jun 2016) $
 * @version $Revision: 246749 $
 * @brief 
 *  
 **/
class MysteryShop implements IMysteryShop
{
	public function previewResolveHero($arrHid)
	{
		return $this->doResolveHero($arrHid, true);
	}
	
	public function resolveHero($arrHid)
	{
		return $this->doResolveHero($arrHid, false);
	}
	
    private function doResolveHero($arrHid, $preview = false)
    {
        Logger::trace('resolver.resolveHero start.params--arrHid:%s.',$arrHid);
        if(empty($arrHid) || (is_array($arrHid) == FALSE))
        {
            throw new FakeException('error param %s.please check.',$arrHid);
        }
        if(EnSwitch::isSwitchOpen(SwitchDef::REFINEFURNACE) == FALSE)
        {
            throw new FakeException('switch refine furnace is not open.can not resolve hero.');
        }
        if(count($arrHid) > ResolverConf::$MAX_RESOLVE_HERO_NUM)
        {
            throw new FakeException('the max resolved hero num is %s you request num is %s',ResolverConf::$MAX_RESOLVE_HERO_NUM,count($arrHid));
        }
        $resolveGot = array();
        foreach($arrHid as $index => $hid)
        {
            $tempGot = self::resolveOneHero($hid);
            $resolveGot = self::array_add($resolveGot, $tempGot);
        }
        $userObj = Enuser::getUserObj();
        self::getResolveGot($resolveGot);
        if(! $preview)
        {
        	$userObj->update();
        }
        Logger::trace('resolver.resolveHero end.result %s.',$resolveGot);
        return $resolveGot;
    }
    
    private static function resolveOneHero($hid)
    {
        $userObj = EnUser::getUserObj();
        $heroMng = $userObj->getHeroManager();
        $heroObj = $heroMng->getHeroObj($hid);
        if($heroObj == NULL)
        {
            throw new FakeException('no such heroObj %s.',$hid);
        }
        if($heroObj->isLocked())
        {
            throw new FakeException('this hero %d is locked can not be resolved',$hid);
        }
        if($heroObj->canBeDel() == FALSE)
        {
            throw new FakeException('this hero %s can not be deleted.',$hid);
        }
        $starLv = Creature::getHeroConf($heroObj->getHtid(), CreatureAttr::STAR_LEVEL);
        if(in_array($starLv, HeroConf::$RESOLVED_HERO_STARLV) == FALSE)
        {
            throw new FakeException('hero %s htid %s is not in starlv %s these can be resolved.',$hid,$heroObj->getHtid(),HeroConf::$RESOLVED_HERO_STARLV);
        }
        $soul = $heroObj->getSoul() + intval($heroObj->getConf(CreatureAttr::SOUL));
        $silver = intval($heroObj->getConf(CreatureAttr::LVLUP_RATIIO)/100 * $soul);
        $conf = btstore_get()->RESOLVER[ResolverDef::RESOLVER_TYPE_ONLY_HERO];
        $gotSoul = intval($soul * ($conf['soul_ratio']/UNIT_BASE));
        $gotSilver = intval($silver * ($conf['silver_ratio']/UNIT_BASE));
        $gotJewel = $heroObj->getConf(CreatureAttr::JEWEL_NUM);
        $heroMng->delHeroByHid($hid);
        return array(
                ResolverDef::RESOLVER_GOT_TYPE_SOUL=>$gotSoul,
                ResolverDef::RESOLVER_GOT_TYPE_SILVER=>$gotSilver,
                ResolverDef::RESOLVER_GOT_TYPE_JEWEL=>$gotJewel,
                );
    }
    
	public function previewResolveHero2Soul($arrHid)
	{
		return $this->doResolveHero2Soul($arrHid, true);
	}
	
	public function resolveHero2Soul($arrHid)
	{
		return $this->doResolveHero2Soul($arrHid, false);
	}
    
    /**
     * (non-PHPdoc)
     * @see IMysteryShop::resolveHero2Soul()
     */
    private function doResolveHero2Soul($arrHid, $preview = false)
    {
    	Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
    	
    	// 参数必须是数组
    	if (empty($arrHid) || !is_array($arrHid))
        {
            throw new FakeException('empty or not array, invalid param[%s]', $arrHid);
        }
        
        // 转化为整数
        $arrHid = array_unique($arrHid);
        foreach ($arrHid as $key => $hid)
        {
        	$arrHid[$key] = intval($hid);
        }
        
    	// 炼化炉没开
    	if (!EnSwitch::isSwitchOpen(SwitchDef::REFINEFURNACE))
    	{
    		throw new FakeException('refine furnace is not open');
    	}
    	
    	// 是否超过最大限
    	if (count($arrHid) > ResolverConf::$MAX_RESOLVE_HERO_NUM_2_SOUL)
    	{
    		throw new FakeException('exceed max[%d], curr[%d]', ResolverConf::$MAX_RESOLVE_HERO_NUM_2_SOUL, count($arrHid));
    	}
    	
    	$userObj = EnUser::getUserObj();
    	$heroMng = $userObj->getHeroManager();
    	$bag = BagManager::getInstance()->getBag();
    	
    	// 循环将武将化魂
    	$costSilver = 0;
    	$arrGot = array();
    	foreach ($arrHid as $aHid)
    	{
    		$heroObj = $heroMng->getHeroObj($aHid);
    		
    		// 是否有武将
    		if ($heroObj == NULL)
    		{
    			throw new FakeException('hero[%d] not exist', $aHid);
    		}
    		 
    		// 是否上锁
    		if ($heroObj->isLocked())
    		{
    			throw new FakeException('hero[%d] locked', $aHid);
    		}
    		 
    		// 是否能被删除（这里判断了 是否在阵上，是否在小伙伴，是否在助战军，是否有装备，是否是主角，进阶是否大于1，是否在过关斩将里）
    		if (!$heroObj->canBeDel())
    		{
    			throw new FakeException('hero[%d] can not be del', $aHid);
    		}
    		 
    		// 小于4星或者大于5星的武将不能化魂
    		$starLv = Creature::getHeroConf($heroObj->getHtid(), CreatureAttr::STAR_LEVEL);
    		if ($starLv < 4 || $starLv > 5)
    		{
    			throw new FakeException('hero[%d] htid[%d] startLv[%d] less than 4 or more than 5', $aHid, $heroObj->getHtid(), $starLv);
    		}
    		
    		// 强化次数大于等于1的不能化魂
    		if ($heroObj->getSoul() > 0)
    		{
    			throw new FakeException('hero[%d] soul[%d], has soul', $aHid, $heroObj->getSoul());
    		}
    		
    		// 已服用丹药的武将不能化魂
    		if ($heroObj->hasPill())
    		{
    			throw new FakeException('hero[%d] has pill', $aHid);
    		}
    		
    		// 变身的武将不能化魂
    		$transfer = $heroObj->getTransfer();
    		if (!empty($transfer))
    		{
    			throw new FakeException('hero[%d] has transfer[%d]', $hid, $transfer);
    		}
    		
    		// 有觉醒不能化魂
    		if ($heroObj->hasTalent()) 
    		{
    			throw new FakeException('hero[%d] has talent', $hid);
    		}
    		
    		// 化魂的碎片模板消息，只能化魂成一种碎片
    		$FragTplInfo = Creature::getHeroConf($heroObj->getHtid(), CreatureAttr::RESOLVE_2_SOUL_FRAG_INFO);
    		if (count($FragTplInfo) != 1) 
    		{
    			throw new ConfigException('hid[%d] htid[%d], resolve 2 multi type frag or empty[%s]', $aHid, $heroObj->getHtid(), $FragTplInfo);
    		}
    		foreach ($FragTplInfo as $FragTplId => $num){break;}
    		
    		// 是否是武将碎片模板
    		if (ItemAttr::getItemAttr($FragTplId, ItemDef::ITEM_ATTR_NAME_TYPE) != ItemDef::ITEM_TYPE_HEROFRAG) 
    		{
    			throw new ConfigException('invalid hero frag tpl id[%d]', $FragTplId);
    		}
    		
    		// 这个武将碎片合成的武将htid和当前武将是否一致
    		$arrUseAcq = ItemAttr::getItemAttr($FragTplId, ItemDef::ITEM_ATTR_NAME_USE_ACQ);
    		$acqHtid = 0;
    		if (!empty($arrUseAcq[ItemDef::ITEM_ATTR_NAME_USE_ACQ_HERO]))
    		{
    			foreach ($arrUseAcq[ItemDef::ITEM_ATTR_NAME_USE_ACQ_HERO] as $acqHtid => $v){break;}
    		}
    		if ($acqHtid != $heroObj->getHtid()) 
    		{
    			throw new ConfigException('htid diff, curr hero hitd[%d], frag tpl id[%d], frag form htid[%d]', $heroObj->getHid(), $FragTplId, $acqHtid);
    		}
    		
    		// 获取消耗和获得
    		if (empty(btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_HERO_2_SOUL_COST][$starLv])) 
    		{
    			throw new ConfigException('no cost config, curr startLv[%d], cost config[%s]', $starLv, btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_HERO_2_SOUL_COST]);
    		}
    		$costSilver += intval(btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_HERO_2_SOUL_COST][$starLv]);
    		if (empty($arrGot[$FragTplId])) 
    		{
    			$arrGot[$FragTplId] = 0;
    		}
    		$arrGot[$FragTplId] += $num;
    		
    		// 删掉武将 
    		$heroMng->delHeroByHid($aHid);
    	}
    	
    	if (!$userObj->subSilver($costSilver)) 
    	{
    		throw new FakeException('not enough silver, curr[%d], need[%d]', $userObj->getSilver(), $costSilver);
    	}
    	
    	if (!empty($arrGot))
    	{
    		if (!$bag->addItemsByTemplateID($arrGot))
    		{
    			throw new FakeException('full bag. add item tpls[%s] failed', $arrGot);
    		}
    	}
    	
    	if( !$preview)
    	{
    		$userObj->update();
    		$bag->update();
    	}
    	Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $arrGot);
    	return $arrGot;
    }
    
    public function resolveHeroJH($arrItemInfo)
    {
    	return $this->doResolveHeroJH($arrItemInfo, false);
    }
    
 	public function previewResolveHeroJH($arrItemInfo)
    {
    	return $this->doResolveHeroJH($arrItemInfo, true);
    }
    
    private function doResolveHeroJH($arrItemInfo, $preview = false)
    {
    	Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
    	
    	$arrRealParam = array();
    	foreach ($arrItemInfo as $aItemId => $aItemNum)
    	{
    		$arrRealParam[intval($aItemId)] = intval($aItemNum);
    	}
    	
    	$ret = Resolve::HeroJHResolve($arrRealParam, $preview);
    	Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
    	return $ret;
    }
    
    public function resolveItem($arrItemId)
    {
    	return $this->doResolveItem($arrItemId, false);
    }
    
	public function previewResolveItem($arrItemId)
    {
    	return $this->doResolveItem($arrItemId, true);
    }
    
    private function doResolveItem($arrItemId, $preview)
    {
        Logger::trace('mysteryshop.resolveItem start.param arrItemId:%s.',$arrItemId);
        $ret = Resolve::armResolve($arrItemId, $preview);
        return $ret;
    }
    
    public function resolveTreasure($arrItemId)
    {
    	return $this->doResolveTreasure($arrItemId, false);
    }
    
	public function previewResolveTreasure($arrItemId)
    {
    	return $this->doResolveTreasure($arrItemId, true);
    }
    
    private function doResolveTreasure($arrItemId, $preview)
    {
    	Logger::trace('mysteryshop.resolveTreasure start.param arrItemId:%s.',$arrItemId);
    	$ret = Resolve::treasureResolve($arrItemId, $preview);
    	return $ret;
    }
    
    public function rebornItem($arrItemId)
    {
    	return $this->doRebornItem($arrItemId, false);
    }
    
	public function previewRebornItem($arrItemId)
    {
    	return $this->doRebornItem($arrItemId, true);
    }
    
    private function doRebornItem($arrItemId, $preview)
    {
        Logger::trace('mysteryshop.rebornItem start.param arrItemId:%s.',$arrItemId);
        $ret = Resolve::armReborn($arrItemId, $preview);
        return $ret;
    }
    
	public function rebornTreasure($arrItemId)
    {
    	return $this->doRebornTreasure($arrItemId, false);
    }
    
	public function previewRebornTreasure($arrItemId)
    {
    	return $this->doRebornTreasure($arrItemId, true);
    }
    
    private function doRebornTreasure($arrItemId, $preview)
    {
        Logger::trace('mysteryshop.rebornTreasure start.param arrItemId:%s.',$arrItemId);
        $ret = Resolve::treasureReborn($arrItemId, $preview);
        return $ret;
    }
    
	public function resolveDress($arrItemId)
    {
    	return $this->doResolveDress($arrItemId, false);
    }
    
	public function previewResolveDress($arrItemId)
    {
    	return $this->doResolveDress($arrItemId, true);
    }   
    
    private function doResolveDress($arrItemId, $preview)
    {
        Logger::trace('mysteryshop.resolveDress start.param arrItemId:%s.',$arrItemId);
        $ret = Resolve::dressResolve($arrItemId, $preview);
        return $ret;
    }
    
    public function rebornDress($arrItemId)
    {
    	return $this->doRebornDress($arrItemId, false);
    }
    
	public function previewRebornDress($arrItemId)
    {
    	return $this->doRebornDress($arrItemId, true);
    }  
    
    private function doRebornDress($arrItemId, $preview)
    {
        Logger::trace('mysteryshop.rebornDress start.param arrItemId:%s.',$arrItemId);
        $ret = Resolve::dressReborn($arrItemId, $preview);
        return $ret;
    }
    
    public function resolveRune($arrRuneItemId, $arrTreasItemId = array())
    {
    	return $this->doResolveRune($arrRuneItemId, $arrTreasItemId, false);
    }
    
	public function previewResolveRune($arrRuneItemId, $arrTreasItemId = array())
    {
    	return $this->doResolveRune($arrRuneItemId, $arrTreasItemId, true);
    }  
    
    private function doResolveRune($arrRuneItemId, $arrTreasItemId = array(), $preview = false)
    {
    	Logger::trace('mysteryshop.resolveRune start.param arrRuneItemId:%s, arrTreasItemId:%s',$arrRuneItemId, $arrTreasItemId);
    	$ret = Resolve::runeResolve($arrRuneItemId, $arrTreasItemId, $preview);
    	return $ret;
    }
    
    public function rebornPocket($arrItemId)
    {
    	return $this->doRebornPocket($arrItemId, false);
    }
    
    public function previewRebornPocket($arrItemId)
    {
    	return $this->doRebornPocket($arrItemId, true);
    }
    
    private function doRebornPocket($arrItemId, $preview)
    {
    	Logger::trace('mysteryshop.rebornPocket start.param arrItemId:%s.',$arrItemId);
    	$ret = Resolve::pocketReborn($arrItemId, $preview);
    	return $ret;
    }
    
	public function rebornFightSoul($arrItemId)
    {
    	return $this->doRebornFightSoul($arrItemId, false);
    }
    
    public function previewRebornFightSoul($arrItemId)
    {
    	return $this->doRebornFightSoul($arrItemId, true);
    }
    
    public function doRebornFightSoul($arrItemId, $preview)
    {
    	Logger::trace('mysteryshop.rebornFightSoul start.param arrItemId:%s.',$arrItemId);
    	$ret = Resolve::fightsoulReborn($arrItemId, $preview);
    	return $ret;
    }
    
    /**
     * 武将重生    将武将的等级设为1，将魂重设为0，进阶次数重设为0  返回强化消耗的银币和将魂、进阶消耗的武将、物品
     * 只能重生非主角武将    非主角武将进阶时htid是不变化的   所以重生时不需要改武将的htid
     * (non-PHPdoc)
     * @see IMysteryShop::rebornHero()
     */
	public function rebornHero($hid)
    {
    	return $this->doRebornHero($hid, false);
    }
    
    public function previewRebornHero($hid)
    {
    	return $this->doRebornHero($hid, true);
    }
    
    public function doRebornHero($hid, $preview)
    {
        Logger::trace('MysteryShop.resetHero start.params hid:%s.',$hid);
        $hid = intval($hid);
        if(empty($hid))
        {
            throw new FakeException('error params hid %s.',$hid);
        }
        if(EnSwitch::isSwitchOpen(SwitchDef::REFINEFURNACE) == FALSE)
        {
            throw new FakeException('switch refine furnace is not open.can not reborn hero.');
        }
        $userObj = Enuser::getUserObj();
        $heroMng = $userObj->getHeroManager();
        $heroObj = $heroMng->getHeroObj($hid);
        if(empty($heroObj))
        {
            throw new FakeException('no such heroObj %s.',$hid);
        }
        if($heroObj->isLocked())
        {
            throw new FakeException('this hero %d is locked can not be reborn',$hid);
        }
        $transfer = $heroObj->getTransfer();
        if(!empty($transfer))
        {
            Logger::info('hero %d has transfer %d',$hid,$transfer);
            $heroObj->unsetTransfer();
            $heroObj->unsetDXTrans();
        }
        if($heroObj->isEquiped() || ($heroObj->isMasterHero()) 
                || EnFormation::isHidInFormation($hid, $userObj->getUid()))
        {
            throw new FakeException('hero %s cant be reseted.',$hid);
        }
        if($heroObj->getLevel() <= 1)
        {
            throw new FakeException('the hero %s level is %s.can not be reseted',$hid,$heroObj->getLevel());
        }
        //消耗金币
        $needGold = (1 + $heroObj->getEvolveLv()) * ($heroObj->getConf(CreatureAttr::REBORN_GOLD_BASE));
        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_MYSTERYSHOP_REBORN_HERO) == FALSE)
        {
            throw new FakeException('reBornHero.sub gold failed.');
        }
        //获得将魂、银币        物品、卡牌
        $rebornGot = array();
        $soul = $heroObj->getSoul();
        $silver = intval($heroObj->getConf(CreatureAttr::LVLUP_RATIIO)/100 * $soul);
        $conf = btstore_get()->RESOLVER[ResolverDef::RESOLVER_TYPE_ONLY_HERO];
        $rebornGot[ResolverDef::RESOLVER_GOT_TYPE_SOUL] = intval($soul * ($conf['soul_ratio']/UNIT_BASE));
        $rebornGot[ResolverDef::RESOLVER_GOT_TYPE_SILVER] = intval($silver * ($conf['silver_ratio']/UNIT_BASE));
        $evSilver = 0;
        for($i=0;$i<$heroObj->getEvolveLv();$i++)
        {
            $evlTblId = HeroLogic::getEvolveTbl($heroObj->getHtid(), $i);
            $evlTblConf = btstore_get()->HERO_CONVERT[$evlTblId];
            $evSilver += intval(btstore_get()->HERO_CONVERT[$evlTblId]['needSilver']);
            foreach($evlTblConf['arrNeedItem'] as $itemTmplId => $itemNum)
            {
                if(!isset($rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTmplId]))
                {
                    $rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTmplId] = 0;
                }
                $rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTmplId] += $itemNum;
            }
            foreach($evlTblConf['arrNeedHero'] as $index => $heroConf)
            {
                $htid = $heroConf[0];
                $heroLevel = $heroConf[1];
                $heroNum = $heroConf[2];
                $rebornGot[ResolverDef::RESOLVER_GOT_TYPE_HERO][] = array(
                        'htid'=>$htid,
                        'level'=>$heroLevel,
                        'num'=>$heroNum
                        );
            }
        }
        $rebornGot[ResolverDef::RESOLVER_GOT_TYPE_SILVER] += $evSilver;
        $pillInfo = $heroObj->getPillInfo();
        foreach($pillInfo as $index => $indexInfo)
        {
            foreach($indexInfo as $itemTplId => $num)
            {
                if(!isset($rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTplId]))
                {
                    $rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTplId] = $num;
                }
                else
                {
                    $rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTplId] += $num;
                }
            }
        }
        $evolveLv = $heroObj->getEvolveLv();
        $level = $heroObj->getLevel();
        $heroObj->resetHero();
        self::getResolveGot($rebornGot);
        
        if( !$preview )
        {
        	$userObj->update();
        	$bag = BagManager::getInstance()->getBag();
        	$bag->update();
        }
        Logger::info('reborn hero.hid:%d htid:%d evolve_level:%d level:%d.',$hid,$heroObj->getHtid(),$evolveLv,$level);
        Logger::trace('MysteryShop.rebornHero end.result %s.',$rebornGot);
        return $rebornGot;        
    }
    
    /**
     * 将数组arr2加到数组arr1中，如果arr1中存在这个key   合并两个数组中的值（如果是int型相加，如果是array型array_merge）
     * @param array $arr1
     * [
     *     silver:int
     *     soul:int
     *     jewel:int
     *     item:array
     *         [
     *             item_template_id=>num
     *         ]
     *     hero:array
     *         [
     *             htid=>num
     *         ]
     * ]
     * @param array $arr2  格式同arr1
     * @return array 格式同arr1
     */
    private static function array_add($arr1,$arr2)
    {
        foreach($arr2 as $key => $value)
        {
            if(!isset($arr1[$key]))
            {
                $arr1[$key] = $value;
                continue;
            }
            if(is_int($value))
            {
                $arr1[$key] += $value;
            }
            else if(is_array($value))
            {
                foreach($value as $tid => $num)
                {
                    if(!isset($arr1[$key][$tid]))
                    {
                        $arr1[$key][$tid] = 0;
                    }
                    $arr1[$key][$tid] += $num;
                }
            }
        }
        return $arr1;
    }
    
    /**
     * 
     * @param array $got
     * [
     *     soul:int
     *     silver:int
     *     jewel:int
     *     item:array
     *         [
     *             item_template_id=>num
     *         ]
     *     hero:array
     *         [
     *             array
     *             [
     *                 htid:int
     *                 level:int
     *                 num:int
     *             ]
     *         ]
     * ]
     */
    private static function getResolveGot($got)
    {
        $userObj = EnUser::getUserObj();
        $heroMng = $userObj->getHeroManager();
        $bag = BagManager::getInstance()->getBag();
        foreach($got as $type => $value)
        {
            switch($type)
            {
                case ResolverDef::RESOLVER_GOT_TYPE_SILVER:
                    $userObj->addSilver($value);
                    break;
                case ResolverDef::RESOLVER_GOT_TYPE_SOUL:
                    $userObj->addSoul($value);
                    break;
                case ResolverDef::RESOLVER_GOT_TYPE_JEWEL:
                    $userObj->addJewel($value);
                    break;
                case ResolverDef::RESOLVER_GOT_TYPE_ITEM:
                    $bag->addItemsByTemplateID($value,true);
                    break;
                case ResolverDef::RESOLVER_GOT_TYPE_HERO:
                    foreach($value as $index => $heroesGot)
                    {
                        $num = $heroesGot['num'];
                        $level = $heroesGot['level'];
                        $htid = $heroesGot['htid'];
                        for($i=0;$i<$num;$i++)
                        {
                            $heroMng->addNewHeroWithLv($htid, $level);
                        }
                    }
                    break;
            }
        }
    }
    
    
    public function getShopInfo()
    {
        Logger::trace('MysteryShop.getShopInfo start');
        if(EnSwitch::isSwitchOpen(SwitchDef::REFINEFURNACE) == FALSE)
        {
            throw new FakeException('switch refine furnace is not open.can not get mystery shopinfo.');
        }
        $shop = new MyMysteryShop();
        $shopInfo = $shop->getShopInfo();
        if(empty($shopInfo['goods_list']))
        {
            $shop->refreshGoodsList();
            $shopInfo = $shop->getShopInfo();
        }
        $shop->update();
        return $shopInfo;
    }
    
    /**
     * 
     * @param int $type  1.金币刷新  2.物品刷新 3.免费系统刷新
     */
    public function playerRfrGoodsList($type)
    {
        $bag = NULL;
        $userObj = NULL;
        if(EnSwitch::isSwitchOpen(SwitchDef::REFINEFURNACE) == FALSE)
        {
            throw new FakeException('switch refine furnace is not open.can not refresh mystery goodslist.');
        }
        $shop = new MyMysteryShop();
        $sysRfrNum = $shop->getSysRfrNum();
        if($sysRfrNum >= 1 && ($type == MysteryShopDef::MYSTERY_REFR_LIST_TYPE_GOLD || 
                $type == MysteryShopDef::MYSTERY_REFR_LIST_TYPE_ITEM))
        {
            throw new FakeException('user has free num %d rfr type is %d',$sysRfrNum,$type);
        }
        if($type == MysteryShopDef::MYSTERY_REFR_LIST_TYPE_FREE)
        {
            if($shop->sysRfrGoodsList() == FALSE)
            {
                throw  new FakeException('sysRfrGoodsList failed.');
            }
        }
        else if($type == MysteryShopDef::MYSTERY_REFR_LIST_TYPE_GOLD)
        {
            $userObj = EnUser::getUserObj();
            $refrNum = $shop->getPlayerRfrNum();
            $vip = $userObj->getVip();
            if($refrNum >= btstore_get()->VIP[$vip]['mysteryRfrTimes'])
            {
                throw new FakeException('no refresh num.have refreshed %d times.has only %d times.',$refrNum,btstore_get()->VIP[$vip]['mysteryRfrTimes']);
            }
            $needGold = btstore_get()->MYSTERYSHOP['refresh_gold_base'] + 
                $refrNum * btstore_get()->MYSTERYSHOP['refresh_gold_inc'];
            if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_MYSTERYSHOP_REFR) == FALSE)
            {
                throw new FakeException('playerRfrGoodsList.sub gold failed.');
            }
            $shop->playerRfrGoodsListByGold();
        }
        else if($type == MysteryShopDef::MYSTERY_REFR_LIST_TYPE_ITEM)
        {
            $bag = BagManager::getInstance()->getBag();
            $itemTmplId = btstore_get()->MYSTERYSHOP['refresh_item'];
            //TODO:每次刷新都是消耗一个物品
            if($bag->deleteItembyTemplateID($itemTmplId, 1) == FALSE)
            {
                throw new FakeException('delete item from bag failed.');
            }
            $shop->refreshGoodsList();
        }
        else 
        {
            throw new FakeException('invalide playerRfrGoodsList type %s.',$type);
        }
        EnActive::addTask(ActiveDef::MYSTERYSHOP);
        $shopInfo = $shop->getShopInfo();
        $shop->update();
        if($userObj != NULL)
        {
            $userObj->update();
        }
        if($bag != NULL)
        {
            $bag->update();
        }
        $uid = RPCContext::getInstance()->getUid();
        EnMission::doMission($uid, MissionType::REF_MYSTSHOP);
        return $shopInfo;
    }
    
    public function buyGoods($goodsId)
    {
        Logger::trace('MysteryShop.buyGoods start.params goodsid:%d.',$goodsId);
        if(empty($goodsId))
        {
            throw new FakeException('error params goodsId %s.',$goodsId);
        }
        if(EnSwitch::isSwitchOpen(SwitchDef::REFINEFURNACE) == FALSE)
        {
            throw new FakeException('switch refine furnace is not open.can not buy mystery goods.');
        }
        $shop = new MyMysteryShop();
//         if($shop->canSysRfrGoodsList())
//         {
//             Logger::fatal('should refresh goodslist by qianduan.');
//         }
        $goodsList = $shop->getGoodsList();
        if(in_array($goodsId, $goodsList) == FALSE)
        {
            throw new FakeException('now the goodslist is %s.can not buy goods %s.',$goodsList,$goodsId);
        }
        $ret = $shop->exchange($goodsId);
        $shop->update();
        Logger::trace('MysteryShop.buyGoods start.result %s.',$ret);
        return $ret;
    }
    
    public static function getMaxFreeSysRfrNum($uid)
    {
        $userObj = Enuser::getUserObj($uid);
        $vip = $userObj->getVip();
        return btstore_get()->VIP[$vip]['mysSysRfrNum'];
    }
    
	public function rebornOrangeHero($hid)
    {
    	return $this->doRebornOrangeHero($hid, false);
    }
    
    public function previewRebornOrangeHero($hid)
    {
    	return $this->doRebornOrangeHero($hid, true);
    }
    
    private function doRebornOrangeHero($hid, $preview)
    {
        Logger::info('mysteryshop.rebornOrangeHero start.hero %d',$hid);
        if (EnSwitch::isSwitchOpen(SwitchDef::REFINEFURNACE) == FALSE)
        {
            throw new FakeException('switch refine furnace is not open.can not reborn orange hero.');
        }
        $userObj = EnUser::getUserObj();
        $heroMng = $userObj->getHeroManager();
        $heroObj = $heroMng->getHeroObj($hid);
        if (empty($heroObj))
        {
            throw new FakeException('no such heroObj %s.',$hid);
        }
        if ($heroObj->isLocked())
        {
            throw new FakeException('this hero %d is locked can not be reborn',$hid);
        }
        $transfer = $heroObj->getTransfer();
        if (!empty($transfer))
        {
            Logger::info('hero %d has transfer %d',$hid,$transfer);
            $heroObj->unsetTransfer();
            $heroObj->unsetDXTrans();
        }
        if($heroObj->getStarLv() < 6)
        {
            throw new FakeException('hero %d star lv is %d is not orangecard.',$hid,$heroObj->getStarLv());
        }
        if (EnFormation::isHidInFormation($hid, $userObj->getUid()) 
                || EnFormation::isHidInExtra($hid, $userObj->getUid())
                || EnFormation::isHidInAttrExtra($hid, $userObj->getUid()))
        {
            throw new FakeException("hero %d is in formation or in extra or in attr extra",$hid);
        }
        //返还进阶材料
        $rebornGot = array();
        $rebornSilver = 0;
        $orangeHeroEvLv = $heroObj->getEvolveLv();
        for ($i=0; $i<$orangeHeroEvLv; $i++)
        {
            $evlTblId = HeroLogic::getEvolveTbl($heroObj->getHtid(), $i);
            $evlTblConf = btstore_get()->HERO_CONVERT[$evlTblId];
            $rebornSilver += intval(btstore_get()->HERO_CONVERT[$evlTblId]['needSilver']);
            foreach ($evlTblConf['arrNeedItem'] as $itemTmplId => $itemNum)
            {
                if (!isset($rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTmplId]))
                {
                    $rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTmplId] = 0;
                }
                $rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTmplId] += $itemNum;
            }
            foreach ($evlTblConf['arrNeedHero'] as $index => $heroConf)
            {
                $htid = $heroConf[0];
                $heroLevel = $heroConf[1];
                $heroNum = $heroConf[2];
                $rebornGot[ResolverDef::RESOLVER_GOT_TYPE_HERO][] = array(
                        'htid'=>$htid,
                        'level'=>$heroLevel,
                        'num'=>$heroNum
                );
            }
        }
        $unDevelopId = Creature::getHeroConf($heroObj->getHtid(), CreatureAttr::UNDEVELOP_TBL_ID);
        $developTbl = btstore_get()->HERO_DEVELOP[$unDevelopId];
        //返还进化材料
        foreach ($developTbl['arrNeedItem'] as $itemTmplId => $itemNum)
        {
            if (!isset($rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTmplId]))
            {
                $rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTmplId] = 0;
            }
            $rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTmplId] += $itemNum;
        }
        foreach ($developTbl['arrNeedHero'] as $index => $heroConf)
        {
            $htid = $heroConf[0];
            $heroLevel = $heroConf[1];
            $heroNum = $heroConf[2];
            $rebornGot[ResolverDef::RESOLVER_GOT_TYPE_HERO][] = array(
                    'htid'=>$htid,
                    'level'=>$heroLevel,
                    'num'=>$heroNum
            );
        }
        $rebornSilver += $developTbl['needSilver'];
        $rebornGot['silver'] = $rebornSilver;
        $talentInfo = $heroObj->getCurTalent();
        foreach ($talentInfo as $talentIndex => $talentId)
        {
            if(HeroLogic::isTalentHcopyPassed($userObj->getUid(), $hid, $talentIndex) == FALSE)
            {
                $heroObj->addSealedTalent($talentIndex);
            }
        }
        $needGold = Creature::getHeroConf($heroObj->getHtid(), CreatureAttr::UNDEVELOP_NEED_EXTRA_GOLD) 
            + Creature::getHeroConf($heroObj->getHtid(), CreatureAttr::REBORN_GOLD_BASE) * ($heroObj->getEvolveLv() + 1);
        if ($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_MYSTERYSHOP_REBORN_HERO) == FALSE)
        {
            throw new FakeException('sub gold faild');
        }
        $toHtid = $developTbl['fromHtid'];
        $needEvLv = $developTbl['needEvolveLv'];
        //武将变成紫将   进阶等级变成+7
        $preHtid = $heroObj->getHtid();
        $heroObj->unDevelop($toHtid,$needEvLv);
        self::getResolveGot($rebornGot);
        
        if( ! $preview )
        {
        	$userObj->update();
       		BagManager::getInstance()->getBag()->update();
        }
        Logger::info('mysteryshop.rebornOrangeHero end.hero %d htid %d evolvelv %d tohtid %d',
                $hid,$preHtid,$orangeHeroEvLv,$toHtid);
        return array(
                'reborn_get'=>$rebornGot,
                'hero_info'=>$heroObj->getInfo()
                );
    }
    
	public function rebornRedHero($hid)
    {
    	return $this->doRebornRedHero($hid, false);
    }
    
    public function previewRebornRedHero($hid)
    {
    	return $this->doRebornRedHero($hid, true);
    }
    
    /**
     * (non-PHPdoc)
     * @see IMysteryShop::rebornRedHero()
     */
    private function doRebornRedHero($hid, $preview)
    {
    	Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
    	
    	// 炼化炉没开
    	if (!EnSwitch::isSwitchOpen(SwitchDef::REFINEFURNACE))
    	{
    		throw new FakeException('refine furnace is not open');
    	}
    	
    	$userObj = EnUser::getUserObj();
    	$heroMng = $userObj->getHeroManager();
    	
    	// 武将不存在
    	$heroObj = $heroMng->getHeroObj($hid);
    	if (empty($heroObj))
    	{
    		throw new FakeException('hero[%d] not exist', $hid);
    	}
    	
    	// 武将上锁不能重生
    	if ($heroObj->isLocked())
    	{
    		throw new FakeException('hero[%d] is locked', $hid);
    	}
    	
    	// 如果有未确认的变身信息，去掉
    	$transfer = $heroObj->getTransfer();
    	if (!empty($transfer))
    	{
    		Logger::info('hero[%d] has transfer[%d], remove', $hid, $transfer);
    		$heroObj->unsetTransfer();
    		$heroObj->unsetDXTrans();
    	}
    	
    	// 是否是红卡
    	if ($heroObj->getStarLv() != 7)
    	{
    		throw new FakeException('hero[%d] starlv[%d] is not red card', $hid, $heroObj->getStarLv());
    	}
    	
    	// 是否在阵上，小伙伴，助战军
    	if (EnFormation::isHidInAll($hid, $userObj->getUid()))
    	{
    		throw new FakeException("hero[%d] is in formation or in extra or in attr extra", $hid);
    	}
    	
    	// 返还进阶材料
    	$rebornGot = array();
    	$rebornSilver = 0;
    	$redHeroEvLv = $heroObj->getEvolveLv();
    	for ($i = 0; $i < $redHeroEvLv; $i++)
    	{
	    	$evlTblId = HeroLogic::getEvolveTbl($heroObj->getHtid(), $i);
	    	$evlTblConf = btstore_get()->HERO_CONVERT[$evlTblId];
	    	$rebornSilver += intval(btstore_get()->HERO_CONVERT[$evlTblId]['needSilver']);
	    	foreach ($evlTblConf['arrNeedItem'] as $itemTmplId => $itemNum)
	    	{
	    		if (!isset($rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTmplId]))
	    		{
	    			$rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTmplId] = 0;
	    	 	}
	    		$rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTmplId] += $itemNum;
	    	}
	    	foreach ($evlTblConf['arrNeedHero'] as $index => $heroConf)
	    	{
	    		$htid = $heroConf[0];
	    		$heroLevel = $heroConf[1];
	    		$heroNum = $heroConf[2];
	    		$rebornGot[ResolverDef::RESOLVER_GOT_TYPE_HERO][] = array('htid'=>$htid, 'level'=>$heroLevel, 'num'=>$heroNum);
	    	}
	    }
	    
	    // 返还进化材料
	    $unDevelopId = Creature::getHeroConf($heroObj->getHtid(), CreatureAttr::UNDEVELOP_TBL_ID);
	    $developTbl = btstore_get()->HERO_DEVELOP[$unDevelopId];
	    foreach ($developTbl['arrNeedItem'] as $itemTmplId => $itemNum)
	    {
	    	if (!isset($rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTmplId]))
	    	{
	    		$rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTmplId] = 0;
	    	}
	    	$rebornGot[ResolverDef::RESOLVER_GOT_TYPE_ITEM][$itemTmplId] += $itemNum;
    	}
    	foreach ($developTbl['arrNeedHero'] as $index => $heroConf)
        {
    		$htid = $heroConf[0];
    		$heroLevel = $heroConf[1];
    		$heroNum = $heroConf[2];
    		$rebornGot[ResolverDef::RESOLVER_GOT_TYPE_HERO][] = array('htid'=>$htid, 'level'=>$heroLevel, 'num'=>$heroNum);
    	}
    	$rebornSilver += $developTbl['needSilver'];
    	$rebornGot['silver'] = $rebornSilver;
    	
    	// 武将天赋处理
    	$talentInfo = $heroObj->getCurTalent();
    	foreach ($talentInfo as $talentIndex => $talentId)
    	{
    		if (!HeroLogic::isTalentHcopyPassed($userObj->getUid(), $hid, $talentIndex))
    		{
    			$heroObj->addSealedTalent($talentIndex);
    		}
    	}
    	
    	// 消耗的金币
    	$needGold = Creature::getHeroConf($heroObj->getHtid(), CreatureAttr::UNDEVELOP_NEED_EXTRA_GOLD) + Creature::getHeroConf($heroObj->getHtid(), CreatureAttr::REBORN_GOLD_BASE) * ($heroObj->getEvolveLv() + 1);
    	if (!$userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_MYSTERYSHOP_REBORN_RED_HERO))
    	{
    		throw new FakeException('sub gold faild, need[%d], curr[%d]', $needGold, $userObj->getGold());
    	}
    	
    	//返还天命培养材料
    	$arrCost = HeroLogic::getCostOfDestiny($userObj->getUid(), $hid);
    	$arrCost = Resolve::trans3D2Arr($arrCost);
    	$rebornGot = self::array_add($rebornGot, $arrCost);
    	$heroObj->setDestiny(0);
    	
    	// 武将变成橙将   进阶等级变成+5
    	$toHtid = $developTbl['fromHtid'];
    	$needEvLv = $developTbl['needEvolveLv'];
    	$preHtid = $heroObj->getHtid();
    	$heroObj->unDevelop($toHtid, $needEvLv);
    	self::getResolveGot($rebornGot);
    	
    	// update
    	if( !$preview) 
    	{
    		$userObj->update();
    		BagManager::getInstance()->getBag()->update();
    	}
		Logger::info('mysteryshop.rebornRedHero end.hero[%d] htid[%d] evolvelv[%d] tohtid[%d]', $hid, $preHtid, $redHeroEvLv, $toHtid);
		
		$ret = array('reborn_get'=>$rebornGot, 'hero_info'=>$heroObj->getInfo());
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
    }
    
	public function resolveTally($hid)
    {
    	return $this->doResolveTally($hid, false);
    }
    
    public function previewResolveTally($hid)
    {
    	return $this->doResolveTally($hid, true);
    }
    
    private function doResolveTally($arrItemId, $preview)
    {
    	Logger::trace('mysteryshop.resolveTally start.param arrItemId:%s.',$arrItemId);
    	$ret = Resolve::tallyResolve($arrItemId, $preview);
    	return $ret;
    }
    
	public function rebornTally($hid)
    {
    	return $this->doRebornTally($hid, false);
    }
    
    public function previewRebornTally($hid)
    {
    	return $this->doRebornTally($hid, true);
    }
    
    public function doRebornTally($arrItemId, $preview)
    {
    	Logger::trace('mysteryshop.rebornTally start.param arrItemId:%s.',$arrItemId);
    	$ret = Resolve::tallyReborn($arrItemId, $preview);
    	return $ret;
    }
 }
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */