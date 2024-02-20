# PhpConsole

### PhpConsole MODx Revolution 3. 
### Author: Vgrish <vgrish@gmail.com>
### [The author of the idea is Nikolai Lanets @Fi1osof](https://github.com/MODX-Club/modx-console)


#### Инструкция
Компонент позволяет выполнить php код в панели управления сайта. 
Доступ к консоли возможен sudo пользователям, либо пользователям с разрешением **phpconsole**.

[![](https://file.modx.pro/files/3/0/7/3074ec73e150388c2614d7f8480a1730s.jpg)](https://file.modx.pro/files/3/0/7/3074ec73e150388c2614d7f8480a1730.png)

Пример получения и вывода пользователя
```
<?php

if ($user = $modx->getObject(modUser::class, ['sudo' => 1])) {
    print_r($user->toArray()); // add info to result
    $modx->log(1, print_r($user->toArray() ,1)); // add info to log
}
```

На вкладке **Результат** будет выведен ассоциативный массив объекта **modUser**
```
Array
(
    [id] => 1
    [username] => s33228
    [password] => $2y$10$LrbNqj8iH9zO8XrDTp.6h/j.zBiItcQBOHr/XhnlvVm
    [cachepwd] => 
    [class_key] => MODX\Revolution\modUser
    [active] => 1
    [remote_key] => 
    [remote_data] => 
    [hash_class] => MODX\Revolution\Hashing\modNative
    [salt] => 
    [primary_group] => 1
    [session_stale] => Array
        (
            [0] => mgr
            [1] => web
        )

    [sudo] => 1
    [createdon] => 2024-02-19 09:40:45
)
```

Компонент поддерживает инициализацию повторного выполнения кода, необходимо лишь задать переменную **$REEXECUTE**
```
if ($_SESSION['idx'] < 10) {
    echo 'idx: '. $_SESSION['idx'];
    $_SESSION['idx']++;
    
    $REEXECUTE = true; // set flag repeat request
}
else {
    echo 'idx: '. $_SESSION['idx'];
}
```

Доступна загрузка кода из списка файлов простым перетаскиванием необходимого файла на область редактирования.


#### Licence
```
The module code is licensed under the MIT License.
See the LICENSE file distributed with this work for additional
information regarding copyright ownership.
Withal, the license does not cover the assets, like built 
packages and other derivatives. Spreading such assets is prohibitted 
without prior written authorization.
```
