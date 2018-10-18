<?php
require_once (LIB_ROOT . '/ParserUtil.php');
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BattleTest.php 250333 2016-07-07 03:45:10Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/BattleTest.php $
 * @author $Author: BaoguoMeng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-07-07 03:45:10 +0000 (Thu, 07 Jul 2016) $
 * @version $Revision: 250333 $
 * @brief 
 *  
 **/

/**
 * 男性角色的主角武将htid是20001
 * man.cfg
 * fmt:0,10002,20001,0,0,0;0,1010011,1010012,1010014,0,0
 *
 */
/**
 * 女性角色的主角武将htid是20002
 * woman.cfg1
    fmt:10002,0,20002,0,0,0;1001
    herolevel:10002,20
    heroevolvelv:10002,2
    additem:10002,101101,10
    additem:10002,102101,20
    additem:10002,103212,16
    additem:20002,101101,10
    additem:20002,102101,20
    additem:20002,103212
    addpet:1,10
    addpet:2
    addstar:10001,5
    addstar:10002
 *
 *
 *
 * woman.cfg2
    fmt:10002,0,20002,0,0,0;0,1010011,1010012,1010014,0,0
    herolevel:10002,20
    heroevolvelv:10002,2
    additem:10002,101101,10
    additem:10002,102101,20
    additem:10002,103212,16
    additem:20002,101101,10
    additem:20002,102101,20
    addpet:1,10
    addstar:10001,5
 */
