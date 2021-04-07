<?php
// namespace libs\crest;
require (__DIR__.'/CRest.php');

class CRestPlus extends CRest {
	const CLIENT = __DIR__.'/settings.json';

	/**
	* Функция посчитывает количество необходимых сущностей на портале и создает массив с параметрами получения
	* всего списка этих сущностей
	*
	* @var str method - метод rest
	* @var arr params - массив, параметры для списочных методов (filter, select)
	* @return arr - массив с для batch запроса, разделенный по 50 пакетов 
	*/
	protected static function iteration ($method, $params) {
		$tmp = parent::call ($method, $params);
	    $iteration = intval($tmp['total'] / 50) + 1;
	    if ($tmp['total'] % 50 == 0) $iteration -= 1;
	    for ($i = 0; $i < $iteration; $i++) {
	        $start = $i * 50;
	        $data[$i]['method'] = $method;
	        $data[$i]['params'] = array('start' => $start);
	        $data[$i]['params'] += $params;
	    }
	    if (isset($data)) {
		    if (count($data) > 50) $data = array_chunk($data, 50);
		    else $data = array($data);
		} else { $data = false; }
	    return $data;
	}

	/**
	* Функция для списочных методов, получает весь список сущностей, соблюдая условия фильтра
	*
	* @var str method - списочный метод rest
	* @var arr params - параметры для списочных методов (filter, select)
	* @return arr - результат метода callbatch (список сущностей) или error
	*/
	public static function callBatchList($method, $params = [])
  {
    $tmp[] = self::iteration($method, $params);
    if (!empty($tmp)) {
      foreach ($tmp as $packageSt) {
        foreach ($packageSt as $packageNd) {
          $result[] = parent::callBatch($packageNd);
        }
      }
    } else {
      $result = false;
    }
    return $result ?: 'error';
  }

	/**
	* Функция для получения данных пользователей, принимает простой массив id ('1','2','3','n'),
	* подходит для случаев, когда нужно получить данные пользователей связанных с определенными событиями(лиды, сделки),
	* для получения пользователей (или пользователя) с определенными фильтрами или ограничениями лучше использовать callbatch
	*
	* @var arr params - массив id ('1','2','3','n')
	* @return arr - результат метода callbacth (данные пользователей)
	*/
	public static function callBatchUsers ($params) {
		$return = false;
		foreach ($params as $v) {
			$data[] = array('method' => 'user.get','params' => array('ID' => $v,));
		}
		$data = array_chunk($data, 50);
		for ($i = 0, $s = count($data); $i < $s; $i++) { $return[] = parent::callBatch ($data[$i]); }
		return $return ?: false;
	}

	/**
	* Метод выводит системную информацию о сущности битрикса Дело
	*
	* @return arr
	*/
	public static function aboutActivity () {
		$fields = parent::call ('crm.activity.fields', array())['result'];
		$direction = parent::call ('crm.enum.activitydirection', array())['result'];
		$ownerType = parent::call ('crm.enum.ownertype', array())['result'];
		$status = parent::call ('crm.enum.activitystatus', array())['result'];
		$type = parent::call ('crm.enum.activitytype', array())['result'];

		$return = array(
			'description' => 'Информация о сущности дело',
			'fields' => array( 'desc' => 'Поля', 'value' => $fields ),
			'direction' => array( 'desc' => 'Направления', 'value' => $direction ),
			'ownerType' => array( 'desc' => 'Тип владельца(сущности)', 'value' => $ownerType ),
			'status' => array( 'desc' => 'Статус', 'value' => $status ),
			'type' => array( 'desc' => 'Тип дела', 'value' => $type )
		);
		return $return ?: false;
	}

	/**
	* метод вызова методов рест апи битрикс на токенах обращающегося пользователя
	*
	* @var method (string) - метод рест апи битрикс
	* @var params (array) - праметры метода
	* @var auth (array) - аутентификация пользователя
	* @return (array)
	*/
	public static function restCommand ($method, $params = array(), $auth = array()) {
		$queryUrl = 'https://'.$auth['DOMAIN'].'/rest/'.$method;
		$queryData = http_build_query(array_merge($params, array('auth' => $auth['AUTH_ID'])));
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_POST           => 1,
			CURLOPT_HEADER         => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYPEER => 1,
			CURLOPT_URL            => $queryUrl,
			CURLOPT_POSTFIELDS     => $queryData
		));
		$result = curl_exec($curl);
		curl_close($curl);
		return json_decode($result, 1);
	}

	/**
	* метод получения информации о текущем пользователе и является ли он админом
	*
	* @var auth (array) - аутентификация пользователя
	* @return (array)
	*/
	public static function callCurrentUser ($auth) {
		$user = self::restCommand('user.current', array(), $auth);
		$admin = self::restCommand('user.admin', array(), $auth);
		return array('admin' => $admin['result'], 'user' => $user['result']);
	}

	/**
	* установка приложения
	*/
	public static function init ($event, $callback = null) {
		if (!file_exists(self::CLIENT)) {
			if (isset($event['event']) && $event['event'] == 'ONAPPINSTALL')
				parent::installApp();
			else if (isset($event['PLACEMENT']) && $event['PLACEMENT'] == 'DEFAULT')
				require_once __DIR__.'/install.php';

			if ($callback) $callback();
		}
	}
}
