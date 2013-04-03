<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 */

class Aitsu_Forms_Renderer_ExtJs {

	public static function render(Aitsu_Forms $form) {

		$out = 'new Ext.FormPanel({';

		$params = $form->getParams()->extjs->toArray();
		$params['title'] = $form->title;
		$params['url'] = $form->url;
		$params['id'] = $form->getUid();

		array_walk($params, array (
			'self',
			'_transform'
		));

		$out .= implode(', ', $params);

		$groups = $form->getGroups()->toArray();

		array_walk($groups, array (
			'self',
			'_transformGroups'
		));

		$out .= ', items: [' . implode(', ', $groups) . ']';

		$buttons = $form->getButtons()->toArray();

		array_walk($buttons, array (
			'self',
			'_transformButtons'
		), $form->getUid());

		$out .= ', buttons: [' . implode(', ', $buttons) . ']';

		$out .= '})';

		return $out;
	}

	protected static function _transform(& $value, $key) {

		$val = null;

		if ($value === true || $value == 'true') {
			$val = 'true';
		}
		elseif ($value === false || $value == 'false') {
			$val = 'false';
		}
		elseif (is_numeric($value)) {
			$val = $value;
		}
		elseif (is_array($value)) {
			array_walk($value, array (
				self,
				'_transform'
			));
			$val = '{' . implode(', ', $value) . '}';
		} else {
			$val = "'$value'";
		}

		$value = $key . ': ' . $val;
	}

	protected static function _transformGroups(& $value, $key) {

		$configs = array ();

		if (!empty ($value['type'])) {
			$configs[] = "xtype: '{$value['type']}'";
			$configs[] = "title: '" . Aitsu_Translate :: translate($value['legend']) . "'";

			if (isset ($value['extjs'])) {
				foreach ($value['extjs'] as $key => $val) {
					if (is_array($val)) {
						array_walk($val, array (
							'self',
							'_transform'
						));
						$configs[] = $key . ': {' . implode(', ', $val) . '}';
					} else {
						$configs[] = "$key: '$val'";
					}
				}
			}
		}

		if (!empty ($value['field'])) {
			$fields = $value['field'];

			array_walk($fields, array (
				'self',
				'_transformFields'
			));

			$configs[] = 'items: [' . implode(', ', $fields) . ']';

			$value = '{' . implode(', ', $configs) . '}';
		}
	}

	protected static function _transformFields(& $value, $key) {

		$configs = array ();

		$configs[] = "xtype: '{$value['type']}'";
		if (!empty ($value['label'])) {
			$configs[] = "fieldLabel: '" . Aitsu_Translate :: translate($value['label']) . "'";
		}
		$configs[] = "name: '{$key}'";

		if (method_exists('Aitsu_Forms_Renderer_ExtJs', '_extraFieldAtts' . ucfirst($value['type']))) {
			$configs = call_user_func(array (
				'self',
				'_extraFieldAtts' . ucfirst($value['type'])
			), $configs, $key, $value);
		}

		if (!empty ($value['value'])) {
			$fieldValue = str_replace(chr(39), chr(92) . chr(39), str_replace("\r", "", str_replace("\n", '\n', $value['value'])));
			$configs[] = "value: '{$fieldValue}'";
		}

		if (!empty ($value['option'])) {
			self :: _addOptions($configs, $value['type'], $value['option']);
		}

		if (isset ($value['extjs'])) {
			foreach ($value['extjs'] as $key => $val) {
				if (is_array($val)) {
					array_walk($val, array (
						self,
						'_transform'
					));
					$configs[] = $key . ': {' . implode(', ', $val) . '}';
				}
				elseif (is_numeric($val)) {
					$configs[] = "$key: $val";
				} else {
					$configs[] = "$key: '$val'";
				}
			}
		}

		$value = '{' . implode(', ', $configs) . '}';
	}

	protected static function _extraFieldAttsCombo($configs, $key, $field) {

		$configs[] = "hiddenName: '{$key}'";

		return $configs;
	}

