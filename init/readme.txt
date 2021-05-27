## 初始化方法

1. 按install.txt配置环境。(可把web文件夹修改为apache2的默认文件夹。改完后记得把/etc/apache2/apache2.conf里的denied改成granted.)
2. 按Lab1-README.txt 建立数据库账户，创建数据库
3. 按sql/table.sql 建表
4. 复制以下copy语句把data/process里的数据导入数据库。(路径改成你的)

copy city from '/home/alphabet/db/12307-db-system/data/process/city.csv' with (format csv, delimiter ',');
copy train from '/home/alphabet/db/12307-db-system/data/process/train.csv' with (format csv, delimiter ',');
copy station from '/home/alphabet/db/12307-db-system/data/process/station.csv' with (format csv, delimiter ',');
copy stop from '/home/alphabet/db/12307-db-system/data/process/stop.csv' with (format csv, delimiter ',');
copy price from '/home/alphabet/db/12307-db-system/data/process/price.csv' with (format csv, delimiter ',');

5.在php/utils里把pg_connect里的用户名和密码和数据库名字改成你的。
6.先注册用户名为admin的即为管理员，先在管理页面开放购票，然后可以买票。