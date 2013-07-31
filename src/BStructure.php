<?php
namespace velosipedist\Structure;

class BStructure extends Structure{
	const TTL = 3600;

	public function loadConfig() {
//		return parent::loadConfig();
        //todo fix empty config caching
        $cache = new \CPHPCache();
		$cacheFile = $_SERVER['DOCUMENT_ROOT']."/bitrix/cache/".$cache->GetPath(__CLASS__);
		// проверяем, обновлялся ли конфиг
        $cacheWritten = filemtime($cacheFile);
        $configWritten = filemtime($this->configFile);

		// устаревший кеш или неудачно начатый кеш перезаписываем
		if(($configWritten>$cacheWritten) || !$cache->InitCache(self::TTL, __CLASS__, '/')){
			$cache->Clean(__CLASS__, '/');
            try {
                parent::loadConfig();
                if ($cache->StartDataCache(self::TTL, __CLASS__, '/')) {
                    $cache->EndDataCache(array('config' => $this->config));
                }else{
                    _log('Caching failed','widgets');
                }
            } catch (Exception $e) {
                _log('loading config error: '.$e->getMessage(),'widgets');
            }
		}else{
			$vars = $cache->GetVars();
			$this->config = $vars['config'];
		}
	}

}
