<?php

class tx_identity_tcemain_hook {
	public function processDatamap_afterAllOperations($parent) {
		$uuidRegistry = t3lib_div::makeInstance('Tx_Uuid_Registry');
//		$uuidRegistry->rebuild();
		$uuidRegistry->commit();
	}
}
?>