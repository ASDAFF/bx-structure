<?php
namespace velosipedist\Structure;

class InsetManager{
	const FOLDER_NAME = 'insets';
	public $curDir;
	/** @var WidgetStructure $widgetStructure */
	public $widgetStructure;
	function __construct($widgetConfigFile = false) {
		$curDir = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$this->curDir = $_SERVER['DOCUMENT_ROOT'].$curDir;
		if($widgetConfigFile){
			$this->widgetStructure = new WidgetStructure($widgetConfigFile);
			$this->widgetStructure->parsePath(trim($curDir, '/'));
			$this->widgetStructure->flushConfig();
		}
	}

	/**
	 * Подключение регионально-зависимого фрагмента для текущего региона.
	 * Если указать ид города,он будет использован вместо текущего региона.
	 * !!! slot=index Вызывать ТОЛЬКО в конце header.php шаблона !!!
	 * @param string $slot если index - заменяется страница целиком
	 * @param int $cityId
	 * @return void
	 */
	public function inset($slot = 'index'){
		// вычисляем, в какой папке мы находмися
		$curDir = rtrim($this->curDir, '/').'/';
		$commonDir = $curDir .'.'.static::FOLDER_NAME;
		if(is_dir($commonDir)){
			$incl = $commonDir .'/'.$slot.'.php';
			if(is_file($incl)){
				require $incl;
				// если это индексная страница - после включения заканчиваем работу
				if($slot=='index'){
					require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
					exit();
				}
			}
		}
	}

	public function widget($name){
		if($this->widgetStructure != null){
			return $this->widgetStructure->widget($name);
		}
	}

	/**
	 * Checks if there any widget in specified slots
	 * @param array|string $slots list of slots for check, or regexp
	 * @return bool
	 */
	public function hasWidgets($slots) {
		if(is_string($slots)){
			$slotsRegExp = strtr($slots,'/','\/');
			$existingSlots = $this->widgetStructure->getFields();
			foreach($existingSlots as $slot => $val) {
				$match = (int)preg_match('/' . $slotsRegExp . '/', $slot);
				if(empty($val) || (!$match)){
					continue;
				}
				return true;
			}
			return false;
		}
		foreach($slots as $slot){
			$val = $this->widgetStructure->getField($slot);
			if(!empty($val)){
				return true;
			}
		}
		return false;
	}

}