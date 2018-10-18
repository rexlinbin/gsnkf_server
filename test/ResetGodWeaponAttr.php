<?php
/**
 * Created by PhpStorm.
 * User: hanshijie
 * Date: 15/9/24
 * Time: 16:05
 */

class ResetGodWeaponAttr extends BaseScript
{

    public function getItemInfo($uid)
    {

        $userObj = EnUser::getUserObj($uid);
        $uname = $userObj->getUname();

        $bag = BagManager::getInstance()->getBag($uid);

        $itemType = ItemDef::ITEM_TYPE_GODWEAPON;

        $arrItemIdInBag = $bag->getItemIdsByItemType($itemType);

        $arrItemIdInFormation = array();

        $arrHid = EnFormation::getArrHidInFormation($uid);
        foreach ($arrHid as $hid)
        {
            $heroObj = $userObj->getHeroManager()->getHeroObj($hid);
            $arrItemIdInFormation = array_merge($arrItemIdInFormation, $heroObj->getAllEquipId() );
        }

        $arrItemByType = array();
        $allItemId = array_merge($arrItemIdInFormation, $arrItemIdInBag);
        ItemManager::getInstance()->getItems( $allItemId  );

        $msg = sprintf("uid:%d, uname:%s:\n", $uid, $uname);
        foreach( $allItemId as $itemId )
        {
            if($itemId == 0)
            {
                continue;
            }
            $itemObj = ItemManager::getInstance()->getItem($itemId);
            if( empty($itemObj) )
            {
                Logger::fatal('cant find itemId:%d', $itemId);
                return;
            }
            if( $itemObj->getItemType() == $itemType )
            {
                $arrItemByType[] = $itemId;

                if( in_array( $itemId, $arrItemIdInBag ) )
                {
                    $msg .= sprintf("\t[in  bag]");
                }
                else if( in_array( $itemId, $arrItemIdInFormation ) )
                {
                    $msg .= sprintf("\t[in hero]");
                }
                else
                {
                    Logger::fatal('cant be true');
                    return;
                }
                $msg .= sprintf("itemId:%d, tplId:%d",
                    $itemId, $itemObj->getItemTemplateID() );

                $affix = "";
                foreach($itemObj->getConfirmedAttr() as $index => $attr)
                {
                    $affix .= "$index => $attr # ";
                }
                $msg .= sprintf(" totalExp:%d, reinForceLevel:%d, evolveNum:%d, num:%d, confirmAffix:%s. \n",
                    $itemObj->getTotalExp(), $itemObj->getReinForcelevel(), $itemObj->getEvolveNum(), $itemObj->getItemNum(), $affix);
            }
        }
        printf("%s\n", $msg);
        Logger::info("%s", $msg);


    }

    /**
     * 实际的执行函数
     */
    protected function executeScript($arrOption)
    {

        if ( count($arrOption) < 1 )
        {
            printf("param: uid [itemId要设置属性的神兵id index索引 affix属性 ]\n");
            return;
        }

        $uid = intval( $arrOption[0] );

        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);

        $userObj = EnUser::getUserObj($uid);
        $uname = $userObj->getUname();

        $bag = BagManager::getInstance()->getBag($uid);

        if ( count( $arrOption ) < 3 )
        {
            $this->getItemInfo($uid);
            return;
        }
        Util::kickOffUser($uid);

        $itemId = intval($arrOption[1]);
        $index = intval($arrOption[2]);
        $affix = intval($arrOption[3]);

        $item = ItemManager::getInstance()->getItem($itemId);
        if(empty($item))
        {
            printf("item not exist");
            return;
        }

        $oldAffix = $item->getConfirmedAttr();
        printf("oldAffix:");
        var_dump($oldAffix);

        printf("input y if ensure \n");
        $ret = trim(fgets(STDIN));
        if($ret != 'y')
        {
            printf("ignore\n");
            return;
        }

        $item->setAttr($index, $affix);
        $newAffix = $item->getConfirmedAttr();
        printf("newAffix:");
        var_dump($newAffix);

        $bag->update();


        printf("ok");
    }
}