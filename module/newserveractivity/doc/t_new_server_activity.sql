set names utf8;

create table if not exists t_new_server_activity(
    uid int unsigned not null comment '用户ID',
    va_info blob not null comment 'array[taskInfo => array[任务类型type => array[状态s=> array[任务id taskId => "0未完成, 1完成, 2已领奖" ], 进度fn => int]])',
    va_goods blob not null comment 'array[day(如果天数有,则代表那一天的抢购商品已经购买了), ...]',
    primary key(uid) 
)default charset utf8 engine = InnoDb;