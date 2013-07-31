<?php
namespace velosipedist\Structure;
/**
 * Класс-конфигуратор, обрабатывающий путь в формате /anyval/node2/foobar/
 * И собирающий хеш-конфигурацию в соответствии с этим путем
 */
class Structure {
	/* @var array $config */
	protected $config;
	protected $configFile;
	protected $fieldsMap = array();
	protected $fields = array();

	// операции по обработке значения параметра
	const REPLACE = 0;		// заменяем данным значением
	const APPEND = 10;		// объединяем с тем, что уже есть - приклеиваем в хвост
	const PREPEND = 15;		// объединяем с тем, что уже есть - в начало
	const REMOVE = 20;	// удаляем указанные значения
	const CLEAR = 30;	// удаляем все значения

	// порядок операций соблюдается при парсинге
	//todo прикинуть, каковы приоритеты этих операций
	private $operationSigns = array(
		''=>self::REPLACE,
		'+'=>self::APPEND,
		'++'=>self::PREPEND,
		'-'=>self::REMOVE,
	);
	protected $path;
	protected $defaultType = 'string';
	//todo ввести forceType

	function __construct($configFile){
		$this->configFile = $configFile;
		$this->loadConfig();
		$this->init();
	}

	/**
	 * Наполняет поля начальными значениями, только с 1го уровня
	 * @return void
	 */
	public function init() {
		$this->applyConfig();
	}

	/**
	 * Текущее состояние в виде массива
	 * @return array
	 */
	public function toArray() {
		$ret = array();
		foreach($this->fieldsMap as $name=>$type){
			$value = $this->getField($name);
			if($value !== null)
				$ret[$name] = $value;
		}
		return $ret;
	}

	public function toYaml() {
		return $this->yaml->dump($this->toArray(), 4, 80);
	}

	/**
	 * Массив загруженной конфигурации
	 * @return array|mixed
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * Обработка адреса с занесением результатов в соотв. поля
	 * @param $path
	 * @return void
	 */
	public function parsePath($path, $config = false) {
		$path = trim($path, '/');

		//todo поддержка любых маршрутов, типа /path/to/one/dir + тесты заранее
		//todo ? поддержка RegExp, типа #path/any(.+)#
		$starConfig = array();

		// исследуем текущий узел
		if($config===false){
//			$this->fields = array();
			$config = $this->config;
			$this->path = $path;
		}

		// в корне не используем вложенные конфиги,всё просто
		if($path == ''){
			$this->applyConfig($config);
		}else{
			// ищем дефолты для всего следующего уровня
			if(isset($config['/*'])){
				$starConfig = $config['/*'];
			}

			// готовимся к обходу вложенных конфигов
			$pathParts = explode('/', $path);
			$currentPathKey = '/'.array_shift($pathParts);

			// суммируем общие конфиги с конкретным для данного пути
			if(isset($config[$currentPathKey])){
				//todo logging for non-necessary duplicate entries
				// if we have already {some-value} assigned for now, report about {some-value} reassign
				$currentConfig = $this->mergeConfig($starConfig, $config[$currentPathKey]);
			}else{
				$currentConfig = $starConfig;
			}
			if($currentConfig){
				$this->applyConfig($currentConfig);

				// если есть вложенный путь - опускаемся в него
				if(count($pathParts)){
					$nextPath = implode('/', $pathParts);
					$this->parsePath($nextPath, $currentConfig);
				}
			}
		}

	}

	/**
     * Override settings from srcConfig with addConfig
     * @param $srcConfig array Where to write
     * @param $addConfig array Overriding values
     * @return array
     */
    private function mergeConfig($srcConfig, $addConfig) {
		$ret = array();
		$configs = array_filter($addConfig, array($this, 'isWidgetConfig'));
		if(!$configs){
            // if no sublevel configs - just mergign with current level
            // it will just copy "disabling" empty values from addConfig to src
			$ret = CMap::mergeArray(
				$srcConfig,
				$addConfig
			);
		}else{
            // if there is configs in some slots, mergin' each slot separately
			foreach($configs as $slot=>$slotConfig){
				$ret[$slot] = CMap::mergeArray($srcConfig[$slot], $slotConfig);
			}
            // source non-configs
			$othersSrc = array_diff_key($srcConfig, $configs);
            // add non-configs
            $othersAdd = array_diff_key($addConfig, $configs);

            // mixing other slots and apapending to config
            $others = CMap::mergeArray($othersSrc, $othersAdd);
            $ret = CMap::mergeArray(
				$ret,
				$others
			);
		}
		return $ret;
	}

	/**
	 * Все, полученные из конфига, слоты парами ключ-значение
	 * @return array
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * @param array $config
	 */
	public function setConfig($config)
	{
		$this->config = $config;
	}

	/**
     * Finds out if is passed array config array
     * @param $item array
     * @return bool
     */
    private function isWidgetConfig($item) {
		$ret = false;
//        dump('checking conf array...', $item);
        if(is_array($item)){
			// if it is 2 elements list - it is config
			$list = array_filter(array_keys($item), 'is_numeric');
			$ret = (count($item) == count($list)) && (count($item) >1);
		}
//        dump('It is '.($ret ? 'conf':'not'),$ret);
		return $ret;
	}

	/**
	 * Производит разбор данного конфига, на 1 уровне,
	 * после чего переназначает поля
	 * @param bool $config
	 * @return void
	 */
	protected function applyConfig($config=false) {
		if($config===false){
			$config = $this->config;
			// в начале конфигурации заполняем конфиг дополнительными полями, указанными в корне
			// типы определяются автоматически
			foreach($config as $key=>$val){
				if(!preg_match('#^/#', $key)){
					if(!isset($this->fieldsMap[$key])){
						$type = $this->defaultType;
						switch(gettype($val)){
							case 'integer':
							case 'double':
							case 'string':
								$type = 'string';
								break;
							case 'array':
							case 'object':
								$type = 'array';
								break;
						}
						$this->fieldsMap[$key] = $type;
						$this->fields[$key] = $val;
					}
				}
			}
		}
		foreach($this->fieldsMap as $name=>$type){
			$this->applyParam($config, $name);
		}
	}

	/**
	 * Применяет параметр из конфига к текущему состоянию,
	 * прорабатывая все операции
	 * @throws Exception
	 * @param $config
	 * @param $name
	 * @return void
	 */
	protected function applyParam($config, $name) {
		// перебираем операции
		foreach($this->operationSigns as $sign=>$operation){
			if(isset($config[$sign.$name])){
				$this->applyOperation($operation, $name, $config[$sign.$name]);
			}
		}
	}

	/**
	 * Применяем очередную операцию к указанному полю, используя указанное значение
	 * @param $operation
	 * @param $name
	 * @param $value
	 * @return void
	 */
	protected function applyOperation($operation, $name, $value) {
		$type = $this->fieldsMap[$name];
		$oldValue = $this->getField($name);
		$value = $this->normalizeValue($value, $type);
		switch($operation){
			case self::REPLACE:
				if(!$value){
					$value = null;
				}
				break;
			case self::APPEND:
				switch($type){
					case 'string':
						if($oldValue !== null){
							$value = $oldValue . $value;
						}
						break;
					case 'array':
						$value = array_merge($oldValue, $value);
						break;
				}
				break;
			case self::PREPEND:
				switch($type){
					case 'string':
						if($oldValue !== null){
							$value = $value . $oldValue ;
						}
						break;
					case 'array':
						$value = array_merge($value, $oldValue);
						break;
				}
				break;
			case self::REMOVE:
				if(!$value){
					$value = null;
				}else{
					switch($type){
						case 'string':
							$value = str_replace($oldValue, '', $value);
							break;
						case 'array':
							foreach($value as $valRow){
								$ind = array_search($valRow, $oldValue);
								unset($oldValue[$ind]);
							}
							$value = $oldValue;
							break;
					}
				}
				break;
		}
		$this->setField($name, $value);
	}

	/**
	 * Приводим переданное значение к пригодному для обработки виду
	 * @param $value
	 * @param $type
	 * @return array|string
	 */
	protected function normalizeValue($value, $type) {
		switch($type){
			case 'string':
				$value = (string) $value;
				break;
			case 'array':
				if(gettype($value) == 'string'){
					if(trim($value) == ''){
						$value = array();
					}else{
						// TODO настраивать разделитель
						$arValue = explode(',', $value);
						if(empty($arValue)){
							$value = array($value);
						}
						$value = array_map('trim', $arValue);
					}
				}
				break;
		}
		return $value;
	}

	public function getField($name){
		return $this->fields[$name];
	}
	public function setField($name, $value){
		$this->fields[$name] = $value;
	}

	public function flushConfig(){
		$this->config = null;
	}

	public function loadConfig() {
		//todo remove dependency
		$ext = CFileHelper::getExtension($this->configFile);
		switch($ext){
			case 'php':
				$this->config = require $this->configFile;
				break;
			case 'yml':
			case 'yaml':
				$yaml = new Spyc();
				$result = $yaml->loadFile($this->configFile);
				$this->config = is_array($result) ? $result : array();
				break;
		}
		if(!is_array($this->config)){
			throw new Exception('Config error:it\'s not array');
		}
	}
}
