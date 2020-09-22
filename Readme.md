# Расширяем методы и возможности библиотеки Crest #
### Данный репозиторий является учебным и новые (и возможно старые) методы могут работать некорректно ###
* Оригинальная версия библиотеки лежит [тут](https://github.com/bitrix-tools/crest/blob/master/src/crest.php)
* Расширение включает:
	1. статический метод callBatchList - возвращает батч пакетом запрашиваемые данные
	CRestPlus::callBatchList('crm.deal.list', array('filter' => array('>DATE_CREATE' => '2020-01-01')));

	2. статический метод restCommand - соответствует методу call, но работает от токенов текущего пользователя
	CRestPlus::restCommand('crm.deal.list', array(), $auth);

	3. статический метод callCurrentUser - возвращает текущего пользователя
	CRestPlus::callCurrentUser($auth);

	4. статический метод init - установка приложения Auth2.0,
	принимает пост данные переданные порталом и в соответсвием с событием производит установку для 2 или 3 типа приложения
	вторым параметром принимает callback-функцию в которой можно произвести доп настройки приложения
	CRestPlus::init($_REQUEST, function () {
		CRestPlus::call('placement.bind', array(
			'PLACEMENT' => 'TASK_VIEW_TAB',
			'HANDLER'   => HANDLER
		));
	});