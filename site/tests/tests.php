<?php

require_once __DIR__ . '/testframework.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../modules/database.php';
require_once __DIR__ . '/../modules/page.php';

$tests = new TestFramework();

$tests->add('Database connection', function() use ($config) {
    try {
        $db = new Database($config['db_path']);
        return assertExpression(true, 'Подключение успешно');
    } catch (Exception $e) {
        return assertExpression(false, '', $e->getMessage());
    }
});

$tests->add('Database table creation', function() use ($config) {
    $db = new Database($config['db_path']);
    $result = $db->Execute("CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, value TEXT)");
    return assertExpression($result !== false, 'Таблица создана');
});

$tests->add('Database Create', function() use ($config) {
    $db = new Database($config['db_path']);
    $id = $db->Create('test', ['name' => 'foo', 'value' => 'bar']);
    return assertExpression(is_numeric($id), 'Запись создана');
});

$tests->add('Database Read', function() use ($config) {
    $db = new Database($config['db_path']);
    $record = $db->Read('test', 1);
    return assertExpression($record && $record['name'] === 'foo', 'Чтение успешно');
});

$tests->add('Database Update', function() use ($config) {
    $db = new Database($config['db_path']);
    $success = $db->Update('test', 1, ['value' => 'baz']);
    $updated = $db->Read('test', 1);
    return assertExpression($success && $updated['value'] === 'baz', 'Обновление успешно');
});

$tests->add('Database Count', function() use ($config) {
    $db = new Database($config['db_path']);
    $count = $db->Count('test');
    return assertExpression($count >= 1, "Найдено $count записей");
});

$tests->add('Database Delete', function() use ($config) {
    $db = new Database($config['db_path']);
    $db->Delete('test', 1);
    $deleted = $db->Read('test', 1);
    return assertExpression($deleted === false || $deleted === null, 'Удаление успешно');
});

$tests->add('Page Render', function() {
    $template = __DIR__ . '/../templates/index.tpl';
    file_put_contents($template, "<h1>{{ title }}</h1><p>{{ content }}</p>");
    $page = new Page($template);

    ob_start();
    $page->Render(['title' => 'Test Title', 'content' => 'Hello']);
    $output = ob_get_clean();

    return assertExpression(
        strpos($output, 'Test Title') !== false && strpos($output, 'Hello') !== false,
        'Шаблон отрендерен корректно'
    );
});

$tests->run();
echo $tests->getResult() . PHP_EOL;

unlink($config['db_path']);
unlink(__DIR__ . '/../templates/index.tpl');
