запускаем докер контейнер тестового задания

заходим в командную строку mysql
```shell
docker exec -it my_mysql mysql -u root -p
```
пароль: root_passw

переключаемся на базу
```mysql
USE test_creatmedia
```
    
создаем таблицу
```mysql
CREATE TABLE users_for_mailing (
    number INT,
    name VARCHAR(255)
);
```
далее можно тестировать, например так:

```bash
cd test
curl -X POST http://localhost:8080/api/upload-users.php -F "file=@users.csv"
```
