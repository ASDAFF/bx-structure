<?php
namespace velosipedist\Structure;
/**
 * Вывод виджетов в заданных местах согласно конфигу.
 * В отличие от врезок (.insets) - выводят одни и те же участки кода
 * в произвольных комбинациях, независимо от разметки самой страницы
 */
class WidgetStructure extends BStructure{

	protected function applyParam($config, $name) {
		$operation = self::REPLACE;
		if(isset($config[$name])){
			$this->applyOperation($operation, $name, $config[$name]);
		}
	}

    public function widget($name){
		$widget = $this->getField($name);
		if(is_array($widget) && !empty($widget)){
			$path = $widget[0];
			$config = array();
			if(isset($widget[1]) && is_array($widget[1])){
				$config = $widget[1];
			}
			inc_multiple('widgets/'.$path, $config);
		}
	}
}
