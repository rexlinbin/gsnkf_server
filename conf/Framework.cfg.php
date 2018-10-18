<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Framework.cfg.php 259101 2016-08-29 09:02:57Z GuohaoZheng $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Framework.cfg.php $
 * @author $Author: GuohaoZheng $(hoping@babeltime.com)
 * @date $Date: 2016-08-29 09:02:57 +0000 (Mon, 29 Aug 2016) $
 * @version $Revision: 259101 $
 * @brief
 *
 **/

class FrameworkConfig
{

	const MAX_RECURS_LEVEL = 10;

	/**
	 * PHPProxy的最大重试次数
	 * @var int
	 */
	const MAX_RETRY_NUM = 3;

	/**
	 * 是否对响应进行编码
	 * @var bool
	 */
	const ENCODE_RESPONSE = true;

	/**
	 * 最小用户uid
	 * @var int
	 */
	const MIN_UID = 20000;

	/**
	 * 最大正整数
	 * @var uint
	 */
	const MAX_UINT = 0xffffffff;

	/**
	 * 最大整数
	 * @var int
	 */
	const MAX_INT = 0x7fffffff;

	/**
	 * 同一时间所允许的误差
	 * @var int
	 */
	const SAME_TIME_OFFSET = 2;

	/**
	 * 用于生成摘要的扰码
	 * @var string
	 */
	const MESS_CODE = 'BabelTime';

	/**
	 * 系统所使用的编码方式
	 * @var string
	 */
	const ENCODING = "utf8";

	/**
	 * 异步方法
	 * @var int
	 */
	const ASYNC_CMD_TPL = '/home/pirate/bin/php /home/pirate/rpcfw/lib/ScriptRunner.php -f /home/pirate/rpcfw/lib/AsyncExecutor.php %s >>/home/pirate/rpcfw/log/popen.log.wf 2>&1 &';

	/**
	 * 本地用于连接phpproxy的地址
	 * @var unknown_type
	 */
	const PHPPROXY_PATH = '/home/pirate/phpproxy/var/phpproxy.sock';

	/**
	 * PPCProxy/PHPProxy的读超时时间
	 * @var int
	 */
	const PROXY_READ_TIMEOUT = 5;

	/**
	 * RPCPRoxy/PHPProxy的写超时时间
	 * @var int
	 */
	const PROXY_WIRTE_TIMEOUT = 1;

	/**
	 * RPCProxy/PHPProxy/HTTPClient的连接超时时间
	 * @var int
	 */
	const PROXY_CONNECT_TIMEOUT = 1;

	/**
	 * HTTPClient业务执行超时时间
	 * @var int
	 */
	const PROXY_EXECUTE_TIMEOUT = 3;

	/**
	 * 压缩上限
	 * @var int
	 */
	const PROXY_COMPRESS_THRESHOLD = 102400;

	/**
	 * 编码参数
	 * @var int
	 */
	const AMF_ENCODE_FLAGS = AMF_ENCODE_FLAGS;

	/**
	 * 解码参数
	 * @var int
	 */
	const AMF_DECODE_FLAGS = AMF_DECODE_FLAGS;

	/**
	 * 最大执行时间
	 * @var int
	 */
	const MAX_EXECUTE_TIME = 2550;

	/**
	 * 最大响应大小
	 * @var int
	 */
	const MAX_RESPONSE_SIZE = 1024000;

	/**
	 * 最大请求大小
	 * @var int
	 */
	const MAX_REQUEST_SIZE = 1024000;

	/**
	 * 一天开始的偏移秒数。 以前是14400 4个小时
	 * @var int
	 */
	const DAY_OFFSET_SECOND = 0;

	/**
	 * 每周开始的偏移秒数, 类似DAY_OFFSET_SECOND，
	 * @var int
	 */
	const WEEK_SECOND = 0;

	/**
	 * 每月开始的偏移秒数
	 * @var int
	 */
	const MONTH_SECOND = 0;

	/**
	 * 日志文件名
	 * @var int
	 */
	const LOG_NAME = 'rpc.log';

	/**
	 * 日志级别
	 * @var int
	 */
	const LOG_LEVEL = Logger::L_INFO;

	/**
	 * 是否开启debug,为调试方便会将某些检查禁掉，线上一定要为false
	 * @var int
	 */
	const DEBUG = false;

	/**
	 * 是否开启覆盖率检查，线上一定为false
	 * @var int
	 */
	const COVERAGE = false;

	/**
	 * 是否将异步执行任务转移到main机器上运行
	 */
	const ASYNC_TASK_ON_MAIN = true;

	/**
	 * 覆盖率报告生成地址
	 * @var string
	 */
	const COVERAGE_ROOT = '/home/pirate/static/coverage';

	/**
	 * 请求前的hook
	 * @var array
	 */
	static $ARR_BEFORE_HOOK = array ('FilterRequestDropHeroOrItem' , 'CheckNegative', 'RestrictUserReq');

	/**
	 * 请求后的hook
	 * @var array
	 *
	 * @import 请确保将SessionEncode放置在array的最后.
	 */
	static $ARR_AFTER_HOOK = array ( 'UpdateFightForce', 'UpdateNewServerActivity', 'UpdateAchieve', 'DoneTaskOnTrustDevice' );

	/**
	 * 可以排除的不需要用户登陆的命令
	 * @var array
	 */
	static $ARR_EXCLUDE_LOGIN_METHOD = array (
			'user' => array ('userLogin' => true, 'getUsers' => true, 'createUser' => true,
					'cancelDel' => true, 'getRandomName' => true ));

	/**
	 * 可以排除的不需要连接的命令
	 * @var array
	 */
	static $ARR_EXCLUDE_CONNECT_METHOD = array (
			'user' => array ('login' => true ),
			'battle' => array ('test' => true, 'getRecord' => true, 'getRecordForWeb' => true, 'getMultiRecord' => true),
			'gm' => array ('reportClientError' => true, 'getTime' => true, 'reportScriptResult' => true ),
			'countrywarcross' =>array('loginCross' => true, ),
			);

	/**
	 * 允许不登录的私有方法
	 * @var unknown_type
	 */
	static $ARR_PRIVATE_METHOD = array ('timer' => array ('execute' => true ),

			'user' => array (
					'modifyUserByOther' => true, 'addGold4BBpay' => true,
					'addItemsOtherUser' => true,'buyItem'=>true,
					'getArrUserByPid' => true, 'getOrder' => true, 'getArrOrder' => true,
					'getByUname' => true, 'getMultiInfoByPid' => true,
					'getTopEn' => true, 'getByPid' => true, 'ban' => true, 'getBanInfo' => true,
			        'getItemOrder'=>true,'getArrItemOrder'=>true,'canBuyItem'=>true, 'addBadOrder' => true,'getBattleFormation' => true,
					'getCoreUser'=>true,'addVipExp'=>true,
					 ),
			'bag' => array(),
			'activity' => array('doRefreshConf' => true, 'refreshConf' => true, 'getAllConf' => true, 'checkConf' => true),
			'arena' => array ('arenaDataRefresh' => true ),
			'guild' => array('getTopGuild' => true, 'refreshUser' => true, 'refreshFields' => true,
			         'addUserPoint' => true, 'guildDataRefresh' => true, 'distributeGrain' => true,
			         'initLevelUpInfo'=>true, 'refreshGuildName' => true),
			'flop' => array('robUserByOther' => true),
			'compete' => array('competeDataRefresh' => true),
	        'gm'=>array('silentUser'=>true, 'sendSysMail'=>true, 'getScriptResult' => true),

			'reward' => array( 'sendTopupFeedBack' => true, 'sendSystemReward' => true ),
			'friend' => array( 'lovedByOther' => true,'addBepkNumByOther' => true,'addFriendNachieveByOther' => true ),
			'heroshop' => array('refreshRank'=>true,'rewardUserOnActClose'=>true,
			        'rewardUser'=>true),
			'boss' => array('bossComing' => true, 'bossStart'=> true, 'rewardForTimer' => true, 'reward' => true,
					'bossEnd' => true),
			'payback' => array( 'addPayBackInfo' => true, 'modifyPayBackInfo' => true, 'getPayBackInfoByTime' => true,
					'openPayBackInfo' => true, 'closePayBackInfo' => true, 'isPayBackInfoOpen' => true,
					'getArrPayBackInfoByTime'=>true),

			'copyteam' => array('startTeamAtk'=>true,'doneTeamBattle'=>true),

			'citywar' => array('signupEnd' => true, 'attackStart' => true, 'battleEnd' => true, 'doAttack' => true, 'checkAttack' => true),

			'groupon' => array('refGoodsList'=>true, 'incGroupOnTimes' => true, 'reissueForTime' => true, 'doReissue' => true),
			'achieve' => array('updateTypeByOther' => true, 'updateTypeArrBySystem' => true),
			'mineral' => array('duePitGuard'=>true,'duePit'=>true,'duePitManually'=>true,'updateRobLog'=>true,'doChangeGuild'=>true,),
			'monthlycard' => array('sendRewardToCenter'=>true),
            'chargeraffle' => array('rewardUserOnLogin'=>true),
            'topupreward' => array('rewardUserOnLogin'=>true),
            'olympic'=>array('modifyUserInfoByOther'=>true,'saveLog'=>true),
            'mail' => array( 'guildrobNotice' => true ),
            'guildrob' => array('onFightWin' => true, 'onFightLose' => true, 'onBattleEnd' => true, 'onTouchDown' => true, 'doRewardOnEnd' => true,
            					'onSpecTimerOut' => true, 'onBattleEndByRpcfw' => true, /*'enterSpecBarnImplement' => true, 'leaveSpecBarn' => true,*/ 'robNotice' => true,
								'addUserReward' => true, 'npcJoin' => TRUE, 'getBattleData' => TRUE,),
			'guildwar' => array('initUserGuildWarByUid' => true, 'reInitUserGuildWarInfo' => true, 'getChampionPresidentInfo' => true,
								'sendMail' => true, 'sendMailByMain' => true,),

			'trustdevice' => array('updateTaskInfoOnDevice'=>true),
			'guildcopy' => array('addAtkNumFromOther' => true),
			'roulette' => array('checkRewardTimer' => TRUE, 'rewardUserBfClose' => TRUE, 'rewardUser' => TRUE),
            'worldgroupon' => array('forgeGoodNum' => true, 'getTeamInfo4Plat' => true),
            'worldcarnival' => array('getBattleFmt' => true, 'getBattleDataOfUsers' => true, 'push' => true, 'getUserBasicInfo' => true),
            'travelshop' => array('addTimer' => true, 'reward' => true, 'rewardUser' => true),
            'worldcompete' => array('getBattleFormation' => true, 'getBattleDataOfUsers' => true),
            'countrywarcross' => array('doNotifySign' => true, 'doCheckAndCreateRoom' => true,
            						'onFightWin' => true,'onFightLose' => true,'onTouchDown' => true,
            						'onBattleEnd' => true,),
            'onerecharge' => array('rewardToCenter' => TRUE, 'doReward' => TRUE),
	        'chargedart'=> array('__sendReward' => TRUE),
	        'mineralelves'=>array('__genMineralElves'=>TRUE,'__sendMineralElvesPrize'=>TRUE),
			'newserveractivity' => array('updateTypeByOtherUser' => true),
			);

