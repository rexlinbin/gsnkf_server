<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RecoverTalent.php 251144 2016-07-12 05:45:48Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/fix/RecoverTalent.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-07-12 05:45:48 +0000 (Tue, 12 Jul 2016) $
 * @version $Revision: 251144 $
 * @brief 
 *  
 **/
/**
 * for mail 【三国申请盗号处理】【手游-APP】-140905- ✿Queen。PID：6805795 【V10】以此为准 
 * 玩家信息是40020080 35021
 * @author dell
 *
 */
class RecoverTalent extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $uid = intval($arrOption[0]);
        $arrTalent = array(
                74480523=>array(
//                         1=>1308,
                        2=>1310
                        ),
                );
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        foreach($arrTalent as $hid => $arrTalentIndex)
        {
            foreach($arrTalentIndex as $talentIndex => $talentId)
            {
                $this->resolveOneTalent($uid, $hid, $talentIndex, $talentId, $arrOption);
            }
        }
    }
    
    public function resolveOneTalent($uid,$hid,$talentIndex,$talentId,$arrOption)
    {
        $userObj = EnUser::getUserObj();
        $heroMng = $userObj->getHeroManager();
        $allHeroObj = $heroMng->getAllHero();
        if(!isset($allHeroObj[$hid]))
        {
            echo 'user has no such hero '.$hid."\n";
            return;
        }
        $heroObj = $heroMng->getHeroObj($hid);
        echo 'hero htid is '.$heroObj->getHtid()." evolvelevel is ".$heroObj->getEvolveLv()."\n";
        $talentInfo = $heroObj->getTalentInfo();
        $preTalentId = 0;
        if(isset($talentInfo[HeroDef::VA_SUBFIELD_TALENT_CONFIRMED][$talentIndex]))
        {
            $preTalentId = $talentInfo[HeroDef::VA_SUBFIELD_TALENT_CONFIRMED][$talentIndex];
            echo 'hero has talent '.$preTalentId." on index $talentIndex \n";
        }
        else
        {
            echo 'hero has no talent '." on index $talentIndex \n";
        }
        if(isset($talentInfo[HeroDef::VA_SUBFIELD_TALENT_TO_CONFIRM][$talentIndex])
                && !empty($talentInfo[HeroDef::VA_SUBFIELD_TALENT_TO_CONFIRM][$talentIndex]))
        {
            echo 'hero has talent '.serialize($talentInfo[HeroDef::VA_SUBFIELD_TALENT_TO_CONFIRM][$talentIndex])." to confirm on index $talentIndex \n";
        }
        else
        {
            echo 'hero has no talent to confirm '." on index $talentIndex \n";
        }
        if(isset($arrOption[1]) && ($arrOption[1] == 'recover'))
        {
            $talentInfo[HeroDef::VA_SUBFIELD_TALENT_CONFIRMED][$talentIndex] = $talentId;
            Util::kickOffUser($uid);
            $heroObj->setTalentInfo($talentInfo);
            $heroObj->update();
            Enuser::getUserObj($uid)->modifyBattleData();
            echo "recover done\n";
            Logger::info('recover talent %d on index %d for user %d hero %d pretalent is %d',$talentId,$talentIndex,$uid,$hid,$preTalentId);
        }
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */