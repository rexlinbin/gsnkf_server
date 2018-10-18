<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ReplaceHeroAndFrag.script.php 254199 2016-08-02 07:39:42Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/fix/ReplaceHeroAndFrag.script.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-02 07:39:42 +0000 (Tue, 02 Aug 2016) $
 * @version $Revision: 254199 $
 * @brief 
 *  
 **/
class ReplaceHeroAndFrag extends BaseScript
{
    private $preHtid = 10196;
    private $preHeroFrag = 410196;
    private $htid = 10021;
    private $heroFrag = 410021;
    private $replace = FALSE;
    private $uid = 0;
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $this->uid = intval($arrOption[0]);
        if(isset($arrOption[1]) && $arrOption[1] == 'replace')
        {
            $this->replace = TRUE;
        }
        if(empty($this->uid))
        {
            Logger::fatal('empty uid.can not fix.please check');
            return;
        }
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $this->uid);
        if($this->replace)
        {
            Util::kickOffUser($this->uid);
        }
        $this->replaceHeroFrag();
        $this->replaceHero();
        if($this->replace)
        {
            $userObj = EnUser::getUserObj($this->uid);
            $bag = BagManager::getInstance()->getBag($this->uid);
            $fmt = EnFormation::getFormationObj($this->uid);
            $userObj->update();
            $bag->update();
            $fmt->update();
            Logger::info('replace done.');
            echo "update\n";
        }
        echo "all done\n";
    }
    
    private function replaceHeroFrag()
    {
        $bag = BagManager::getInstance()->getBag($this->uid);
        $fragNum = $bag->getItemNumByTemplateID($this->preHeroFrag);
        if(empty($fragNum))
        {
            return;
        }
        if(!$bag->deleteItembyTemplateID($this->preHeroFrag, $fragNum))
        {
            throw new FakeException('delete herofrag failed');
        }
        if(!$bag->addItemByTemplateID($this->heroFrag, $fragNum, true))
        {
            throw new FakeException('add herofrag failed');
        }
        Logger::info('replaceHeroFrag uid %d pre %d now %d num %d',$this->uid
                ,$this->preHeroFrag,$this->heroFrag,$fragNum);
        echo "replaceHeroFrag done.num $fragNum\n";
    }
    
    private function replaceHero()
    {
        $userObj = EnUser::getUserObj($this->uid);
        $heroMngr = $userObj->getHeroManager();
        $bag = BagManager::getInstance()->getBag($this->uid);
        $arrHero = $heroMngr->getAllHero();
        foreach($arrHero as $hid => $heroInfo)
        {
            if($heroInfo['htid'] != $this->preHtid)
            {
                continue;
            }
            //如果武将在阵型、替补、小伙伴上 就卸下
            $fmt = EnFormation::getFormationObj($this->uid);
            $extra = $fmt->getExtra();
            $index = 0;
            foreach($extra as $index => $extraHid)
            {
                if($hid == $extraHid)
                {
                    $fmt->delExtra($hid, $index);
                    Logger::info('del hero %d from extraindex %d for uid %d',$hid,$index,$this->uid);
                }
            }
            $attrExtra = $fmt->getAttrExtra();
            $index = 0;
            foreach($attrExtra as $index => $attrExtraHid)
            {
                if($hid == $attrExtraHid)
                {
                    $fmt->delAttrExtra($hid, $index);
                    Logger::info('del hero %d from attrextraindex %d for user %d',
                            $hid,$index,$this->uid);
                }
            }
            $fmthero = $fmt->getSquad();
            $heroObj = $heroMngr->getHeroObj($hid);
            foreach($fmthero as $index => $fmtHid)
            {
                if($hid == $fmtHid)
                {
                    if($heroObj->isEquiped())
                    {
                        self::unEquipeHero($hid);
                    }
                    $fmt->delHero($hid);
                    Logger::info('del hero in fmt hid %d for uid %d',
                            $hid,$this->uid);
                }
            }
            
            if($heroObj->isLocked())
            {
                $heroObj->unLock();
            }
            
            $level = $heroInfo['level'];
            if($level > 1)
            {
                //武将重生
                $ret = $this->rebornOneHero($hid);
                Logger::info('reborn hero %d for uid %d',$hid,$this->uid);
            }
            if(EnPass::canArrHidBeDel($this->uid, array($hid)) == FALSE)
            {
                $msg = sprintf('hid %d is in pass of uid %d.can not delete',$hid,$this->uid);
                echo $msg."\n";
                Logger::info($msg);
                if($this->replace)
                {
                    $data = new CData();
                    $data->update(PassDao::$tbl)
                         ->set(array('refresh_time'=>0))
                         ->where(array('uid','=',$this->uid))
                         ->query();
                    PassObj::releaseInstance($this->uid);
                    Logger::info('reset passinfo for user %d',$this->uid);
                    if(EnPass::canArrHidBeDel($this->uid, array($hid)))
                    {
                        echo "reset passinfo done\n";
                    }
                    else
                    {
                        echo "fail to reset passinfo\n";
                    }
                }
                else
                {
                    continue;
                }
            }
            //替换武将
            $heroMngr->delHeroByHid($hid);
            //添加武将
            $newHid = $heroMngr->addNewHero($this->htid);
            Logger::info('add new hero %d',$newHid);
            Logger::info('delHeroByHid %d',$hid);
            echo "delhero $hid and add a new hero\n";
        }
    }
    
    public function rebornOneHero($hid)
    {
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
//         //消耗金币
//         $needGold = (1 + $heroObj->getEvolveLv()) * ($heroObj->getConf(CreatureAttr::REBORN_GOLD_BASE));
//         if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_MYSTERYSHOP_REBORN_HERO) == FALSE)
//         {
//             throw new FakeException('reBornHero.sub gold failed.');
//         }
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
                if($itemTmplId == $this->preHeroFrag)
                {
                    $itemTmplId = $this->heroFrag;
                }
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
                if($htid == $this->preHtid)
                {
                    $htid = $this->htid;
                }
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
        
    }
    
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
    
    public static function unEquipeHero($hid,$equipType = HeroDef::EQUIP_ALL,$arrPos = array())
    {
        $user    = EnUser::getUserObj();
        $heroMng = $user->getHeroManager();
        $heroObj = $heroMng->getHeroObj($hid);
        if($heroObj == NULL)
        {
            throw new FakeException('unEquipHero heroobj %s is null.or is not equiped',$hid);
        }
        if($heroObj->isEquiped() == FALSE)
        {
            return 'nochange';
        }
        $arrHeroEquip = array();
        switch($equipType)
        {
            case HeroDef::EQUIP_ALL:
                foreach(HeroDef::$ALL_EQUIP_TYPE as $type)
                {
                    $arrHeroEquip[$type] = $heroObj->getEquipByType($type);
                }
                break;
            default:
                $arrHeroEquip[$equipType] = $heroObj->getEquipByType($equipType);
        }
        $bag = BagManager::getInstance()->getBag();
        foreach($arrHeroEquip as $type => $heroArms)
        {
            $setFunc	= HeroUtil::getSetEquipFunc($type);
            foreach($heroArms as $posId=>$armId)
            {
                if($armId == ItemDef::ITEM_ID_NO_ITEM)
                {
                    continue;
                }
                if(!empty($arrPos)  &&   (in_array($posId, $arrPos) == FALSE))
                {
                    continue;
                }
                call_user_func_array(array($heroObj, $setFunc), array($type, ItemDef::ITEM_ID_NO_ITEM,$posId) );
                $bag->addItem($armId,true);
            }
        }
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */