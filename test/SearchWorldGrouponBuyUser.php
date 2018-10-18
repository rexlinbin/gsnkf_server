<?php
/**
 * Created by PhpStorm.
 * User: hanshijie
 * Date: 15/9/25
 * Time: 13:43
 */

class SearchWorldGrouponBuyUser extends BaseScript
{


    protected function executeScript($arrOption)
    {
        /**
        if(count($arrOption) < 3)
        {
            printf("Usage: btscript gamexxx SearchWorldGrouponBuyUser.php beginTime endTime goodId");
            return;
        }
        */

        $beginTime = 1443067200;//$arrOption[0];
        $endTime = 1443092400;//$arrOption[1];
        $goodId = 1;

        $strOutPut = "start\n";
        $allUserInfo = WorldGrouponDao::selectAllInnerUserInfo();
        foreach($allUserInfo as $userInfo) {
            $uid = $userInfo[WorldGrouponSqlDef::TBL_FIELD_UID];
            try
            {
                //$innerUserInfo = WorldGrouponInnerUser::getInstance($uid);
                $his = $userInfo[WorldGrouponSqlDef::TBL_FIELD_VA_INFO][WorldGrouponSqlDef::HIS_IN_VA_INFO];
                if(empty($his))
                {
                    continue;
                }
                foreach($his as $each)
                {
                    $buyTime = $each[WorldGrouponSqlDef::BUY_TIME_IN_VA_INFO];
                    if($buyTime >= $beginTime
                        && $buyTime <= $endTime)
                    {
                        $userObj = EnUser::getUserObj($uid);
                        $serverId = $userObj->getServerId();
                        $pid = $userObj->getPid();
                        if($each[WorldGrouponSqlDef::GOOD_ID_IN_VA_INFO] == $goodId)
                        {
                            $num = $each[WorldGrouponSqlDef::NUM_IN_VA_INFO];
                            $gold = $each[WorldGrouponSqlDef::GOLD_IN_VA_INFO];
                            $strOutPut .= sprintf("serverId:%d uid:%d pid:%d goodId:%d num:%d gold:%d buyTime:%d\n",
                               $serverId, $uid, $pid, $goodId, $num, $gold, $buyTime);
                        }
                    }
                }
            }
            catch (Exception $e)
            {
                Logger::fatal($e);
                printf($e);
            }

        }
        printf("%s", $strOutPut);
        $fileName =  'buyList' . $serverId;

        file_put_contents($fileName, $strOutPut);
    }
}