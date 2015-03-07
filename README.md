# Crude - A simple database toolkit for PHP

一个简单的PHP数据库操作工具类。

目前处于内测阶段。(The project is in Alpha for now.)

## 安装 (Installing)

```php
require '/path/to/crude.php';
```

## 数据库配置与初始化 (Configuration & Initial)

```php
$configs = [
    'dsn' => 'mysql:dbname=dbname;host=127.0.0.1',
    'username' => 'username',
    'password' => 'password',
    'options' => [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
    ]
];

$crude = new Crude($configs);
```

## API

### column()

```php
$result = $crude->column('select count(*) count from meta');
```

>Output:
>4

### pairs()

```php
$result = $crude->pairs('select name,value from meta where name=? or name=?', ['account', 'password']);
```

>Output:
>```
Array
(
    [account] => root
    [password] => 202cb962ac59075b964b07152d234b70
)
```

### columns()

```php
$result = $crude->columns('select name from meta');
```

>Output:
```
Array
(
    [0] => account
    [1] => password
    [2] => code
    [3] => post
)
```

### named_columns()

```php
$result = $crude->named_columns('select name,value from meta where name=? or name=?', ['account', 'password']);
```

>Output:
```
Array
(
    [name] => Array
    (
        [0] => account
        [1] => password
    )
    [value] => Array
    (
        [0] => root
        [1] => 202cb962ac59075b964b07152d234b70
    )
)
```

### row()

```php
$result = $crude->row('select value from meta where name=?', ['password']);
```

>Output:
```
Array
(
    [0] => 202cb962ac59075b964b07152d234b70
)
```

### rows()

```php
$result = $crude->rows('select name,value from meta');
```

>Output:
```
Array
(
    [0] => Array
    (
        [0] => account
        [1] => root
    )
    [1] => Array
    (
        [0] => password
        [1] => 202cb962ac59075b964b07152d234b70
    )
)
```

### map()

```php
$result = $crude->map('select name,value from meta where name=?', ['password']);
```

>Output:
```
Array
(
    [name] => password
    [value] => 202cb962ac59075b964b07152d234b70
)
```

### maps()

```php
$result = $crude->maps('select name,value from meta');
```

>Output:
```
Array
(
    [0] => Array
    (
        [name] => account
        [value] => root
    )
    [1] => Array
    (
        [name] => password
        [value] => 202cb962ac59075b964b07152d234b70
    )
)
```

### key_maps()

```php
$result = $crude->key_maps('select name,value from meta');
```

>Output:
```
Array
(
    [account] => Array
    (
        [name] => account
        [value] => root
    )
    [password] => Array
    (
        [name] => password
        [value] => 202cb962ac59075b964b07152d234b70
    )
)
```

### key_rows()

```php
$result = $crude->key_rows('select name,value from meta');
```

>Output:
```
Array
(
    [account] => Array
    (
        [0] => account
        [1] => root
    )
    [password] => Array
    (
        [0] => password
        [1] => 202cb962ac59075b964b07152d234b70
    )
)
```

### keys_maps()

```php
$sql = '
    select year,
           month, 
           count(*) count 
     from post 
    group by year,
             month 
    order by year desc,
             month asc';

$result = $crude->keys_maps($sql);
```

>Output:
```
Array
(
    [2014] => Array
    (
        [0] => Array
        (
            [year] => 2014
            [month] => 1
            [count] => 1
        )
        [1] => Array
        (
            [year] => 2014
            [month] => 2
            [count] => 2
        )
    )
    [2013] => Array
    (
        [0] => Array
        (
            [year] => 2013
            [month] => 2
            [count] => 1
        )
    )
)
```

### keys_rows()

```php
$sql = '
    select year,
           month, 
           count(*) count 
     from post 
    group by year,
             month 
    order by year desc,
             month asc';

$result = $crude->keys_rows($sql);
```

>Output:
```
Array
(
    [2014] => Array
    (
        [0] => Array
        (
            [0] => 2014
            [1] => 1
            [2] => 1
        )
        [1] => Array
        (
            [0] => 2014
            [1] => 2
            [2] => 2
        )
    )
    [2013] => Array
    (
        [0] => Array
        (
            [0] => 2013
            [1] => 2
            [2] => 1
        )
    )
)
```

## License

The MIT License (MIT)

Copyright (c) 2015 Belin Wu