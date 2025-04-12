# Лабораторная работа №8: Непрерывная интеграция с помощью Github Actions

## Цель работы
В рамках данной работы студенты научатся настраивать непрерывную интеграцию с помощью Github Actions.

## Задание
Создать Web приложение, написать тесты для него и настроить непрерывную интеграцию с помощью Github Actions на базе контейнеров.

## Выполнение

Создаём github репозиторий containers08

Внутри создаём директории ./site, ./site/modules, ./site/templates, ./site/styles, ./sql, ./tests, ./.github

![Alt text](/images/Снимок%20экрана%202025-04-12%20143219.png "image")

Внутри ./site создаём файлы index.php и config.php

index.php

```php
<?php

require_once __DIR__ . '/modules/database.php';
require_once __DIR__ . '/modules/page.php';

require_once __DIR__ . '/config.php';

$db = new Database($config["db"]["path"]);

$page = new Page(__DIR__ . '/templates/index.tpl');

// bad idea, not recommended
$pageId = $_GET['page'];

$data = $db->Read("page", $pageId);

echo $page->Render($data);
```

config.php
```php
<?php

$config = [
    'db_path' => __DIR__ . '/../test.sqlite'
];
```

Внутри ./sql создаём файл schema.sql
```sql
CREATE TABLE page (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    content TEXT
);

INSERT INTO page (title, content) VALUES ('Page 1', 'Content 1');
INSERT INTO page (title, content) VALUES ('Page 2', 'Content 2');
INSERT INTO page (title, content) VALUES ('Page 3', 'Content 3');
```

Внутрь ./tests добавляем тесты в файле tests.php и файл testframework.php

testframework.php
```php
<?php

function message($type, $message) {
    $time = date('Y-m-d H:i:s');
    echo "{$time} [{$type}] {$message}" . PHP_EOL;
}

function info($message) {
    message('INFO', $message);
}

function error($message) {
    message('ERROR', $message);
}

function assertExpression($expression, $pass = 'Pass', $fail = 'Fail'): bool {
    if ($expression) {
        info($pass);
        return true;
    }
    error($fail);
    return false;
}

class TestFramework {
    private $tests = [];
    private $success = 0;

    public function add($name, $test) {
        $this->tests[$name] = $test;
    }

    public function run() {
        foreach ($this->tests as $name => $test) {
            info("Running test {$name}");
            if ($test()) {
                $this->success++;
            }
            info("End test {$name}");
        }
    }

    public function getResult() {
        return "{$this->success} / " . count($this->tests);
    }
}
```

tests.php
```php
<?php

require_once __DIR__ . '/testframework.php';

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../modules/database.php';
require_once __DIR__ . '/../modules/page.php';

$testFramework = new TestFramework();

// test 1: check database connection
function testDbConnection() {
    global $config;
    // ...
}

// test 2: test count method
function testDbCount() {
    global $config;
    // ...
}

// test 3: test create method
function testDbCreate() {
    global $config;
    // ...
}

// test 4: test read method
function testDbRead() {
    global $config;
    // ...
}

// add tests
$tests->add('Database connection', 'testDbConnection');
$tests->add('table count', 'testDbCount');
$tests->add('data create', 'testDbCreate');
// ...

// run tests
$tests->run();

echo $tests->getResult();
```

Добавляем Dockerfile в корневую директорию
```Dockerfile
FROM php:7.4-fpm as base

RUN apt-get update && \
    apt-get install -y sqlite3 libsqlite3-dev && \
    docker-php-ext-install pdo_sqlite

VOLUME ["/var/www/db"]

COPY sql/schema.sql /var/www/db/schema.sql

RUN echo "prepare database" && \
    cat /var/www/db/schema.sql | sqlite3 /var/www/db/db.sqlite && \
    chmod 777 /var/www/db/db.sqlite && \
    rm -rf /var/www/db/schema.sql && \
    echo "database is ready"

COPY site /var/www/html
```

Внутрь ./.github/workflows добавляем файл main.yml
```yml
name: CI

on:
  push:
    branches:
      - main

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4
      - name: Build the Docker image
        run: docker build -t containers08 .
      - name: Create `container`
        run: docker create --name container --volume database:/var/www/db containers08
      - name: Copy tests to the container
        run: docker cp ./tests container:/var/www/html
      - name: Up the container
        run: docker start container
      - name: Run tests
        run: docker exec container php /var/www/html/tests/tests.php
      - name: Stop the container
        run: docker stop container
      - name: Remove the container
        run: docker rm container
```

Делаем коммит и заходим на гитхаб

![Alt text](/images/Снимок%20экрана%202025-04-12%20141712.png "image")

Исправляем все ошибки билда и тестов

![Alt text](/images/Снимок%20экрана%202025-04-12%20142552.png "image")

## Ответы на вопросы

### Что такое непрерывная интеграция?

    Процесс в разаботке програмного обеспечения при котором изменения в коде непрерывно автоматически собираются и тестируются

### Для чего нужны юнит-тесты? Как часто их нужно запускать?

    Юнит тесты - это код, проверяющий корректность работы отдельных модулей приложения в изоляции. Они позволяют обнаруживать ошибки в отдельных модулях прямо в процессе разработки. Юнит тесты жизненно необходимы на больших проектах с большим количеством модулей. 

### Что нужно изменить в файле .github/workflows/main.yml для того, чтобы тесты запускались при каждом создании запроса на слияние (Pull Request)?

В секции on: добавить pull_request: и указание веток

```yml
on: 
  pull_request:
    branches: 
        - main 
```

### Что нужно добавить в файл .github/workflows/main.yml для того, чтобы удалять созданные образы после выполнения тестов?

В секции name: добавить run: docker image prune -f (Для удаления всех образов)

```yml
- name: Удаление Docker-образа
  if: always()  
  run: docker image prune -f
```