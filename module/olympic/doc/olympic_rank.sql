set names utf8;
CREATE TABLE IF NOT EXISTS t_olympic_rank( 
    sign_up_index int unsigned not null comment '报名位置从0到31',
    olympic_index int unsigned not null comment '比赛顺序 从0到31',
    uid int unsigned not null comment '用户ID',
    final_rank int unsigned not null comment '比赛结果名次',
    primary key(sign_up_index)
)default charset utf8 engine = InnoDb;
