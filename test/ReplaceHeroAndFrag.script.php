<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ReplaceHeroAndFrag.script.php 253936 2016-08-01 07:14:03Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/ReplaceHeroAndFrag.script.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-01 07:14:03 +0000 (Mon, 01 Aug 2016) $
 * @version $Revision: 253936 $
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
            if(EnFormation::isHidInFormation($hid, $this->uid))
            {
                $fmt->delHero($hid);
                Logger::info('del hero %d from fmt for uid %d',$hid,$this->uid);
            }
            
            $heroObj = $heroMngr->getHeroObj($hid);
            if($heroObj->isLocked())
            {
                $heroObj->unLock();
            }
            
            $level = $heroInfo['level'];
            if($level > 1)
            {
                $needGold = (1 + $heroObj->getEvolveLv()) * ($heroObj->getConf(CreatureAttr::REBORN_GOLD_BASE));
                if($userObj->addGold($needGold, StatisticsDef::ST_FUNCKEY_MYSTERYSHOP_REBORN_HERO) == FALSE)
                {
                    throw new FakeException('reBornHero.add gold failed.');
                }
                //武将重生
                $mysteryshop = new MysteryShop();
                $ret = $mysteryshop->doRebornHero($hid, true);
                Logger::info('reborn hero %d for uid %d',$hid,$this->uid);
            }
            //替换武将
            $heroMngr->delHeroByHid($hid);
            $heroMngr->addNewHeroWithLv($this->htid, 1);
            Logger::info('delHeroByHid %d add new hero',$hid);
            echo "delhero $hid and add a new hero\n";
        }
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */