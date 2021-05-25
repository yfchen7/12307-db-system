1.建表语句在table.sql
2.按install.txt配置环境。可把web文件夹修改为apache2的默认文件夹。
3.在php/utils里把pg_connect里的用户名和密码和数据库名字改成你的。
4.通过tabletest.txt里的copy语句把data/process里的数据导入数据库。
5.先注册用户名为admin的为管理员，可在web页面放票后查询。