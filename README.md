# Yii2-mega-kassa
Yii2 megakassa extension.

[![Latest Stable Version](https://poser.pugx.org/churakovmike/yii2-megakassa/v/stable)](https://packagist.org/packages/churakovmike/yii2-megakassa)
[![License](https://poser.pugx.org/churakovmike/yii2-megakassa/license)](https://packagist.org/packages/churakovmike/yii2-megakassa)

## Установка
Установить расширение можно через композер командой
```php
composer require churakovmike/yii2-megakassa
```
Официальная документация с доступными методами и примерами ответа доступна на сайте платежной системы [https://megakassa.ru/api/!](https://megakassa.ru/api/)
## Конфигурация компонента
В main-local.php добавьте следующие строки
```php
'components' => [
    'megakassaComponent' => [
        'class' => \ChurakovMike\Megakassa\MegaKassaComponent::class,
        'shopId' => XXXXXXX,
        'secretKey' => 'YYYYYYYYYY',
    ],
],
```
## Проверка отправителя колбэков
```php
public function behaviors()
{
    return [
        ChurakovMike\Megakassa\filters\MegakassaAccessFilter::class,
     ]
}
```
## Использование форм
Данная форма позволяет загружать и валидировать данные об успешной оплате.
```php
$form = new ChurakovMike\Megakassa\forms\SuccessCallbackForm();
$form->setAttributes(\Yii::$app->request->post());
$form->validate();
```
## Использование компоненты
Получение экземпляра компонента работает так же, как и получение любой другой компоненты Yii2.
```php
/** @var MegaKassaComponent $component */
$component = \Yii::$app->megaKassaComponent;
```

## Доступные методы
### Получение списка платежных систем
```php
$list = $component->getPaymentSystems();
```
### Проверка баланса
```php
$balance = $component->getBalance();
```
### Проведение выплаты
```php
$withdraw = $component->createWithdraw(
    $methodId,      // ID платежной системы и вылюты
    $amount,        // Сумма к оплате
    $amountDue,     // Сумма к получению
    $currencyFrom,  // RUR
    $wallet,        // Номер карты получателя
    $comment,       // Комментарий к выплате
    $debug          // 0 или 1
);
```
### Информация о выплате
```php
$withdrawDetail = $component->getWithdraw($withdrawId);
```
### Информация по выплатам

```php
$balance = $component->getWithdrawList($page);
```