	protected static function _extraFieldAttsRadiogroup($configs, $key, $field) {

		if (!isset ($field['extjs']['columns'])) {
			$configs[] = "columns: 2";
		}

		$items = array ();
		foreach ($field['option'] as $option) {
			$option = (object) $option;
			$value = is_numeric($option->value) ? $option->value : "'{$option->value}'";
			if (isset($field['value']) && $field['value'] == $option->value) {
				$items[] = "{boxLabel: '" . Aitsu_Translate :: translate($option->name) . "', name: '{$key}', inputValue: $value, checked: true}";
			} else {
				$items[] = "{boxLabel: '" . Aitsu_Translate :: translate($option->name) . "', name: '{$key}', inputValue: $value}";
			}
		}

		$configs[] = 'items: [' . implode(', ', $items) . ']';

		return $configs;
	}

	protected static function _extraFieldAttsCheckboxgroup($configs, $key, $field) {

		if (!isset ($field['extjs']['columns'])) {
			$configs[] = "columns: 2";
		}

		$counter = -1;
		$items = array ();
		foreach ($field['option'] as $option) {
			$counter++;
			$option = (object) $option;
			$value = is_numeric($option->value) ? $option->value : "'{$option->value}'";
			if (isset ($field['value']) && ($field['value'] == $option->value || (is_array($field['value']) && in_array($option->value, $field['value'])))) {
				$items[] = "{boxLabel: '" . Aitsu_Translate :: translate($option->name) . "', name: '{$key}[$counter]', inputValue: $value, checked: true}";
			} else {
				$items[] = "{boxLabel: '" . Aitsu_Translate :: translate($option->name) . "', name: '{$key}[$counter]', inputValue: $value}";
			}
		}

		$configs[] = 'items: [' . implode(', ', $items) . ']';

		return $configs;
	}

	protected static function _extraFieldAttsDatefield($configs, $key, $field) {

		$configs[] = "format: 'Y-m-d H:i:s'";
		$configs[] = "altFormats: 'Y-m-d|d.m.Y|d.m.y'";

		return $configs;
	}

	protected static function _transformButtons(& $value, $key, $uid) {

		$button = $value;

		if (isset ($button['text'])) {
			$value = "{tooltip: '{$value['text']}'";
		} else {
			$value = "{tooltip: ''";
		}

		if ($key == 'save') {
			$value .= ", iconCls: 'save'";
			if (isset ($button['handler'])) {
				$value .= ", handler: function() {{$button['handler']}('$uid');}";
			} else {
				$value .= ", handler: function() {formSubmit('$uid');}";
			}
		}
                
                if ($key == 'delete') {
			$value .= ", iconCls: 'delete'";
			if (isset ($button['handler'])) {
				$value .= ", handler: function() {{$button['handler']}('$uid');}";
			} else {
				$value .= ", handler: function() {formSubmit('$uid');}";
			}
		}

		$value .= '}';
	}

	protected static function _addOptions(& $target, $type, $options) {

		if (!method_exists('Aitsu_Forms_Renderer_ExtJs', '_addOptions' . ucfirst($type))) {
			/*
			 * If the specified xtype is not supported, we just ignore it.
			 */
			return;
		}

		$target[] = call_user_func(array (
			'self',
			'_addOptions' . ucfirst($type)
		), $options);
	}

	protected static function _addOptionsCombo($options) {

		$return = "mode: 'local', forceSelection: true, editable: false, typeAhead: false, triggerAction: 'all', ";
		$return .= "store: new Ext.data.ArrayStore({fields: ['dataValue','dataLabel'],";
		$option = array ();
		foreach ($options as $value) {
			$value = (object) $value;
			$option[] = "['{$value->value}', '" . Aitsu_Translate :: translate($value->name) . "']";
		}
		$return .= 'data: [' . implode(', ', $option) . ']}),';
		$return .= "valueField: 'dataValue', displayField: 'dataLabel'";

		return $return;
	}

}