class BattleTest extends BaseScript
{
    private $cfg = array();
    private $myFmt = NULL;
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        //最好使用新用户
        $uid = 20991;
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        $this->openAllFmtPos();//开启阵型上的所有位置
        EnSwitch::checkSwitch();//开启阵型的功能节点
        //清空背包
        $bag = BagManager::getInstance()->getBag($uid);
        $bag->clearBag();
        if(isset($arrOption[0]))
        {
            $file = $arrOption[0];
        }
        else 
        {
            $file = '/home/pirate/battle.cfg';    
        }
        $this->cfg = $this->readCfg($file);
        if(empty($this->cfg))
        {
            return;
        }
        echo "config:\n";
        var_dump($this->cfg);
        $this->myFmt = new MyFormation($uid);
        $userObj = EnUser::getUserObj();
        $fmt = $this->prepareHeroFmt();
        if(empty($fmt))
        {
            return;
        }
        $this->prepareHero($fmt);
        $this->prepareHeroEquip($fmt);
        $this->preparePet();
        $this->prepareStar();
        echo "all Pet:\n";
        $myPet = PetManager::getInstance($uid);
        var_dump($myPet->getAllPet());
        echo "all star:\n";
        $myStar = MyStar::getInstance($uid);
        var_dump($myStar->getAllInfo());
        $playerBt = $userObj->getBattleFormation();
        $mstBt = $this->getMonsterBattleFormation(BaseLevel::SIMPLE);
        echo "player Battle data:\n";
        var_dump($playerBt);
        echo "monster Battle data:\n";
        var_dump($mstBt);
        $atkRet = self::doHero($playerBt, $mstBt);
        echo "attack return:\n";
        var_dump($atkRet);
    }
    
    private static function doHero($arrFormation1,$arrFormation2,$type = 0, $callback = null,
			$arrEndCondition = null, $arrExtra = null, $db = null)
    {
        //如果是跨服战，篡改一下hid
        if(isset($arrExtra['isKFZ']) && $arrExtra['isKFZ'])
        {
            foreach($arrFormation1['arrHero'] as &$hero)
            {
                $hero['hid'] = $hero['hid']*10 + 1;
            }
            unset($hero);
            foreach($arrFormation2['arrHero'] as &$hero)
            {
                $hero['hid'] = $hero['hid']*10 + 2;
            }
            unset($hero);
        }
        $arrKey = array ('bgid', 'musicId', 'type' );
        foreach ( $arrKey as $key )
        {
            if (! isset ( $arrExtra [$key] ))
            {
                $arrExtra [$key] = 0;
            }
        }
        $arrHero1 = $arrFormation1 ['arrHero'];
        $arrHero2 = $arrFormation2 ['arrHero'];
        $arrHero1 = BattleUtil::unsetEmpty ( $arrHero1 );
        $arrHero2 = BattleUtil::unsetEmpty ( $arrHero2 );
        $arrFormation1 ['arrHero'] = $arrHero1;
        $arrFormation2 ['arrHero'] = $arrHero2;
        
        if (empty ( $arrEndCondition ))
        {
            $arrEndCondition = array ('dummy' => true );
        }
        $proxy = new PHPProxy ( 'battle' );
        $arrRet = $proxy->doHero ( BattleUtil::prepareBattleFormation ( $arrFormation1 ),
                BattleUtil::prepareBattleFormation ( $arrFormation2 ), $type, $arrEndCondition, array('dummy' => true) );
        
        
        Logger::debug('The dohero use db is %s.', $db);
        $brid = IdGenerator::nextId ( "brid", $db );
        $arrRet ['server'] ['uid1'] = $arrFormation1 ['uid'];
        $arrRet ['server'] ['uid2'] = $arrFormation2 ['uid'];
        $arrRet ['server'] ['brid'] = $brid;
        
        $arrClient = $arrRet ['client'];
        if (! empty ( $callback ))
        {
        
            $arrReward = call_user_func ( $callback, $arrRet ["server"] );
            $arrClient ['reward'] = $arrReward;
            $arrRet ['server'] ['reward'] = $arrReward;
        }
        
        if (isset ( $arrExtra ['dlgId'] ))
        {
            $arrClient ['dlgId'] = $arrExtra ['dlgId'];
            $arrClient ['dlgRound'] = $arrExtra ['dlgRound'];
        }
        $arrClient ['bgId'] = $arrExtra ['bgid'];
        $arrClient ['type'] = $arrExtra ['type'];
        $arrClient ['musicId'] = $arrExtra ['musicId'];
        $arrClient ['brid'] = $brid;
        $arrClient ['url_brid'] = BabelCrypt::encryptNumber ( $brid );
        if($db != null)
        {
            $arrClient ['brid'] = RecordType::KFZ_PREFIX.$arrClient ['brid'];
            $arrClient ['url_brid'] = RecordType::KFZ_PREFIX.$arrClient ['url_brid'];
        
            Logger::debug('The dohero url brid is %s, brid is %s',
                    $arrClient ['url_brid'], $arrClient ['brid']);
        }
        $arrClient ['team1'] = BattleUtil::prepareClientFormation ( $arrFormation1,
                $arrRet ['server'] ['team1'] );
        $arrClient ['team2'] = BattleUtil::prepareClientFormation ( $arrFormation2,
                $arrRet ['server'] ['team2'] );
        return $arrClient;
    }
    
    /**
     * 玩家升级
     */
    public function openAllFmtPos()
    {
        $levels = btstore_get()->FORMATION['arrOpenNeedLevel'];
        $lv1 =  $levels[count($levels)-1];
        $lv2 = UserConf::MAX_LEVEL;
        $lv = max(array($lv1,$lv2));
        $userObj = EnUser::getUserObj();
        if($userObj->getLevel() >= $lv)
        {
            return;
        }
        $exp = $userObj->getAllExp();
        $expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
        $addExp = $expTable[$lv] - $exp;
        $userObj->addExp($addExp);
    }
    
    private function delAllHeroFromFmt()
    {
        $uid = RPCContext::getInstance()->getUid();
        $hids = EnFormation::getArrHidInFormation($uid);
        foreach($hids as $pos => $hid)
        {
            $this->myFmt->delHero($hid);//所有武将（包括主角）下阵
        }
    }
    private function prepareHeroFmt()
    {
        $uid = RPCContext::getInstance()->getUid();
        $this->myFmt = new MyFormation($uid);
        $userObj = EnUser::getUserObj();
        $fmt = array();
        //一、获取阵型数据
        if(!empty($this->cfg['fmt1']))
        {
            //从阵型上清除所有的武将
            $this->delAllHeroFromFmt();
            $heroMng = $userObj->getHeroManager();
            $fmt1 = $this->cfg['fmt1'];
            //准备需要的武将
            foreach($fmt1 as $pos => $htid)
            {
                if(empty($htid))
                {
                    continue;
                }
                $num = $heroMng->getHeroNumByHtid($htid);
                if($num == 0)
                {
                    if(HeroUtil::isMasterHtid($htid))
                    {
                        $hid = $userObj->getMasterHid();
                        $masterHtid = $heroMng->getHeroObj($hid)->getHtid();
                        if($masterHtid != $htid)
                        {
                            throw new FakeException("this user's master hero is %s.fmt is %s",$masterHtid,$fmt1);
                        }
                    }
                    else
                    {
                        $hid = $heroMng->addNewHero($htid);
                    }
                }
                else 
                {
                    $allHeroObj    =    $heroMng->getAllHeroObj();
                    foreach($allHeroObj as $heroid    =>    $heroObj)
                    {
                        if($heroObj->getHtid() == $htid)
                        {
                            $hid = $heroid;
                            break;
                        }
                    }
                }
                $fmt[$htid] = array(
                        'pos'=>$pos,
                        'htid'=>$htid,
                        'hid'=>$hid
                );
            }
            //将武将加入到阵容中
            $index = 0;
            $formation = array();
            foreach($fmt as $htid => $heroInfo)
            {
                $this->myFmt->addHero($heroInfo['hid'], $index);
                $index++;
                $formation[$heroInfo['pos']] = $heroInfo['hid'];
            }
            $this->myFmt->update();
            //设置阵型
            EnFormation::setFormation($uid, $formation);
        }
        else 
        {
            $arrHid = $this->myFmt->getFormation();
            foreach($arrHid as $pos=>$hid)
            {
                $heroObj = $heroMng->getHeroObj($hid);
                $fmt[$heroObj->getHtid()] = array(
                        'pos'=>$pos,
                        'htid'=>$heroObj->getHtid(),
                        'hid'=>$hid
                        );
            }
        }
       return $fmt;
    }
    
    private function prepareHero($fmt)
    {
        Logger::trace('prepareHero start');
        $heroMng = EnUser::getUserObj()->getHeroManager();
        //设置武将转生次数
        $heroEvLv = $this->cfg['heroevolvelv'];
        foreach($heroEvLv as $index => $evLvInfo)
        {
            $htid = $evLvInfo[0];
            $level = $evLvInfo[1];
            if(!isset($fmt[$htid]))
            {
                continue;
            }
            $hid = $fmt[$htid]['hid'];
            $heroObj = $heroMng->getHeroObj($hid);
            if($heroObj->isMasterHero())
            {
                continue;
            }
            $heroEvLv = $heroObj->getEvolveLv();
            if($heroEvLv < $level)
            {
                for($i=0;$i<($level-$heroEvLv);$i++)
                {
                    $heroObj->convertUp();
                }
            }
            Logger::trace('levelup hero %s level %s evolve %s.',$hid,$heroObj->getLevel(),$heroObj->getEvolveLv());
        }
        
        //设置武将等级
        $heroLv = $this->cfg['herolevel'];
        Logger::trace('prepareHero fmt %s.',$fmt);
        Logger::trace('prepareHero herolv %s.',$heroLv);
        foreach($heroLv as $index => $lvInfo)
        {
            $htid = $lvInfo[0];
            $level = $lvInfo[1];
            if(!isset($fmt[$htid]))
            {
                continue;
            }
            $hid = $fmt[$htid]['hid'];
            $heroObj = $heroMng->getHeroObj($hid);
            if($heroObj->isMasterHero())
            {
                $heroObj->setLevel($level);
                continue;
            }
            $heroObj->levelUp($level - $heroObj->getLevel());
            Logger::trace('levelup hero %s level %s.',$hid,$heroObj->getLevel());
        }
        
    }
    
    private function prepareHeroEquip($fmt)
    {
        //设置武将装备
        $addItem = $this->cfg['additem'];
        $heroMng = EnUser::getUserObj()->getHeroManager();
        $bag = BagManager::getInstance()->getBag();
        foreach($addItem as $index => $itemInfo)
        {
            $htid = $itemInfo['htid'];
            $heroObj = $heroMng->getHeroObj($fmt[$htid]['hid']);
            $itemTmplId = $itemInfo['itemid'];
            $itemLv = 1;
            if(isset($itemInfo['itemlevel']))
            {
                $itemLv = $itemInfo['itemlevel'];
            }
            $itemIds = ItemManager::getInstance()->addItem($itemTmplId);
            $bag->addItem($itemIds[0]);
            $itemObj = ItemManager::getInstance()->getItem($itemIds[0]);
            $itemType = $itemObj->getType();
            $enforceLv = $itemObj->getLevel();
            if($enforceLv < $itemLv)
            {
                $itemObj->reinforce($itemLv - $enforceLv);
            }
            Logger::trace('equipHero %s item %s.',$heroObj->getHid(),$itemIds[0]);
            $heroObj->setArmingByPos($itemIds[0], $itemType);
            Logger::trace('hero %s arm %s info %s.',$heroObj->getHid(),$heroObj->getArming(),$heroObj->getInfo());
        }
        foreach($fmt as $htid => $heroInfo)
        {
            $hid = $heroInfo['hid'];
            $heroObj = $heroMng->getHeroObj($hid);
            Logger::trace('after addItem hero %s info %s.',$hid,$heroObj->getInfo());
        }
    }
    
    private function preparePet()
    {
        if(empty($this->cfg['addpet']))
        {
            return;
        }
        $uid = RPCContext::getInstance()->getUid();
        $petMng = PetManager::getInstance($uid);
        foreach($this->cfg['addpet'] as $index => $petInfo)
        {
            $petTmplId = $petInfo[0];
            $level = 1;
            if(isset($petInfo[1]))
            {
                $level = intval($petInfo[1]);
            }
            
            $petConf =  btstore_get()->PET[$petTmplId];
            $expTblId = $petConf[ 'expTbl'  ];
            $expTbl = btstore_get()->EXP_TBL[ $expTblId ];
            if($level > $petConf[ 'maxLevel' ])
            {
                $level = $petConf['maxLevel'];
            }
            $exp = $expTbl[$level];
            $newPet = $petMng->addNewPet($petTmplId);
            $petId = $newPet['petid'];
            $petMng->setExpAndLv($petId, $exp, $level);
        }
    }
    
    private function prepareStar()
    {
        if(empty($this->cfg['addstar']))
        {
            return;
        }
        $uid = RPCContext::getInstance()->getUid();
        $myStar = MyStar::getInstance($uid);
        foreach($this->cfg['addstar'] as $index => $starInfo)
        {
            $starTmplId = $starInfo[0];
//             $level = 1;
//             if(isset($starInfo[1]))
//             {
//                 $level = $starInfo[1];
//             }
            EnStar::addNewStar($uid, $starTmplId);
         }
         $sidToTid = $myStar->getAllStarTid();
         Logger::trace('all star sid is %s.',$sidToTid);
         $tidToSid = array_flip($sidToTid);
         if(count($sidToTid) != count($tidToSid))
         {
             throw new FakeException('sidtotid %s  not equal to tidtosid %s.',$sidToTid,$tidToSid);
         }
         foreach($this->cfg['addstar'] as $index => $starInfo)
         {
             $starTmplId = $starInfo[0];
             $level = 1;
             if(isset($starInfo[1]))
             {
                 $level = intval($starInfo[1]);
             }
             $sid = $tidToSid[$starTmplId];
             $myStar->setStarLevel($sid, $level);
         }
         Logger::trace('all starinfo is %s.',$myStar->getAllInfo());
    }
    
    private function readCfg($filePath)
    {
        $cfg = array(
                'fmt1'=>array(),
                'fmt2'=>array(),
                'herolevel'=>array(),
                'heroevolvelv'=>array(),
                'additem'=>array(),
                'addpet'=>array(),
                'addstar'=>array()
                );
        $file = fopen($filePath, 'r');
        while(TRUE)
        {
            $line = fgets($file);
            if(empty($line))
            {
                break;
            }
            $tmp = explode(':', $line);
            if(count($tmp) != 2)
            {
                throw new FakeException('error conf %s.',$line);
            }
            $option = $tmp[0];
            $args = $tmp[1];
            $args = trim($args);
            $option = strtolower($option);
            switch($option)
            {
                case 'fmt':
                    $fmt = explode(';', $args);
                    if(count($fmt) == 1)
                    {
                        $cfg['fmt2'] = str2Array($fmt[0], ',');
                    }
                    else if(count($fmt) == 2)
                    {
                        $cfg['fmt1'] = str2Array($fmt[0], ',');
                        $cfg['fmt2'] = str2Array($fmt[1], ',');
                    }
                    else 
                    {
                        throw new FakeException('error conf fmt %s.',$args);
                    }
                    break;
                case 'additem':
                    $params = explode(',', $args);
                    if(count($params) < 2)
                    {
                        throw new FakeException('error conf additem %s.',$args);
                    }
                    $params = array_map('intval', $params);
                    $itemLv = 1;
                    if(isset($params[2]))
                    {
                         $itemLv = $params[2];
                    }
                    $cfg['additem'][] = array('htid'=>$params[0],'itemid'=>$params[1],'itemlevel'=>$itemLv);
                    break;
                case 'herolevel':
                    $cfg['herolevel'][] = explode(',', $args);
                    break;
                case 'heroevolvelv':
                    $cfg['heroevolvelv'][] = explode(',', $args);
                    break;
                case 'addpet':
                    $cfg['addpet'][] = explode(',', $args);
                    break;
                case 'addstar':
                    $cfg['addstar'][] = explode(',', $args);
                    break;
            }
        }
        fclose($file);
        return $cfg;
    }
    
    private function getMonsterBattleFormation($baseLv)
    {
        $fmt = $this->cfg['fmt2'];
        if(count($fmt) == 1)
        {
            $battleInfo = EnFormation::getMonsterBattleFormation(intval($fmt[0]),$baseLv);
            return $battleInfo;
        }
        for($i = 0; $i < FormationDef::FORMATION_SIZD; ++ $i)
        {
            $fmt[$i] = intval($fmt[$i]);
            if (!empty($fmt[$i]))
            {
                $arrCreature [$i] = new Creature ( $fmt [$i]);
                $arrCreature[$i]->setAddAttrByBaseLv($baseLv);
            }
        }
        $unionProfit    =    EnFormation::getUnionProfitByFmt($arrCreature);
        foreach($arrCreature as $pos => $creatureObj)
        {
            $arrCreature[$pos]->setAddAttr(HeroDef::ADD_ATTR_BY_UNIONPROFIT, $unionProfit[$creatureObj->getHid()] );
        }
        $creaturesInfo = EnFormation::changeObjToInfo($arrCreature);
        $battleInfo = array('name' => '',
                'level' => 1,
                'isPlayer' => false,
                'flag' => 0,
                'uid' => 1,
                'arrHero' => $creaturesInfo);
        return $battleInfo;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */