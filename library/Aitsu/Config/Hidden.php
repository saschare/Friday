<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 */

/**
 * @deprecated 2.1.0 - 28.01.2011
 * Please use class Aitsu_Content_Config_Hidden instead.
 */
class Aitsu_Config_Hidden extends Aitsu_Content_Config_Abstract {

	public function getTemplate() {
		return '';
	}

	public static function set($index, $name, $value, $type = 'serialized') {

		if (Aitsu_Registry :: isEdit() && $value !== null) {
			Aitsu_Core_Article_Property :: factory()->setValue('ModuleConfig_' . $index, $name, $value, $type);
		}

		if (empty ($value)) {
			return Aitsu_Core_Article_Property :: factory()->getValue('ModuleConfig_' . $index, $name)->value;
		}

		return $value;
	}
}
?>