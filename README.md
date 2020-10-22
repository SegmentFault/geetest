# geetest
极验验证后端SDK

## 初始化SDK

```php
<?php
// 初始化SDK
$gt = new \Geetest\GeetestLib('极验验证提供的captchaID','极验验证提供的privateKey');

```

## 初始化SDK之后，准备验证的内容
```php
<?php
$param = [
    'user_id' => '1', // 网站用户ID，这里建议传session_id()
    'client_type' => 'web', #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
    'ip_address' => '127.0.0.1' # 请在此处传输用户请求验证时所携带的IP
];
```

## 返回初始验证内容
前端SDK会发起请求，后端调用准备验证接口，并把数据返回给前端：
```php
<?php
// 获取初始化验证数据
$response = $gt->preProcess($param);

// 返回给前端的数据，这里根据具体框架调用自行进行返回，此处为基础示例
echo json_encode($response);
```

## 对验证结果进行判定

前端进行交互验证成功后，把数据提交给后端进行验证，验证结果为`bool`类型，判断结果是否为true即可得知验证是否成功。
此处为简单示例

极验验证服务器正常时候调用正常验证：
```php
<?php
$available = $gt->successValidate($_POST['geetest_challenge'], $_POST['geetest_validate'], $_POST['geetest_seccode'], $data);
```

极验验证宕机情况下调用离线验证

```php
<?php
$available = $gt->failedValidate($_POST['geetest_challenge'],$_POST['geetest_validate'],$_POST['geetest_seccode']);
```

## 备注

判定的结果值重的$_POST类型为前端验证后拿到的结果，在进行登录或注册的时候同时提交到后端即可，后端拿到数据后进行统一验证。

前端验证接口请参考极验验证前端SDK文档。