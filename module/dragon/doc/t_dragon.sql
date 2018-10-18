set names utf8;

CREATE TABLE IF NOT EXISTS t_dragon
(
   uid  int unsigned not null comment '用户id',
   mode int unsigned not null default 0 comment '模式',
   last_time int unsigned not null comment '上次探宝时间',
   act  int unsigned not null comment '寻龙剩余行动力',
   resetnum int unsigned not null comment '当天已重置次数',
   free_reset_num int unsigned not null comment '累积免费重置次数',
   buy_act_num int unsigned not null comment '当天购买行动力次数',
   free_ai_num int unsigned not null comment '当天一键寻龙免行动力次数',
   buy_hp_num int unsigned not null comment '当天购买血池次数',
   hp_pool int unsigned not null comment '血池',
   point int unsigned not null comment '积分',
   total_point int unsigned not null comment '总积分',
   once_max_point int unsigned not null default 0 comment '单次最高积分',
   floor  int unsigned not null comment '当前所处层',
   posid  int unsigned not null comment '当前位置坐标',
   hasmove int unsigned not null comment '本层是否寻龙 用于限制一键寻龙',
   va_data  blob not null comment '武将血量arrhp =>array(hid=>hp int,...), 当前状态cur_event => array(id=>$eventid, data=>$uid..., point=>, other=>...), arraddtion=>array(id=>..武力加成),当前地图 map=>array(0=>array(array(eid=>10000事件id, status=>array(0=>0是否走过, 1=>1是否被炸，2=>1雾是否被驱散, 3=>1事件是否被促发)), array(10001, 1状态)...事件id), 1=>array()...为以后分多层地图准备)',
   va_bf  blob not null comment '战斗相关 阵型数据',
   primary key(uid)
)engine = InnoDb default charset utf8;