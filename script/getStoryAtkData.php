<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: getStoryAtkData.php 69238 2013-10-17 06:07:53Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/getStoryAtkData.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-10-17 06:07:53 +0000 (Thu, 17 Oct 2013) $
 * @version $Revision: 69238 $
 * @brief 
 *  
 **/
class getStoryAtkData extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        
        $uid = $this->getReplay(1);
        $proxy = new ServerProxy();
        $proxy->closeUser($uid);
        sleep(1);
        RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_UID);
        RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
        $this->getReplay(2);
    }
    //1:女 2:男
    private function getReplay($gender)
    {
        $pid = time();
        $str = strval($pid);
        $uname = substr($str, strlen($str) - UserConf::MAX_USER_NAME_LEN);
        $ret = UserLogic::createUser($pid, $gender, $uname);
        $uid = $ret['uid'];
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        Logger::trace('setsession uid %s.',$uid);
        $arrArmy = array(1,2,3);
        foreach($arrArmy as $index => $armyId)
        {
            echo 'gender:'.$gender.' armyID:'.$armyId."\n";
            $ret = $this->doBattle($armyId);
            echo $ret."\n";
        }
        return $uid;
    }
    
    private function getarrHeroPos($armyId)
    {
        $teamId = intval(btstore_get()->ARMY[$armyId]['npc_team_id']);
        // 如果没有找到这个NPC部队信息，则出错返回
        $fmt = btstore_get()->TEAM[$teamId]['fmt']->toArray();
        $userObj = EnUser::getUserObj();
        foreach($fmt as $pos => $hid)
        {
            if($hid == 1)
            {
                $fmt[$pos] = $userObj->getMasterHid();
                break;
            }
        }
        return $fmt;
    }

    private function doBattle($armyId)
    {
        $arrHeroPos = $this->getarrHeroPos($armyId);
        $uid = RPCContext::getInstance()->getUid();
        $playerArr	= EnUser::getUserObj()->getBattleFormation();
        $npcFmt = self::getNpcFormationInfo($armyId, $arrHeroPos, $uid);
        $playerArr['arrHero'] = $npcFmt;
        $mstFmt = self::getMonsterBattleFormation($armyId);
        $btType 	= btstore_get()->ARMY[$armyId]['fight_type'];
        $callback 	= array();
        $winCon 	= array();
        $extraInfo 	= array();
        Logger::warning('TRACEdefeat army %s playerAttr %s mstAttr %s',$armyId,$playerArr,$mstFmt);
        $winCon 	= CopyUtil::getVictoryConditions($armyId);
        if($armyId == 3)
        {
            $atkRet = EnBattle::doHero($playerArr, $mstFmt, 2, null, $winCon);
        }
        else
        {
            $atkRet = EnBattle::doHero($playerArr, $mstFmt, 0, null, $winCon);
        }
        return $atkRet['client'];
    }
    
    
    public static function getNpcFormationInfo($armyId, $arrHeroPos,$uid,$baseLv=1)
    {
        $teamId = intval(btstore_get()->ARMY[$armyId]['npc_team_id']);
        // 如果没有找到这个NPC部队信息，则出错返回
        $army = btstore_get()->TEAM[$teamId];
        if (!isset($army))
        {
            throw new FakeException( 'get army:%d from TEAM faield', $teamId );
        }
        $userObj = EnUser::getUserObj();
        $heroMng    =    $userObj->getHeroManager();
        $arrCreature = array();
        for ($i = 0; $i < FormationDef::FORMATION_SIZD; ++$i)
        {
            //配置要求为空的位置，你不能放东西； 配置要求你放东西的位置，你一定要放上东西
            if (($army['fmt'][$i] == 0 && $arrHeroPos[$i] != 0) ||
                    ($army['fmt'][$i] == 1 && empty($arrHeroPos[$i]) ) )
            {
                throw new FakeException( 'formation is different between %s and %s.', $army['fmt'], $arrHeroPos);
            }
    
            if (intval($army['fmt'][$i]) == 1)
            {
                $arrCreature[$i] = $heroMng->getHeroObj($arrHeroPos[$i]);
            }
            else if (intval($army['fmt'][$i]) != 0)
            {
                $arrCreature[$i] = new Creature($army['fmt'][$i]);
            }
        }
        return self::changeObjToInfo($arrCreature);
    }
    
    public static function getMonsterBattleFormation($armyId,$baseLv=1, $arrLvs = null)
    {
        $teamId = btstore_get()->ARMY[$armyId]['teamid'];
        $arrCreature = self::getMonsterFormationInfo($teamId, $arrLvs,$baseLv);
        //敌方的攻击属性
        $battleInfo = array('name' => '',
                'level' => btstore_get()->ARMY[$armyId]['level'],
                'isPlayer' => false,
                'flag' => 0,
                'uid' => $armyId,
                'arrHero' => $arrCreature);
        return $battleInfo;
    }
    
    
    public static function getMonsterFormationInfo($teamId, $arrLvs,$baseLv=1)
    {
        // 如果没有找到这个部队信息，则出错返回
        $army = btstore_get ()->TEAM[$teamId];
        if (! isset ( $army ))
        {
            throw new FakeException( 'get army：%d from TEAM faield', $teamId );
        }
        Logger::debug ( 'army:%d is %s.', $teamId, $army->toArray () );
    
        $arrCreature = array ();
        for($i = 0; $i < FormationDef::FORMATION_SIZD; ++ $i)
        {
            if (!empty($army ['fmt'] [$i]))
            {
                $arrCreature [$i] = new Creature ( $army ['fmt'] [$i]);
                if(!empty($arrLvs))
                {
                    $arrCreature[$i]->setLevel( $arrLvs [$i] );
                }
            }
        }
        return self::changeObjToInfo($arrCreature);
    }
    
    
    public static function changeObjToInfo($arr)
    {
        $modifyRageSkill = array(
                3010021,3010041,3010061
                );
        $arrCreature = array ();
        for($i = 0; $i < FormationDef::FORMATION_SIZD; ++ $i)
        {
            if (isset ( $arr [$i] ) && ($arr [$i] instanceof Creature))
            {
                $arrCreature [$i] = $arr [$i]->getBattleInfo ();
                $arrCreature [$i] ['position'] = $i;
                if($arr[$i]->isHero())
                {
                    $arrCreature[$i][PropertyKey::PHYSICAL_ATTACK_RATIO] = 0;
                    continue;
                }
                $arr[$i]->setAttr(PropertyKey::ITG_BASE, 5000);
                $arr[$i]->setAttr(PropertyKey::STG_BASE, 5000);
                $arr[$i]->setAttr(PropertyKey::REIGN_BASE, 5000);
                $arrCreature[$i][PropertyKey::ITG_BASE] = 5000;
                $arrCreature[$i][PropertyKey::STG_BASE] = 5000;
                $arrCreature[$i][PropertyKey::REIGN_BASE] = 5000;
                $sanwei = $arr[$i]->getSanwei();
                foreach($sanwei as $attr => $value)
                {
                    $arrCreature[$i][$attr] = $value;
                }
                $maxHp = $arr[$i]->getMaxHp();
                $arrCreature[$i][PropertyKey::MAX_HP] = $maxHp;
                $id = $arr[$i]->getHid();
                if($arr[$i]->isHero() == FALSE || ($arr[$i]->isMasterHero() == FALSE))
                {
                    $arrCreature[$i][PropertyKey::CURR_RAGE] = 4;
                }
                if(in_array($id, $modifyRageSkill))
                {
                    $arrCreature[$i][PropertyKey::RAGE_SKILL] = 7450;
                }
            }
        }
        return $arrCreature;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */