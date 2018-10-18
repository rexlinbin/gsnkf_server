<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: IUser.class.php 255252 2016-08-09 07:30:35Z GuohaoZheng $$
 *
 **************************************************************************/

/**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/IUser.class.php $$
 * @author $$Author: GuohaoZheng $$(lanhongyu@babeltime.com)
 * @date $$Date: 2016-08-09 07:30:35 +0000 (Tue, 09 Aug 2016) $$
 * @version $$Revision: 255252 $$
 * @brief
 *
 **/

interface IUser
{
	/**
	 * 玩家登录到游戏服务器
	 * @param $arrReq
	 * 	{
	 * 		pid:
	 * 		openDateTime:
	 * 		timestamp:
	 * 		ptype:
	 * 		hash:
	 * 	}
	 * @return string ok 成功  full 服务器人太多 timeout 超时 fail 失败
	 */
	public function login($arrReq);


	/**
	 * 得到玩家所有的用户(支持一个帐号有多个角色)
	 * @return array
	 * <code>
	 * [
	 * 		uid:用户id
	 * 		utid:用户模版id
	 * 		name:user名字
	 * ]
	 * </code>
	 */
	public function getUsers();


	/**
	 * 创建角色
	 * @param uint $utid 用户模版id  1:女 2:男
	 * @param string $uname
	 * @return array
	 * <code>
	 * [
	 *     ret:int
	 *     uid:int
	 * ]
	 * </code>
	 * ret的取值如下：
	 * ok                创建成功
	 * invalid_char     用户名不符合规范
	 * sensitive_word   名字中包含敏感词
	 * name_used        名字已经使用
	 * fail             失败
	 */
	public function createUser($utid, $uname);



	/**
	 * 得到随机名字
	 * @param uint $num 返回名字数量，如果大于20,返回20个。
	 * @param uint $gender 0:女 1：男
	 * @return array 名字组成的数组
	 */
	public function getRandomName($num, $gender=0);


	/**
	 * 使用uid用户进入游戏
	 * @param unit $uid 用户id
	 * @return
	 * 		ret =
	 * 			ok
	 * 			logined: 已经在其他地方登录过
	 * 			ban
	 * 			fail
	 * 			badorder:有没有扣除的代充金币
	 *  [1]ret = ban 时还有
	 *  	banInfo =>
	 *  		{
	 *  			time:  封号封号什么时候
	 *  			msg:
	 *  		}
	 *  [2]ret = badorder时还有
	 *  		num:扣除代充金币还差多少
	 */
	public function userLogin($uid);

	/**
	 * 得到用户信息
	 * @see UserDef::$USER_FIELDS
	 * @return array
	 * <code>
	 * [
	 * 	uid:用户id,
	 * 	pid:用户pid
	 *  server_id:所在服server_id
	 * 	uname:用户名字,
	 * 	utid:用户模版id,
	 *  htid:主角武将的htid
	 *  dress:array        玩家的时装信息
	 *  [
	 *      posId=>dressTmplId
	 *  ]
	 *  level:玩家级别
	 *  create_time: 角色创建时间戳
	 * 	execution:当前行动力,
	 * 	execution_time : 上次恢复行动力时间
	 * 	buy_execution_accum : 今天已经购买行动力数量

	 * 	vip:vip等级,
	 * 	silver_num:银两,
	 * 	gold_num:金币RMB,
	 * 	exp_num:阅历,
	 *  soul_num:将魂数目
	 *  jewel_num：魂玉数目
	 *  prestige_num：声望数目
	 *  tg_num:天工令
	 *  wm_num：威名
	 *  stamina:耐力
	 *  stamina_time:上次恢复耐力的时间
     *  stamina_max_num:耐力上限
	 * 	fight_cdtime : 战斗冷却
	 * 	ban_chat_time : 禁言结束时间
	 *  max_level:玩家的等级上限
	 *  hero_limit:武将数目限制
	 *  figure:玩家头像
	 *  title:玩家称号
	 *  fight_force:玩家战斗力
	 *  honor_num:玩家荣誉
	 *  fame_num:玩家名望
	 *  book_num:书的数量
	 *  fs_exp:战魂经验
	 *  jh:武将精华
	 *  tally_point:兵符积分
	 *  cross_honor:跨服荣誉
	 *  user_item_gold:通过使用道具获得的金币，可以增加vip经验
	 *  masterSkill:array
	 *  [
	 *      skilltype=>array  skilltype是技能的类型    如attackSkill是普通攻击技能  rageSkill是怒气技能
	 *      [
	 *          skillid:技能id
	 *          learnsource:学习途径 1是名将模块 2是主角星魂
	 *      ]
	 *  ]
	 * ]
	 * </code>
	 */
	public function getUser ();

	/**
	 * 设置静音
	 * @param unknown_type $isMute 1 静音 0 有声音
	 * @return 'ok'
	 */
	public function setMute($isMute);


	/**
	 * 购买体力
	 * @param int $num
	 * @return overflow(超出最大值)/ok(成功)
	 */
	public function buyExecution($num);




	/**
	 * 是否充值过
	 * @return boolean false没有 true有充值
	 */
	public function isPay();


	/**
	 * 加金币
	 * @param unknown_type $uid
	 * @param uint $orderId 订单号
	 * @param uint $gold 人民币，分为单位
	 */
	public function addGold4BBpay($uid, $orderId, $gold);


	/**
	 * 得到用户配置信息
	 * @return array
	 * object
	 *  [
	 *   配置信息， 通过IUser.setVaConfig保存的key/value对。
	 *  ]
	 */
	public function getVaConfig();

	/**
	 * 前端保存设置用, 整存，整取（海贼中用来做成保存消费提示）
	 * @param $vaConfig 所有的配置
	 * @return 'ok'
	 */
	public function setVaConfig($vaConfig);

	/**
	 * 前端保存设置用。 可单独设置某个key
	 * @param string $key
	 * @param string $value
	 * @return 'ok'
	 */
	public function setArrConfig($key, $value);

	/**
	 *  返回所有的配置
	 *  @return array
	 *  <code>
	 *  key => value
	 *  </code>
	 */
	public function getArrConfig();

	/**
	 * 获取玩家的时装信息
	 * @param array $arrUid
	 * @return array
	 * [
	 *     array
	 *     [
	 *         uid:int
	 *         utid:int
	 *         dress:array
	 *     ]
	 * ]
	 */
	public function getArrUserDressInfo($arrUid);


	/**
	 *
	 * @param string $uname
	 * @return string 0表示没有此名字的用户
	 */
	public function unameToUid($uname);
	/**
	 *
	 * 获取功能节点信息,返回所有开启的功能节点ID
	 * <code>
	 * [
	 *     switchId:int        对应于策划配置的配置表里的ID
	 * ]
	 * </code>
	 */
	public function getSwitchInfo($uid);
	/**
	 * 根据uname获取用户的信息
	 * @param string uname
	 * @return array
	 * <code>
	 * [
	 *     err:string nosuchname或者ok
	 *     uid:int
	 *     utid:int    1:女 2:男
	 *     dress:array
	 * ]
	 * </code>
	 */
	function getUserInfoByUname($uname);
	/**
	 * @param int $type 1是金币开启 2是道具开启   默认是1
	 * @return string 'ok'
	 */
	function openHeroGrid($type=1);
	/**
	 *
	 * @param array $arrUid
	 * @return array
	 * <code>
	 * [
	 *     uid=>array
	 *          [
	 *              uname:string
	 *              title:int
	 *              guild_name:string
	 *              arrHero:array
	 *                      [
	 *                          position=>array
	 *                                  [
	 *                                      hid:int    武将id
	 *                                      htid:int   武将模板id
	 *                                      level:int  等级
	 *                                      destiny:int 天命
	 *                                      evolve_level:int 转生次数
	 *                                      max_hp:int        生命
	 *                                      general_atk:int 攻击
	 *                                      physical_def:int    物理防御
	 *                                      magical_def:int     魔法防御
	 *                                      fight_force:int     战斗力
	 *                                      equipInfo:array
	 *                                              [
	 *                                                  arming:array
	 *                                                      [
	 *                                                          position=>itemId
	 *                                                      ]
	 *                                                  skillBook:array
	 *                                                      [
	 *                                                          position=>itemId
	 *                                                      ]
	 *                                                  treasure:array
	 *                                                      [
	 *                                                          position=>itemId
	 *                                                      ]
	 *                                                  chariot:array
	 *                                                  	[
	 *                                                  		position=>itemId
	 *                                                  	]
	 *                                              ]
	 *                                  ]
	 *                      ]
	 *              squad:array  阵容
	 *                      [
	 *                          index=>hid
	 *                      ]
	 *              littleFriend:array
	 *              [
	 *                  array
	 *                  [
	 *                      hid=>int
	 *                      htid=>int
	 *                      position=>int
	 *                  ]
	 *              ]
	 *              arrPet:array
	 *              [
	 *                  array
	 *                  [
	 *                      petid=>int
	 *                      pet_tmpl=>int
	 *                      level=>int
	 *                      arrSkill=>array
	 *                      [
	 *                          skillNormal:array
	 *                          [
	 *                              array
	 *                              [
	 *                                  id=>int
	 *                                  level=>int
	 *                                  status=>int
	 *                              ]
	 *                          ]
	 *                          skillTalent:array    结构同skillNormal
	 *                          skillProduct:array   结构同skillNormal
	 *                      ]
	 *                  ]
	 *              ]
	 *              rage_skill:int
	 *              attack_skill:int
	 *          ]
	 *          craft_info:array
	 *          attrExtraProfit:array
	 *          [
	 *              hid=>array
	 *              [
	 *                  attrId=>attrValue
	 *              ]
	 *          ]
	 *          attrExtraLevel:array
	 *          [
	 *         		attrPos(助战位置) => attrLv(这个助战位置上的助战军等级;-1未开,0开了,N等级)
	 *         	]
	 * ]
	 * </code>
	 */
	public function getBattleDataOfUsers($arrUid);


	public function checkValue($key,$value,$method);
	/**
	 * @return int  当前玩家的充值金额
	 */
	public function getChargeGold();
	/**
	 * 微博分享或者微信分享   首次分享给用户加金币
	 * @return array
	 * <code>
	 * [
	 *     gold:int
	 *     silver:int
	 * ]
	 * </code>
	 */
	public function share();


	/**
	 * 关闭自己
	 */
	public function closeMe();

	/**
	 * 更改玩家的角色名
	 * @param string $uname
	 * @param int $spendType  1.消耗金币  2.消耗物品
	 * @return string invalid_char(包含非法字符) sensitive_word(包含敏感词) duplication(名字已被使用) ok(修改成功)
	 */
	public function changeName($uname,$spendType);

	/**
	 * 设置头像
	 * @param int $figure    头像id
	 * @return string 'ok'
	 */
	public function setFigure($figure);

	/**
	 * 获取玩家的人民币消费情况
	 * @return array
	 * <code>
	 * [
	 *     is_pay:bool
	 *     can_buy_monthlycard:bool
	 *     charge_info:array
	 *     [
	 *         charge_id => charge_time
	 *     ]
	 * ]
	 * </code>
	 */
	public function getChargeInfo();
	/**
	 * 战力排行
	 * @return array
	 * <code>
	 * [
	 * 		0:array
	 * 			[
	 * 				selfRank: int               返回个人排名
	 * 			]
	 * 		1:array                      		返回排行榜前50的排行
	 *	 		[
	 *				[
	 *		 			uid: int                用户id
	 *					htid: int               主英雄
	 *					uname: string           用户名
	 *					level: int              等级
	 *	 				fight_force: int     	战力
	 *				 	rank: int            	排名
	 * 					guild_name: string   	军团名
	 *					dressInfo: array        时装信息
	 *					vip: int                VIP等级
	 *				]
	 * 			]
	 * ]
	 * </code>
	 */
	public function rankByFightForce();

	/**
	 * 等级排行
	 * @return array
	 * <code>
	 * [
	 * 		0:array
	 * 			[
	 * 				selfRank: int               返回个人排名
	 * 			]
	 * 		1:array                      		返回排行榜前50的排行
	 *	 		[
	 *				[
	 *					uid: int                用户id
	 *					htid: int               主英雄
	 *		 			uname: string           用户名
	 *	 				level: int           	等级
	 *					fight_froce: int        战力
	 *					rank: int            	排名
	 * 					guild_name: string   	军团名
	 * 					dressInfo: array        时装信息
	 * 					vip:                    VIP等级
	 *				]
	 * 			]
	 * ]
	 * </code>
	*/
	public function rankByLevel();

	/**
	 * 卸载主角技能
	 * @param int $skillType  1是普通攻击技能 2是怒气技能
	 * @return string 'ok'
	 */
	public function removeSkill($skillType);

	/**
	 * 获得活动置顶信息
	 *
	 * @return
	 * {
	 * 		'compete' => array
	 * 			{
	 * 				'status' => 'ok'/'invalid'		ok代表有效，需要继续更新extra信息判断/invalid代表功能节点没打开，或者没分组，或者不在有效的时间范围内，extra里的信息不用看啦
	 * 				'extra' => array
	 * 					{
	 * 						'num' => int			剩余比武次数
	 * 					}
	 * 			}
	 * 		'worldcompete' => array
	 * 			{
	 * 				'status' => 'ok'/'invalid'
	 * 				'extra' => array
	 * 					{
	 * 						'num' => int			剩余比武次数
	 * 						'box_reward' => int 	未领宝箱个数
	 * 						'can_worship' => int	可以膜拜次数
	 * 					}
	 * 			}
	 * 		'pass' => array
	 * 			{
	 * 				'status' => 'ok'/'invalid'
	 * 				'extra' => array
	 * 					{
	 * 						'num' => int			剩余攻打次数
	 * 						'pass' => int 			是否已经通关
	 * 						'curr' => int 			当前在第几关
	 * 					}
	 * 			}
	 * 		'moon' => array
	 * 			{
	 * 				'status' => 'ok'/'invalid'
	 * 				'extra' => array
	 * 					{
	 * 						'normal_num' => int		普通剩余攻打次数
	 * 						'nightmare_num' => int	梦魇剩余攻打次数
	 * 					}
	 * 			}
	 * 		'worldpass' => array
	 * 			{
	 * 				'status' => 'ok'/'invalid'
	 * 				'extra' => array
	 * 					{
	 * 						'num' => int			剩余攻打次数
	 * 					}
	 * 			}
	 * 		'tower' => array
	 * 			{
	 * 				'status' => 'ok'/'invalid'
	 * 				'extra' => array
	 * 					{
	 * 						'reset_num' => int		剩余重置次数
	 * 						'can_fail_num' => int	还能够失败的次数
	 * 					}
	 * 			}
	 * 		'dragon' => array
	 * 			{
	 * 				'status' => 'ok'/'invalid'
	 * 				'extra' => array
	 * 					{
	 * 						'num' => int			剩余免费重置次数
	 * 					}
	 * 			}
	 * 		'dart' => array
	 * 			{
	 * 				'status' => 'ok'/'invalid'
	 * 				'extra' => array
	 * 					{
	 * 						'num' => int			剩余运送次数
	 * 					}
	 * 			}
	 *      'helltower' => array
	 *          {
	 *              'status' => 'ok'/'invalid'
	 *              'extra' => array
	 *                  {
	 *                      'reset_num' => int		剩余重置次数
	 * 						'can_fail_num' => int	还能够失败的次数
	 *                  }
	 *          }
	 * }
	 */
	public function getTopActivityInfo();

	/**
	 * 主角变性（更改主角性别、更换主角武将形象）
	 * @return array 主将信息
	 * 或string fail
	 */
	public function changeSex();
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