	/**
	 * 通过lcserver串化的请求
	 */
	static $ARR_SERIALIZE_METHOD = array(
			'citywar' => array('enter' => true, 'leave' => true, 'offlineEnter' => true, 'cancelOfflineEnter' => true,
						'signup' => true, 'ruinCity' => true, 'mendCity' => true),
	);

	/**
	 * 对外暴露的接口
	 * @var array
	 */
	static $ARR_PUBLIC_METHOD = array (
			'user' => array ('getUser' => true, 'userLogoff' => true,
			        'login'=>true,'userLogin'=>true,
			        'unameToUid'=>true,'getSwitchInfo'=>true ,
			        'getUserInfoByUname'=>true,'openHeroGrid'=>true,
			        'isPay'=>true,'getBattleDataOfUsers'=>true,
			        'getVaConfig'=>true,'setVaConfig'=>true,
			        'setArrConfig'=>true,'getArrConfig'=>true,
			        'checkValue'=>true,'getChargeGold'=>true, 'closeMe' => true,
			        'share'=>true,'getArrUserDressInfo'=>true,'changeName'=>true,
			        'setFigure'=>true,'getChargeInfo'=>true,
					'rankByFightForce'=>true,'rankByLevel'=>true,
			        'removeSkill'=>true, 'getTopActivityInfo'=>true, 'changeSex'=>true),

			'gm' => array('reportClientError' => true),

			'bag' => array ('bagInfo' => true, 'openGridByGold' => true, 'openGridByItem' => true,
							'gridInfo' => true, 'gridInfos' => true, 'useItem' => true,
							'addItemsOtherUser'=>true, 'sellItems' => true, 'useGift' => true),
			'console' => array ('execute' => true ),

			'formation' => array('getFormation' => true, 'getSquad' => true,
							'addHero' => true, 'setFormation' => true,
							'getExtra' => true, 'addExtra' => true, 'delExtra' => true, 'openExtra' => true,
							'getWarcraftInfo' => true, 'craftLevelup' => true, 'setCurWarcraft' => true,
							'getAttrExtra' => true, 'addAttrExtra' => true, 'delAttrExtra' => true, 'openAttrExtra' => true,
                            'getAttrExtraLevel' => true, 'strengthAttrExtra' => true),

			'ncopy'=>array('getCopyList'=>true,'enterCopy'=>true,'enterBase'=>true,'enterBaseLevel'=>true,
					'getPrize'=>true,'doBattle'=>true,'leaveBaseLevel'=>true,'reFight'=>true,
					'getBaseDefeatInfo'=>true,'getCopyRank'=>true,'reviveCard'=>true,
					'getAtkInfoOnEnterGame'=>true,'leaveNCopy'=>true,'sweep'=>true,
					'resetAtkNum'=>true,'getUserRankByCopy'=>true,'clearSweepCd'=>true),

			'ecopy'=>array('getEliteCopyInfo'=>true,'enterCopy'=>true,'doBattle'=>true,
					'leaveCopy'=>true,'getCopyDefeatInfo'=>true,'reviveCard'=>true,
					'reFight'=>true,'buyAtkNum'=>true,'sweep'=>true),

			'acopy'=>array('getCopyList'=>true,'getCopyInfo'=>true,
					'enterCopy'=>true,'enterBaseLevel'=>true,
					'atkGoldTree'=>true,'reviveCard'=>true,
					'reFight'=>true,'leaveBaseLevel'=>true,
					'atkActBase'=>true,'atkGoldTreeByGold'=>true,
					'doBattle'=>true,'buyGoldTreeAtkNum'=>true,
					'buyExpTreasAtkNum'=>true,'refreshBattleInfo'=>true,
					'setBattleInfoValid'=>true, 'buyExpUserAtkNum' => TRUE,
			        'buyDestinyAtkNum' => TRUE, ),

			'tower'=>array('getTowerInfo'=>true,'buyDefeatNum'=>true,
					'defeatMonster'=>true,'endSweep'=>true,'sweep'=>true,
					'leaveLevel'=>true,'enterLevel'=>true,'resetTower'=>true,
					'getTowerRank'=>true,'reviveCard'=>true,'leaveTowerLv'=>true,
					'enterSpecailLevel'=>true,'defeatSpecialTower'=>true,
					'buyAtkNum'=>true,'buySpecialTower'=>true,'sweepByGold'=>true,
			        'getShopInfo' => true, 'buy' => true,),

			'mineral'=>array('capturePit'=>true,'giveUpPit'=>true,'getPitsByDomain'=>true,
					'getPitInfo'=>true,'getSelfPitsInfo'=>true,'explorePit'=>true,
					'duePit'=>true,'grabPit'=>true,'grabPitByGold'=>true,
					'getPitsOfGrabber'=>true,'getDomainIdOfUser'=>true,'occupyPit'=>true,
                    'abandonPit'=>true,'getGuardInfoByUid'=>true,'getRobLog'=>true,
                    'robGuards'=>true,'delayPitDueTime'=>true,'leave'=>true),

			'activity' => array('getActivityConf' => true),

			'levelfund' => array( 'getLevelfundInfo' => true, 'gainLevelfundPrize' => true ),

			'topupfund' => array( 'getTopupFundInfo' => true, 'gainReward' => true ),

			'growup' => array( 'getInfo' => true, 'activation' => true, 'fetchPrize' => true ),

			'supply' => array( 'getSupplyInfo' => true, 'supplyExecution' => true ),

			'sign' => array( 'getAccInfo' => true,'getNormalInfo' => true, 'gainNormalSignReward' => true, 'gainAccSignReward' => true,
								'signUpgrade' => true, 'getSignInfo' => true, 'getMonthSignInfo' => true, 'gainMonthSignReward' => true ),

			'online' => array( 'getOnlineInfo'=> true , 'gainGift' => true ),

			'divine' => array ('getDiviInfo' => true, 'divi' => true,'refreshCurstar'=>true,'drawPrize'=>true ,
					'upgrade'=>true,'refPrize' => true, 'drawPrizeAll' => true, 'oneClickDivine' => true ),

			'pet' => array( 'getAllPet' => true, 'feedPetByItem' => true, 'feedToLimitation' => true,'openKeeperSlot' => true,
					'swallowPetArr' => true,'openSquandSlot' => true ,'squandUpPet' => true, 'fightUpPet' => true, 'collectProduction' => true,
					'learnSkill' => true, 'resetSkill' => true,'squandDownPet' => true,'lockSkillSlot' => true, 'unlockSkillSlot' => true,
					'sellPet' => true, 'getRankList' => true, 'getPetInfoForRank' => true,'getPetHandbookInfo' => true,
					'evolve' => true, 'wash' => true, 'exchange' => true, 'ensure' => true, 'giveUp' => true,'collectAllProduction' => true),

			'reward' => array( 'getRewardList' => true , 'receiveReward' => true, 'getGiftByCode' => true,
						'receiveByRidArr' => true, 'getReceivedList' => TRUE ),

			'mail' => array ( 'sendMail' => true, 'getMailBoxList' => true, 'getSysMailList' => true,
					'getPlayMailList' => true, 'getBattleMailList' => true, 'getSysItemMailList' => true,
					'getMailDetail' => true, 'fetchItem' => true, 'fetchAllItems' => true, 'deleteMail' => true,
					'deleteAllSystemMail' => true, 'deleteAllBattleMail' => true, 'deleteAllPlayerMail' => true,
					'deleteAllMailBoxMail' => true, 'setApplyMailAdded' => true, 'setApplyMailRejected' => true,
					'getMineralMailList' => true, ),

			'chat' => array ('sendPersonal' => true, 'sendWorld' => true, 'sendCopy' => true,
					'sendBroadCast' => true, 'sendGuild' => true, 'sendScreen' => true, ),

			'friend' => array( 'applyFriend' => true, 'addFriend' => true, 'rejectFriend' => true,
						'delFriend' => true, 'getFriendInfo' => true, 'getFriendInfoList' => true,
						'getRecomdFriends' => true, 'isFriend' => true, 'getRecomdByName' => true,
						'unreceiveLoveList' => true, 'receiveAllLove' => true, 'receiveLove' => true,
						'loveFriend' =>true, 'getPkInfo' => true, 'getMyPkInfo' => true,'pkOnce' => true,
						'getBlackers' => true, 'blackYou' => true,'unBlackYou' => true,'getBlackUids' => true),

			'star' => array('getAllStarInfo' => true, 'addFavorByGift' => true, 'addFavorByAllGifts' => true,
					'addFavorByGold' => true,'addFavorByAct' => true, 'answer' => true, 'swap' => true,
					'draw' => true, 'shuffle' => true, 'getReward' => true, 'changeSkill' => true,
					'quickDraw' => true),

			'arena' => array('getArenaInfo' => true, 'getRankList' => true, 'getLuckyList' => true,
					'challenge' => true, 'buy' => true, 'sendRankReward' => true),

			'shop' => array('getShopInfo' => true, 'bronzeRecruit' => true,
					'silverRecruit' => true, 'goldRecruit' => true, 'goldRecruitConfirm' => true,
					'buyGoods' => true, 'buyVipGift' => true),

			'shopexchange' => array('buy' => true),

			'compete' => array('getCompeteInfo' => true, 'refreshRivalList' => true, 'contest' => true,
					'getRankList' => true, 'buyCompeteNum' => true, 'getShopInfo' => true, 'buy' => true,),

			'forge' => array('reinforce' => true, 'autoReinforce' => true, 'upgrade' => true, 'upgradeDress' => true,
					'evolve' => true, 'promote' => true, 'fixedRefresh' => true, 'fixedRefreshAffirm' => true,
					'compose' => true, 'lock' => true, 'unlock' => true, 'develop' => true, 'inlay' => true,
					'outlay' => true, 'upgradePocket' => true, 'fightSoulDevelop' => true, 'fightSoulEvolve' => true,
					'promoteByExp' => true, 'developArm' => true, 'upgradeTally' => true, 'developTally' => true,
					'evolveTally' => true, 'transferTreasure' => true, 'composeRune' => true, 'transferTally' => true,),

			'hero'=>array('getAllHeroes'=>true,'enforce'=>true,'resolve'=>true,
							'sell'=>true,'evolve'=>true,'addArming'=>true,
							'removeArming'=>true,
							'equipBestArming'=>true,'enforceByHero'=>true,
							'getHeroBook'=>true,'addTreasure'=>true,
							'removeTreasure'=>true,'equipBestFightSoul'=>true,
							'addFashion'=>true,'removeFashion'=>true,
							'addFightSoul'=>true, 'removeFightSoul'=>true,
							'lockHero'=>true,'unlockHero'=>true,'activateTalent'=>true,
							'activateTalentConfirm'=>true,'activateTalentUnDo'=>true,
							'inheritTalent'=>true, 'transfer'=>true, 'transferConfirm'=>true,
							'transferCancel'=>true,'develop'=>true,'activateSealTalent'=>true,
							'addGodWeapon'=>true,'removeGodWeapon'=>true,'addPill'=>true,
							'addPocket' =>true,'removePocket'=>true,'develop2red'=>true,
							'removePill' => true, 'removePillByType' => true,
							'addTally' => true, 'removeTally' => true, 'activeMasterTalent' => true,
							'addArrPills' => true, 'activeDestiny' => true, 'resetDestiny' => true),
			'switchnode'=>array('getSwitchInfo'=>true),

			'fragseize' => array( 'getSeizerInfo'=> true, 'getRecRicher' => true, 'fuse' => true, 'seizeRicher' => true,
									'whiteFlag' => true,'quickSeize' => true, 'oneKeySeize' => true),

			'mysteryshop' => array('rebornHero'=>true,'resolveHero'=>true,'buyGoods'=>true,'resolveHero2Soul'=>true,
			                        'getShopInfo'=>true,'playerRfrGoodsList'=>true,
			                        'resolveItem'=>true,'rebornItem'=>true, 'resolveTreasure'=>true,
			                        'resolveDress'=>true,'rebornDress'=>true,'rebornTreasure'=>true,
							        'rebornOrangeHero'=>true, 'resolveRune' => true, 'rebornPocket'=>true, 'rebornRedHero'=>true,
									'rebornFightSoul' => true, 'resolveHeroJH' => true, 'resolveTally' => true,
									'rebornTally' => true,
									'previewRebornHero'=>true,'previewResolveHero'=>true,'previewResolveHero2Soul'=>true,
			                        'previewResolveItem'=>true,'previewRebornItem'=>true, 'previewResolveTreasure'=>true,
			                        'previewResolveDress'=>true,'previewRebornDress'=>true,'previewRebornTreasure'=>true,
							        'previewRebornOrangeHero'=>true, 'previewResolveRune' => true, 'previewRebornPocket'=>true, 'previewRebornRedHero'=>true,
									'previewRebornFightSoul' => true, 'previewResolveHeroJH' => true, 'previewResolveTally' => true,
									'previewRebornTally' => true,),

			'destiny' => array('getDestinyInfo'=>true,
			                    'activateDestiny'=>true),

            'guild' => array('createGuild' => true, 'applyGuild' => true, 'cancelApply' => true, 'reward' => true,
                    'agreeApply' => true, 'refuseApply' => true, 'quitGuild' => true, 'kickMember' => true,
                    'modifySlogan' => true, 'modifyPost' => true, 'modifyPasswd' => true, 'contribute' => true,
					'getMemberInfo' => true, 'getGuildInfo' => true,'getGuildApplyList' => true, 'getGuildList' => true,
					'getGuildListByName' => true, 'upgradeGuild' => true, 'setVicePresident' => true, 'unsetVicePresident' => true,
					'transPresident' => true, 'dismiss' => true, 'impeach' => true, 'getMemberList' => true, 'getRecordList' => true,
					'getDynamicList' => true, 'refuseAllApply' => true, 'fightEachOther' => true, 'leaveMessage' => true,
					'getMessageList' => true, 'getGuildRankList' => true, 'harvest' => true, 'lottery' => true, 'refreshOwn' => true,
					'refreshAll' => true, 'buyFightBook' => true, 'share' => true, 'getEnemyList' => true, 'getShareInfo' => true,
					'getRefreshInfo' => true, 'getHarvestList' => true, 'modifyIcon' => true, 'quickHarvest' => true, 'modifyName' => true,
					'promote' => true,),

		    'heroshop' => array('getMyShopInfo'=>true,'buyHero'=>true,'leaveShop'=>true),

		    'boss' => array('getBossOffset'=> true, 'enterBoss'=> true, 'attack'=> true, 'revive'=> true, 'over'=> true,
		    		'leaveBoss'=> true, 'inspireBySilver'=> true,'inspireByGold'=> true,'getSuperHero'=> true,
		    		'getAtkerRank'=> true, 'getMyRank' =>true, 'setFormationSwitch' => true, 'setBossFormation' => true,),

		    'spend' => array('getInfo' => true, 'gainReward' => true,),

		    'guildshop' => array('getShopInfo' => true, 'buy' => true, 'refreshList' => true),

		    'robtomb'=>array('getMyRobInfo'=>true,'rob'=>true),

		    'signactivity' => array('getSignactivityInfo' => true, 'gainSignactivityReward' => true),

		    'iteminfo' => array('getArmBook' => true, 'getTreasBook' => true, 'getGodWeaponBook' => true,
							'getTallyBook' => true,'getChariotBook'=>true,),

		    'hunt' => array('getHuntInfo' => true, 'skip' => true, 'skipHunt' => true, 'huntSoul' => true,
							'rapidHunt' => true),

		    'copyteam' => array('getCopyTeamInfo'=>true,'createTeam'=>true,
		                'joinTeam'=>true,'getAllInviteInfo'=>true,
		                'inviteGuildMem'=>true,'buyAtkNum'=>true,'setInviteStatus'=>true),

		    'active' => array('getActiveInfo' => true, 'getTaskPrize' => true, 'getPrize' => true, 'upgrade' => true),

		    'weal' => array('getKaInfo' =>true,'kaOnce' => true),

            'vipbonus' => array( 'getVipBonusInfo' => true, 'fetchVipBonus' => true, 'buyWeekGift' => true ),

            'mysmerchant' => array( 'getShopInfo' => true, 'playerRfrGoodsList' => true, 'buyGoods' => true , 'buyMerchantForever' => true),

            'hcopy' => array('getArrPassCopy' => true, 'getCopyInfo' => true, 'enterBaseLevel' => true,
						'doBattle' => true, 'leaveBaseLevel' => true, 'reviveCard' => true,
						'getAllCopyInfos' => true),

            'citywar' => array('getGuildSignupList' => true, 'getCitySignupList' => true, 'getCityAttackList' => true,
            			'getCityInfo' => true, 'getCityId' => true, 'inspire' => true, 'buyWin' => true,
            			'getReward' => true, 'clearCd' => true),

            'achieve' => array('getInfo' => true, 'obtainReward' => true),

            'actexchange' => array('buyGoods'=>true, 'getShopInfo'=>true, 'rfrGoodsList'=>true ),

            'groupon' => array('getShopInfo'=>true, 'buyGood'=>true, 'recReward'=>true, 'leaveGroupOn'=>true ),

            'monthlycard' => array('getCardInfo'=>true,'getDailyReward'=>true,
                        'getGift'=>true,'buyMonthlyCard'=>true),

            'chargeraffle' => array('getInfo'=>true,'raffle'=>true,'getReward'=>true),

            'dragon' => array('getMap'=>true, 'getUserBf'=>true, 'move'=>true, 'reset'=>true, 'doublePrize'=>true,
                        'bribe'=>true, 'answer'=>true, 'goon'=>true, 'fight'=>true, 'skip'=>true, 'onekey'=>true,
                        'buyHp'=>true, 'buyAct'=>true, 'autoMove'=>true, 'aiDo'=>true, 'trial'=>true, 'buyGood' => true,
                        'contribute'=>true, 'fightBoss'=>true, 'bossDirectWin'=>true),

            'dragonshop' => array('getShopInfo' => true, 'buy' => true),

            'guildtask' => array('getTaskInfo' => true,'refTask' => true,'acceptTask' => true,
							'forgiveTask' => true, 'doneTask' => true, 'handIn' => true),

            'topupreward' => array('getInfo' => true, 'rec' => true),

            'olympic' => array('enterOlympic'=>true,'leave'=>true,'getInfo'=>true,
                    'getFightInfo'=>true,'signUp'=>true,'challenge'=>true,
                    'clearChallengeCd'=>true,'cheer'=>true),

            'lordwar' => array('enterLordwar'=>true,'leaveLordwar'=>true,'register'=>true,
             		'getMyTeamInfo'=>true,'getMyRecord'=>true,'updateFightInfo'=>true,'clearFmtCd'=>true,
             		'getTempleInfo'=>true, 'worship' => true, 'support' => true, 'getMySupport' => true,
             		'getLordInfo' => true,'getPromotionInfo' => true, 'getPromotionBtl' => true, 'push' => true,
             		'getPromotionHistory' => true ),
             'lordwarshop' => array( 'getInfo' => TRUE, 'buy' => true ),

			'stepcounter' => array('checkStatus'=>true, 'recReward'=>true),

			'mergeserver' => array('getRewardInfo' => true, 'receiveLoginReward' => true, 'receiveRechargeReward' => true),

            'weekendshop' => array('getInfo' => true, 'rfrGoodList' => true, 'buyGood' => true, 'getShopNum' => true),

			'roulette' => array('getMyRouletteInfo' => true, 'rollRoulette' => true, 'receiveBoxReward' => true,
								'getRankList' => TRUE),

            'dressroom' => array('getDressRoomInfo' => true, 'activeDress' => true, 'changeDress' => true),

			'barnshop' => array('getShopInfo' => true, 'buy' => true),

			'guildrob' => array('create' => true, 'enter' => true, 'getEnterInfo' => true, 'join' => true, 'leave' => true, 'removeJoinCd' => true, /*'speedUp' => true,*/
					'getGuildRobAreaInfo' => true, 'leaveGuildRobArea' => true, /*'enterSpecBarn' => true,*/ 'getRankByKill' => true, 'getGuildRobInfo' => true,
			        'getInfo' => TRUE, 'offline' => TRUE, ),

			'limitshop' => array('getLimitShopInfo' => true, 'buyGoods' => true),

            'godweapon' => array('reinForce' => true, 'evolve' => true, 'resolve' => true, 'reborn' => true, 'wash' => true, 'replace' => true,
                    'batchWash' => true, 'ensure' => true, 'cancel' => true, 'legend' => true, 'lock' => true, 'unLock' => true, 'transfer' => true,
					'previewReborn' => true, 'previewResolve' => true),

			'retrieve' => array('getRetrieveInfo' => true, 'retrieveByGold' => true, 'retrieveBySilver' => true),

			'pass' => array( 'enter' => true, 'getRankList' => true, 'getOpponentList' => true, 'dealChest' => true,
							'leaveLuxuryChest' => true, 'attack' => true, 'dealBuff' => true, 'getShopInfo' => true, 'buyGoods' => true,
							'refreshGoodsList' => true, 'setPassFormation' => true, 'buyAttackNum' => true ,'sweep' => true),

			'guildwar' => array('enter' => true, 'leave' => true, 'signUp' => true, 'getUserGuildWarInfo' => true,
								'getGuildWarMemberList' => true, 'updateFormation' => true, 'clearUpdFmtCdByGold' => true, 'buyMaxWinTimes' => true,
								'changeCandidate' => true, 'getMyTeamInfo' => true, 'getGuildWarInfo' => true, 'getHistoryCheerInfo' => true,
								'cheer' => true, 'getTempleInfo' => true, 'worship' => true, 'getHistoryFightInfo' => true,
								'getReplay' => true, 'getReplayDetail' => true, ),

			'bowl' => array('getBowlInfo' => true, 'buy' => true, 'receive' => true),

			'festival' => array('getFestivalInfo' => true, 'compose' => true),

			'scoreshop' => array('getShopInfo' => true, 'buy' => true),

            'athena' => array('getAthenaInfo' => true, 'upGrade' => true, 'synthesis' => true, 'buy' => true, 'changeSkill' => true, 'getArrMasterTalent' => true),

            'guildcopy' => array('getUserInfo' => true, 'getCopyInfo' => true, 'setTarget' => true, 'attack' => true, 'getRankList' => true, 'addAtkNum' => true,
								'refresh' => true, 'recvPassReward' => true, 'getBoxInfo' => true, 'openBox' => true, 'getShopInfo' => true, 'buy' => true, 'getLastBoxInfo' => true,
								'bossInfo' => true, 'buyBoss' => true, 'attackBoss' => true,),

			'moon' => array('getMoonInfo' => true, 'attackMonster' => true, 'openBox' => true, 'attackBoss' => true, 'addAttackNum' => true,
							'getShopInfo' => true, 'buyGoods' => true, 'refreshGoodsList' => true, 'buyBox' => true,
							'buyTally'=> true, 'getTallyInfo' => true, 'refreshTallyGoodsList' => true,'sweep'=>true),

		    'worldpass' => array
		    	(
		    			'getWorldPassInfo' => true,
		    			'attack' => true,
		    			'reset' => true,
		    			'addAtkNum' => true,
						'getMyTeamInfo' => true,
						'getRankList' => true,
						'refreshHeros' => true,
						'getShopInfo' => true,
						'buyGoods' => true,
				),

			'worldarena' => array
				(
						'getWorldArenaInfo' => true,
						'signUp' => true,
						'updateFmt' => true,
						'attack' => true,
						'buyAtkNum' => true,
						'reset' => true,
						'getRecordList' => true,
						'getRankList' => true,
				),

			'union' => array('getInfoByLogin' => true, 'getInfo' => true, 'fill' => true),

            'worldgroupon' => array('getInfo' => true, 'buy' => true, 'recReward' => true, ),

            'travelshop' => array('getInfo' => true, 'buy' => true, 'getPayback' => true, 'getReward' => true),

            'worldcarnival' => array
            	(
            			'getCarnivalInfo' => true,
            			'updateFmt' => true,
            			'getRecord' => true,
            			'getFighterDetail' => true,
            	),
            'mission' => array( 'getMissionInfo' => true, 'doMissionItem' => true,'doMissionGold' => true,
            'getRankList' => true, 'receiveDayReward' => TRUE, 'getMissionInfoLogin' => true ),
			'missionmall' => array('getShopInfo' => true, 'buy' => true),

			'blackshop' => array('getBlackshopInfo'=>true,'exchangeBlackshop'=>true),

			'fsreborn' => array('getInfo'=>true,'reborn'=>true),

			'happysign' => array('getSignInfo' => true, 'gainSignReward' => true),

			'worldcompete' => array('getWorldCompeteInfo' => true, 'attack' => true, 'buyAtkNum' => true,
						'getFighterDetail' => true, 'getRankList' => true, 'refreshRival' => true, 'getShopInfo' => true,
						'buyGoods' => true, 'getPrize' => true, 'worship' => true, 'getChampion' => true,
			),

	        'desact' => array('getDesactInfo' => true, 'gainReward' => true),

	        'rechargegift'	=> array('getInfo' => true, 'obtainReward' => true),
	        'countrywarcross' => array( 'loginCross' => true,'enter' => true,'leave' => true,'getEnterInfo' => true,
								'joinTransfer' => true,'inspire' => true,'clearJoinCd' => true,'recoverByUser' => true,
								'setRecoverPara' => true, 'onLogoff' => true, 'turnAutoRecover' => true,'getRankList' => true,
			),
			 'countrywarinner' => array(
							 'getCoutrywarInfoWhenLogin' => true,'getCoutrywarInfo' => true,'getFinalMembers' => true,'getMySupport' => true,
			 				'signForOneCountry' => true, 'getLoginInfo' => true,'supportOneUser' => true,'supportFinalSide' => true,
			 				'worship' => true,'exchangeCocoin' => true,'getRankList' => true,
			 ),
			 'countrywarshop' => array('getShopInfo' => true, 'buy' => true,),
	         'envelope' => array('getInfo' => TRUE, 'getSingleInfo' => TRUE,
	             'getSingleLeft' => TRUE, 'send' => TRUE, 'open' => TRUE, 'rewardUser' => TRUE,
	         ),
	         'onerecharge' => array('getInfo' => TRUE, 'gainReward' => TRUE,),
	         'chargedart' => array('enterChargeDart' => true, 'leave' => true, 'getOnePageInfo' => true, 'getChargeDartInfo' => true, 'ChargeDartLook' => true,
	               'rob' => true, 'enterShipPage' => true, 'refreshStage' => true, 'inviteFriend' => true, 'acceptInvite' => true, 'beginShipping' => true,
	               'openRage' => true, 'finishByGold' => true, 'buyRobNum' => true, 'buyShipNum' => true, 'buyAssistanceNum' => true, 'getThisChargeDartInfo' => true,
	               'getStageInfo' => true, 'getAllMyInfo' => true,
	         ),
	         'stylish' => array('getStylishInfo' => true, 'activeTitle' => true, 'setTitle' => true,),
	         'newserveractivity' => array('getInfo' => true, 'obtainReward' => true, 'buy' => true),
	         'mineralelves'=>array('getMineralElves'=>true,'getMineralElvesByDomainId'=>true,'occupyMineralElves'=>true,'leave'=>true,'getSelfMineralElves'=>true),
	         'pill'=>array('fuse'=>true,),
	         'festivalact' => array('getInfo' => true, 'taskReward' => true, 'buy' => true, 'exchange' => true, 'signReward' => true),
	         'chariot'=>array('equip'=>true,'unequip'=>true,'enforce'=>true,'resolve'=>true,'previewResolve'=>true,
	         'reborn'=>true,'previewReborn'=>true,'develop'=>true),
	         'sevenslottery' => array('getSevensLotteryInfo' => true, 'lottery' => true, 'getShopInfo' => true, 'buy' => true),
	         'welcomeback' => array('getOpen' => true, 'getInfo' => true, 'gainReward' => true, 'buy' => true),
	         
	         );


}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
