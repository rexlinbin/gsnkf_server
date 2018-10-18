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
class DelPill extends BaseScript
{

    /**
     * @param $arrOption
     * $uid
     * $array[
     *  $itemTplId => num,...
     * ]
     */
    protected function executeScript($arrOption)
    {
        if(count($arrOption) != 3)
        {
            printf("error param num");
            return;
        }

        $do = false;
        if($arrOption[0] == 'do')
        {
            $do = true;
        }
        else if($arrOption[0] == 'undo')
        {
            printf("undo");
        }
        $uid = intval($arrOption[1]);
        $arrPill = unserialize($arrOption[2]);
        $this->doDelPill($uid, $arrPill, $do);
    }

    public function doDelPill($uid, $arrPill, $do)
    {
        Logger::info("doDelPill begin:: uid:%d, arrPill:%s", $uid, $arrPill);
        if(empty($uid) || empty($arrPill))
        {
            Logger::debug("empty uid:%d or arrPill:%s", $uid, $arrPill);
            return;
        }
        if($do)
        {
            Util::kickOffUser($uid);
        }

        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);

        $userObj = EnUser::getUserObj($uid);
        $bag = BagManager::getInstance()->getBag($uid);
        $heroManager = $userObj->getHeroManager();

        //提前按照进阶等级倒序取出武将信息
        $arrHero = self::getArrHeroeByUid($uid, array('hid'));

        //逐个去扣
        foreach($arrPill as $itemTplId => $toDelNum)
        {
            printf("uid" . $uid . "itemTplId::" . $itemTplId . " toDelNum:" . $toDelNum . " have:: " .
                $bag->getItemNumByTemplateID($itemTplId) . "\n");

            if($bag->getItemNumByTemplateID($itemTplId) >= $toDelNum)
            {
                if($bag->deleteItembyTemplateID($itemTplId, $toDelNum) == false)
                {
                    Logger::info("Scene1 delItem error from bag:: uid:%d itemTplId:%d toDelNum:%d", $uid, $itemTplId, $toDelNum);
                }
                else
                {
                    Logger::info("Scene1 delItem ok from bag:: uid:%d itemTplId:%d toDelNum:%d", $uid, $itemTplId, $toDelNum);
                }
            }
            else
            {
                $itemNum = $bag->getItemNumByTemplateID($itemTplId);
                if($bag->deleteItembyTemplateID($itemTplId, $itemNum) == false)
                {
                    Logger::info("Scene2 delItem error from bag:: uid:%d itemTplId:%d itemNum:%d", $uid, $itemTplId, $itemNum);
                }
                else
                {
                    Logger::info("Scene2 delItem ok from bag:: uid:%d itemTplId:%d itemNum:%d", $uid, $itemTplId, $itemNum);
                }
                $toDelNum -= $itemNum;

                foreach($arrHero as $hid => $heroInfo)
                {
                    $heroObj = $heroManager->getHeroObj($hid);
                    $arrPill = $heroObj->getPillInfo();

                    foreach($arrPill as $index => $pillInfo)
                    {
                        if(!empty($pillInfo[$itemTplId]))
                        {
                            $pillNum = $pillInfo[$itemTplId];
                            if($toDelNum > 0)
                            {
                                $min = min($pillNum, $toDelNum);
                                $toDelNum -= $min;
                                //从武将身上删除丹药，如果丹药数量减完了，就unset掉
                                $arrPill[$index][$itemTplId] -= $min;
                                if(empty($arrPill[$index][$itemTplId]))
                                {
                                    unset($arrPill[$index][$itemTplId]);
                                }
                                Logger::info("Scene2 delItem on hero:: uid:%d hid:%d itemTplId:%d num:%d", $uid, $hid, $itemTplId, $min);
                                $heroObj->setPillInfo($arrPill);
                            }
                        }
                    }
                }

                if($toDelNum > 0)
                {
                    Logger::info(" uid:%d itemTplId:%d still have %d short", $uid, $itemTplId, $toDelNum);
                }
            }
        }
        if($do)
        {
            $bag->update();
            $userObj->update();
        }

        Logger::info("doDelPill end:: uid:%d, arrPill:%s", $uid, $arrPill);
    }

    public static function getArrHeroeByUid ($uid, $arrField)
    {
        $data = new CData();
        $arrRet = $data->select($arrField)->from("t_hero")
            ->where(array("uid", "=", $uid))
            ->where(array('delete_time', '=', 0))
            ->orderBy("evolve_level", true)
            ->query();
        if (!empty($arrRet))
        {
            $arrRet = Util::arrayIndex($arrRet, 'hid');
        }
        return $arrRet;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */