#controlRoom

每次部署进 linux 并把文件权限打开之后输入网址 http:ip/controlRoom/index.php(一定要有 index.php 不然只能打开登录页无法跳转)

关键词：单页应用、tp5+Layui、Vue+elementUI、功能高度可设计、RBAC 中的 RBAC0 权限管理设计

1.岗位管理仅在下一次导入值班表的时候生效，如果即时生效将会影响历史班表，历史班表不可修改 
2.【岗位类型】：共五大类，不可修改，岗位类型，分别为值班长、大网、传输支撑、移动云、移动云支撑，可对【岗位名称】和人员进行分类
不考虑开放岗位类别管理功能的原因：
A.岗位类型基本上不会有大的变化，开放该功能显得功能复杂；
B.由于岗位类型与岗位名称、人员具有多对多的关联关系，不仅要考虑三者关系还要考虑多对多的关联关系，开发成本太高，效益太低，不值得

3.用户信息：{$adminInfo}

4.角色说明
A.管理员（既监控组组长）：权限包含：【用户模块—人员信息库】、【值班表管理—岗位管理】、【值班表管理—值班表管理·权】以及【值班表管理—每日班表】
B.值班长：权限包含：【值班表管理—值班表管理·权】以及【值班表管理—每日班表】
C.值班组：权限包含：【值班表管理—值班表管理】

5.账号信息与人员信息库不是一一对应的，如果需要添加值班人员，需要将账号与值班同事进行关联

6.如何开通值班人员账号？分为两步：
第一步：添加值班人员个人信息：人员管理-人员信息库管理-新增人员 必填项：姓名、性别、是否值班人员（是）以及岗位类别（可多选）
第二步：开通值班人员账号：人员管理-账号管理-新增账号（点击表格'+'号） 必填项：账号（任意取）、角色（值班组）、密码、关联监控人员（选择刚刚添加的值班人员）

7.个人中心目前仅开放密码与账号修改，不必做的太复杂

8.【值班表管理模块】
动态模块：核心算法实现——“仿 RBAC 基于角色访问控制的动态模块设计” + “历史数据固化管理设计”
值班同事换班时，如果替换了值班长岗位那么对应的 ITMC 也会自动替换

注：配置 lamp 环境的时候需要配置 apache 的虚拟主机，网上似乎很多这方面的教程，详见我的配置文档

20200302-20200306 配置服务器踩坑总结
·配置 lnmp 环境的时候，发现登录页可以打开，但是点击登录的时候无法进行路由转发，导致网页显示‘file is not found’；
·随后采用方案配置 lamp，发现相同问题，后来才知道 lamp 中的 apache 没有配置虚拟机，同时网上并没有虚拟机的相关配置说明
·接着登录之后网页报错，意思是数据库日期数据不可以为 0，查找根源发现是服务器的数据库默认采用严格模式，于是将数据库格式做了修改

9.【值班信息模块】的一些说明
·动态模块：核心算法实现——“仿 RBAC 基于角色访问控制的动态模块设计” + “历史数据 JSON 字符串格式固化管理设计” + 组合搜索算法模型
·模块设计：记在值班表的数据要求三级字段有对应的值才会保存在 rotasituationrecordmanagementdata 表的 ctrr_rsrmD_data 字段中，这样以后不管对三级字段如何增减，都不用考虑历史数据，同时因为这个特点，在搜索、新增、编辑数据的时候，都需要按照这个设计
·字段混管模式：字段动态管理 + 字段传统管理（特殊字段直接采用传统模式） 字段混合管理模式
未来隐患：所有数据都承载在 rotasituationrecordmanagementdata 表格上，未来表格数据量增大时要提前面对搜索速度慢的问题，可以想到两个解决方案：1.可采用默认检索最近半年数据 2.学习更高级的知识解决这个问题

注：值得说明的是，既然已经使用了动态字段设计，那么后续无论如何都要按照该设计进行动态开发，一条走到底，除非迫不得已否则不要每次就想着重构

10【结束时间】字段十分重要：所有待跟进数据都靠此字段，因此不可轻易修改
目前为止，用到该字段的功能包括【生成大值班总结】、【加载值班信息结构待跟进数据】、【交接班所有引用类型的工作项】

11【交接班模块】
动态模块：核心算法实现——“仿 RBAC 基于角色访问控制的动态模块设计” + “交接班数据森林结构数据格式固化管理设计” + websocket 全双工通信
TP5 安装 workerman 版本并使用：composer require workerman/workerman
[websocket]
开启监听： php workermanTest.php start
关闭监听： php workermanTest.php stop
端口：36276
类型互斥原则，同一父级下只能为单一的引用类型/基本类型

12【人员评价模块】
只能对当班次的其他值班人员进行评价，其他情况不允许评价
每个人只能查看当前未完成的交接班的自己对他人的评价

13【版本说明】
php5.4 及以下版本不兼容，往上版本未知，
测试正常使用过的版本为：
windows 中 v7.1.8
linux 中 v7.0.33
测试无法使用的版本
linux 中 v7.4

xlsx.core.min.js 使用参考
官网：https://sheetjs.com/
某博主：
https://www.cnblogs.com/svipch/p/12102546.html（写得好）
http://www.manongjc.com/detail/21-ctzdmnfmyhojeiz.html（直接用）
