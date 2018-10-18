set names utf8;

CREATE TABLE IF NOT EXISTS t_athena(
  uid int unsigned not null comment '用户id',
  va_data blob not null comment '
              array[
                detail => array[
                  {
                    attrId(属性id) => level(等级), ...
                  }
                  , ...
                ],
                special => array{attrId(特殊技能id), ...},
                treeNum => num(开启页数),
                buyNum => array[
                  itemTplId => num, ...
                ],
                buyTime => num,
                arrTalent => array{
                  talentId(觉醒能力id)
                },
              ]
              ',
  primary key (uid)
)engine = InnoDb default charset utf8;