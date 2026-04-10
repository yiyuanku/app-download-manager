接口DEMO  http://121.43.35.128/
上架中游戏列表接口
接口地址：
{{host}}/v1/?type={{type}}&cpsId={{cpsId}}&gamename={{gamename}}&edition={{edition}}&gametype={{gametype}}&pagecode={{pagecode}}&pagenum={{pagenum}}

请求参数说明：
{{host}} 接口域名 固定值 https://www.steamsy.com
{{cpsId}} 你的渠道账号(推广账号)
{{edition}} 游戏类型筛选：空 全部、 0官方、 1 0.1折 2BT （默认空）
{{type}} 平台 ：空 全部，0  安卓+双端游戏，1  苹果+双端游戏 （默认空）
{{gamename}} 搜索游戏名
{{gametype}} 游戏分类 默认空
{{pagecode}} 页码 默认1
{{pagenum}} 每页加载多少条

返回参数说明：
total_num 总条数
now_page 当前页数
total_page总页数

lists 数据列表
id 游戏ID
gamename 游戏名称
device_type 平台 
pic1 图标
typeword 游戏类型
gamesize 游戏大小
fuli 福利标签 
     updatetime 上架时间
discount 折扣 10 就是10折
box_content 游戏介绍
excerpt 福利简介
fanli 返利介绍
Vip  vip介绍
Url 下载地址
Welfare 一句话简介
Apkname 安卓包名
Ios_apkname ios包名





游戏类型版本平台接口

接口地址：
{{host}}/v2/?cpsId={{cpsId}}

请求参数说明：
{{host}} 接口域名 固定值 https://www.steamsy.com
{{cpsId}} 你的渠道账号(推广账号)

返回参数说明：
Type里是游戏分类
Edition里是游戏类型
Deivce_type里 游戏版本

{
  "type": [
    {
      "id": "",
      "name": "全部"
    },
    {
      "id": "23",
      "name": "休闲"
    },
    {
      "id": "22",
      "name": "加速"
    },
    {
      "id": "1",
      "name": "三国"
    },
    {
      "id": "2",
      "name": "回合"
    },
    {
      "id": "3",
      "name": "传奇"
    },
    {
      "id": "4",
      "name": "卡牌"
    },
    {
      "id": "5",
      "name": "策略"
    },
    {
      "id": "6",
      "name": "魔幻"
    },
    {
      "id": "7",
      "name": "放置"
    },
    {
      "id": "8",
      "name": "动漫"
    },
    {
      "id": "16",
      "name": "武侠"
    },
    {
      "id": "15",
      "name": "开箱子"
    },
    {
      "id": "14",
      "name": "仙侠"
    },
    {
      "id": "13",
      "name": "动作"
    }
  ],
  "edition": [
    {
      "id": "",
      "name": "全部"
    },
    {
      "id": "0",
      "name": "官方"
    },
    {
      "id": "1",
      "name": "0.1折"
    },
    {
      "id": "2",
      "name": "BT游戏"
    }
  ],
  "device_type": [
    {
      "id": "",
      "name": "全部"
    },
    {
      "id": "0",
      "name": "Android"
    },
    {
      "id": "1",
      "name": "iOS"
    }
  ]
}

游戏详情接口
接口地址：
{{host}}/v3/?gid={{gid}}&cpsId={{cpsId}}

请求参数说明：
{{host}} 接口域名 固定值 https://www.steamsy.com
{{cpsId}} 你的渠道账号(推广账号)
{{gid}} 游戏id

返回参数说明：
A 为1成功
B 提示
C 数据

C 数据
id 游戏ID
gamename 游戏名称
device_type 平台 
pic1 图标
typeword 游戏类型
gamesize 游戏大小
fuli 福利标签 
     updatetime 上架时间
discount 折扣 10 就是10折
box_content 游戏介绍
excerpt 福利简介
fanli 返利介绍
Vip  vip介绍
Url 下载地址
Welfare 一句话简介
Apkname 安卓包名
Ios_apkname ios包名
photo五宣图



预约游戏列表接口
接口地址：
{{host}}/v4/?type={{type}}&cpsId={{cpsId}}&gamename={{gamename}}&edition={{edition}}&gametype={{gametype}}&pagecode={{pagecode}}&pagenum={{pagenum}}


请求参数说明：
{{host}} 接口域名 固定值 https://www.steamsy.com
{{cpsId}} 你的渠道账号(推广账号)
{{edition}} 游戏类型筛选：空 全部、 0官方、 1 0.1折 2BT （默认空）
{{type}} 平台 ：空 全部，0  安卓+双端游戏，1  苹果+双端游戏 （默认空）
{{gamename}} 搜索游戏名
{{gametype}} 游戏分类 默认空
{{pagecode}} 页码 默认1
{{pagenum}} 每页加载多少条



返回参数说明：
total_num 总条数
now_page 当前页数
total_page总页数

lists 数据列表
id 游戏ID
gamename 游戏名称
device_type 平台 
pic1 图标
typeword 游戏类型
gamesize 游戏大小
fuli 福利标签 
     updatetime 上架时间
discount 折扣 10 就是10折
box_content 游戏介绍
excerpt 福利简介
fanli 返利介绍
Vip  vip介绍
Url 下载地址
Welfare 一句话简介
Apkname 安卓包名
Ios_apkname ios包名



排行榜游戏列表接口
接口地址：
{{host}}/v5/?cpsId={{cpsId}}&edition={{edition}}&toptype={{toptype}}&diynum={{diynum}}&totalnum={{totalnum}}


请求参数说明：
{{host}} 接口域名 固定值 https://www.steamsy.com
{{edition}} 游戏类型筛选：空 全部、 0官方、 1 0.1折 2BT （默认空）
{{toptype}} 排行分类 0注册排行  1充值排行 
{{diynum}} 天数分类 1天  7天 30天
{{totalnum}} 调用条数 最多50条

返回参数说明：
lists 数据列表
id 游戏ID
gamename 游戏名称
device_type 平台 
pic1 图标
typeword 游戏类型
gamesize 游戏大小
fuli 福利标签 
     updatetime 上架时间
discount 折扣 10 就是10折
box_content 游戏介绍
excerpt 福利简介
fanli 返利介绍
Vip  vip介绍
Url 下载地址
Welfare 一句话简介
Apkname 安卓包名
Ios_apkname ios包名










游戏礼包列表接口
接口地址：
{{host}}/v6/?gid={{gid}}&cpsId={{cpsId}}&pagecode={{pagecode}}
请求参数说明：
{{host}} 接口域名 固定值 https://www.steamsy.com
{{cpsId}} 你的渠道账号(推广账号)
{{gid}} 游戏ID
{{pagecode}} 页码 默认1
返回参数说明：
total_num 总条数
now_page 当前页数
total_page总页数

lists 数据列表
id 礼包ID
name 礼包名
start_time 开始时间
end_time 结束时间
card 礼包码
part_num 剩余数