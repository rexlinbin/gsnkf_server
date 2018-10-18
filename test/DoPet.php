<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(ShijieHan@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
class DoPet extends BaseScript
{

    public function getPetInfo($uid)
    {
        $userObj = EnUser::getUserObj($uid);
        $uname = $userObj->getUname();

        $petsInfo = PetLogic::getAllPet($uid);
        $keeperInfo = PetLogic::getKeeperInfo($uid);

        $msg = sprintf("uid:%d, uname:%s:\n", $uid, $uname);
        foreach($petsInfo as $petId => $petInfo)
        {
            $petTplId = $petInfo['pet_tmpl'];
            $petLevel = $petInfo['level'];
            $petExp = $petInfo['exp'];
            $skillPoint = $petInfo['skill_point'];
            $swallow = $petInfo['swallow'];

            $ifFight = 0;
            foreach($keeperInfo['va_keeper']['setpet'] as $onePetKeeper)
            {
                if($onePetKeeper['petid'] == $petId && isset($onePetKeeper['status']) && $onePetKeeper['status'] == 1)
                {
                    $ifFight = 1;
                }
            }

            $msg .= sprintf("petId:%d petTplId:%d petLevel:%d petExp:%d skillPoint:%d swallow:%d ifFight:%d \n",
                $petId, $petTplId, $petLevel, $petExp, $skillPoint, $swallow, $ifFight);
        }

        printf("%s\n", $msg);
        Logger::info("%s", $msg);
    }

    public function delPet($uid, $petId, $ifDo)
    {
        $petManager = PetManager::getInstance($uid);
        $petManager->deletePet($petId);
        $keeperInst = KeeperObj::getInstance($uid);
        $vaKeeper = $keeperInst->getVaKeeper();

        $find = false;
        $realPos = -1;
        foreach ( $vaKeeper['setpet'] as $pos => $info )
        {
            if ( $petId == $info['petid'] )
            {
                $find = true;
                $realPos = $pos;
                break;
            }
        }
        if($find)
        {
            $keeperInst->squandDownPet($realPos);
        }

        if($ifDo == 'do')
        {
            $petManager->update();
            $keeperInst->update();
        }

        printf("del ok %s \n", $ifDo);
    }

    public function addPet($uid, $petTplId, $ifDo)
    {
        $petManager = PetManager::getInstance($uid);
        $petManager->addNewPet($petTplId);

        if($ifDo == 'do')
        {
            $petManager->update();
        }
        printf("add ok %s \n", $ifDo);
    }

    /**
     * 实际的执行函数
     */
    protected function executeScript($arrOption)
    {
        if ( count($arrOption) < 1 )
        {
            //只能删除没有出战的宠物，出战的不管
            printf("can only del pet whose ifFight is 0.");
            //btscript game*** uid del petId do/check 删除宠物
            //btscript game*** uid add petTplId do/check 删除宠物
            printf("Usage: param: uid [ del petId do/check | add petTplId do/check ]\n");
            return;
        }

        $uid = intval( $arrOption[0] );

        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);

        $userObj = EnUser::getUserObj($uid);
        $uname = $userObj->getUname();

        if ( count( $arrOption ) < 3 )
        {
            $this->getPetInfo($uid);
            return;
        }

        Util::kickOffUser($uid);

        $op = $arrOption[1];
        $petId = intval($arrOption[2]);
        $ifDo = $arrOption[3];

        if($op == 'del')
        {
            $this->delPet($uid, $petId, $ifDo);
        }

        if($op == 'add')
        {
            $this->addPet($uid, $petId, $ifDo);
        }

        printf("done already \n");
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */