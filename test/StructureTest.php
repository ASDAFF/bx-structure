<?php

class StructureTest {
	private $structure, $yaml;
	function __construct($configFile){
		$this->structure = new Structure($configFile);
		$this->yaml = new Spyc();
	}



	public function testInit() {
		if(is_array($this->structure->toArray())){
			$this->passed(__METHOD__);
			$this->dumpYaml();
		}else{
			$this->failed(__METHOD__);
		}
	}

	public function dumpArray() {
		var_dump($this->structure->toArray());
	}
	public function dumpArrayConfig() {
		var_dump($this->structure->getConfig());
	}

	public function dumpYaml(){
		print '<pre>';
		print $this->yaml->dump($this->structure->toArray(), 4, 80);
		print '</pre>';
	}
	public function dumpYamlConfig(){
		print '<pre>';
		print $this->yaml->dump($this->structure->getConfig(), 4, 80);
		print '</pre>';
	}

	private function passed($methodName){
		printf(
			'<p style="color:green;border-top:1px dotted green;padding:10px 0 0;margin:0">%s</p>',
			$methodName
		);
	}
	private function failed($methodName){
		printf(
			'<p style="color:red;border-top:1px dotted red;padding:10px 0 0;margin:0">%s</p>',
			$methodName
		);
	}

	public function testPath($address){
		$this->structure->parsePath($address);
		$assert = $this->expect($address);
		if(is_array($assert)){
			$result = $this->structure->toArray();
			ksort($result);
			ksort($assert);
			if($result == $assert){
				$this->passed(__METHOD__);
				$this->log($address);
			}else{
				$this->failed(__METHOD__);
				$this->log($address);
				$this->log('Result:');
				var_dump($result);
				$this->log('Expected:');
				var_dump($assert);
				$this->log('Error:');
				var_dump(array_diff($result, $assert));
			}
		}
	}

	private function expect($address) {
		$parts = explode('/', $address);
		$config = $this->structure->getConfig();
		$item = $config[$parts[0]];
		array_shift($parts);
		foreach($parts as $part){
			$item = $item[$part];
		}
		return isset($item['@expected']) ? $item['@expected'] : false;
	}

	public function log($message){
		print '<div style="font:11px/13px verdana;color:#996600; position:relative; top:10px;">'.$message.'</div>';
	}


}
