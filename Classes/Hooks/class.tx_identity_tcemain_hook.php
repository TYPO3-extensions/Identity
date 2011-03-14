<?php

class tx_identity_tcemain_hook {
	public function processDatamap_afterAllOperations($parent) {
		$identityMap = t3lib_div::makeInstance('Tx_Identity_Map');
//		$identityMap->rebuild();
		$identityMap->commit();
	}
}
?>