реализовано два rest api post метода, часть 1 и 2 задания соответственно:

```/api/upload-users.php``` - для загрузки csv файла с пользователями

```/api/make-mailing.php -d "mailing_id=1" -d "mailing_name=ne2026 ozon 2025-10-09"``` - для старта рассылки. парамтеры - id рассылки из таблицы mailing_list, имя рассылки (произвольное, является ключом сессии-рассылки.)

запустить дважды одну и ту же рассылку не получится, стоит проверка состояния.

запрос возвращает ответ сразу же, запускает рассылку в фоне.

как тестить:

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
    
создаем таблицы, соблюдая порядок создания
```mysql
CREATE TABLE users_for_mailing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    number INT,
    name VARCHAR(255)
);

CREATE TABLE mailing_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mailing_name VARCHAR(255),
    mailing_text TEXT
);

CREATE TABLE mailing_session (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mailing_id INT,
    name VARCHAR(255) UNIQUE,
    status ENUM('not started', 'processing', 'finished') NOT NULL DEFAULT 'not started',
    FOREIGN KEY (mailing_id) REFERENCES mailing_list(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

CREATE TABLE mailing_session_users (
    user_id INT,
    session_id INT,
    FOREIGN KEY (user_id) REFERENCES users_for_mailing(id),
    FOREIGN KEY (session_id) REFERENCES mailing_session(id)
);
```
далее можно тестировать, например так:

```bash
# проверка загрузки пользователей:
cd test
curl -X POST http://localhost:8080/api/upload-users.php -F "file=@users.csv"
# проверка рассылки: (предварительно следует создать рассылку с id=1 в таблице mailing_list)
curl -X POST http://localhost:8080/api/make-mailing.php      -d "mailing_id=1"      -d "mailing_name=ne2026 ozon 2025-10-09"
```
