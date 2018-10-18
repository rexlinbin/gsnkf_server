<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: fixPetSkillNormalErr.php 246942 2016-06-17 11:03:53Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/fixPetSkillNormalErr.php $
 * @author $Author: BaoguoMeng $(liushuo@babeltime.com)
 * @date $Date: 2016-06-17 11:03:53 +0000 (Fri, 17 Jun 2016) $
 * @version $Revision: 246942 $
 * @brief 
 *  
 **/
class fixPetSkillNormalErr extends BaseScript
{
    protected function executeScript($arrOption)
    {
        $petid = intval($arrOption[0]);
        $neesUpdate = $arrOption[1];
        $group = self::$group;
        
        //拉出这个人的信息第一次被处理时的信息
        $filepath = './firstinfo/'.$group.'_'.$petid;
        $fileinfo = file_get_contents($filepath);
        $fixVaNormalSkill = unserialize($fileinfo);
        
        $petVaInfo = self::getOnePetVaInfo($petid);
        if (empty($petVaInfo) || empty($fixVaNormalSkill))
        {
        	//TODO log
        	Logger::warning("something wrong!!no data in database or no data in file");
        	printf("something wrong!!no data in database or no data in file") ;
            return;
        }
        $vaNormalSkill = $petVaInfo[PetDef::VAPET]['skillNormal'];
        $uid = $petVaInfo['uid'];
        
        foreach ($fixVaNormalSkill as $index => $vaskill)
        {
            $skillId = $vaskill['id'];
            
            foreach ($vaNormalSkill as $index2 => $vaskill2)
            {
                if ($vaskill2['id'] == $skillId && $vaNormalSkill[$index2]['level'] < $vaskill['level'])
                {
                    $petVaInfo[PetDef::VAPET]['skillNormal'][$index2]['level'] = $vaskill['level'];
                    break;
                }
            }
        }
        
        $petVaInfo[PetDef::VAPET]['updateTime'] = Util::getTime();
        
        if($neesUpdate == 'need')
        {
            self::kickOffUserIfNeed($uid);
            self::saveOnePetVaInfo($uid, $petid, $petVaInfo[PetDef::VAPET]);
            Logger::info("fix pet[%d] ok!data in database is [%s], after fix is [%s]",$petid,$vaNormalSkill,$petVaInfo[PetDef::VAPET]['skillNormal']);
        }
        else if($neesUpdate == 'no')
        {
            printf("fix pet[%d], data in database is [%s], after fix is [%s]",$petid,$vaNormalSkill,$petVaInfo[PetDef::VAPET]['skillNormal']);
        }
        
        
    }
    
    public function getOnePetVaInfo($petid)
    {
        $fields = array (
                'uid',
                PetDef::VAPET,
        );
        
        $data = new CData();
        $arrRet = $data->select( $fields )
        ->from('t_pet')
        ->where(array(PetDef::PET_ID, "=", $petid))
        ->query();
        
        if ( !empty( $arrRet ) )
        {
            return $arrRet[0];
        }
        
        return array();
    }
    
    public function saveOnePetVaInfo($uid, $petid, $va)
    {
        $fields = array (
                PetDef::VAPET => $va,
        );
    
        $data = new CData();
        $arrRet = $data->update('t_pet')
        ->set($fields)
        ->where(array(PetDef::PET_ID, "=", $petid))
        ->where(array('uid', "=", $uid))
        ->query();
    }
    
    public function kickOffUserIfNeed($uid)
    {
        $proxy = new ServerProxy();
    
        $proxy->sendMessage(array($uid), PushInterfaceDef::BACKEND_CLOSE, array());
    
        usleep(100);
    
        $ret = $proxy->checkUser($uid, true);
        	
        if( $ret )
        {
            sleep(1);
        }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */