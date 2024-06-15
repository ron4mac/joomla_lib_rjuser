<?php
/**
* @package		lib_rjuser
* @copyright	Copyright (C) 2022-2024 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		1.3.1
*/
defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;

class JFormFieldGmkbValue extends Joomla\CMS\Form\FormField
{
	protected $type = 'GmkbValue';

	protected function getInput ()
	{
		$allowEdit = ((string) $this->element['edit'] == 'true') ? true : false;
		$allowClear = ((string) $this->element['clear'] != 'false') ? true : false;

		// create the component default display
		list($cdv,$cdm) = $this->num2gmkv($this->element['compdef']);
		$mc = ['KB','MB','GB'];
		$compdef = $cdv.$mc[$cdm];

		// class='required' for client side validation
		$class = '';

		if ($this->required) {
			$class = ' class="required modal-value"';
		}

		// turn the value into GMK and number
		list($uplsiz,$uplsizm) = $this->num2gmkv($this->value?:$this->element['compdef']);

		// Setup variables for display.
		$html	= [];

		$html[] = '<input type="checkbox" id="'.$this->id.'_dchk" onclick="GMKBff.sDef(this)" '.($this->value ? '' : 'checked ').'style="vertical-align:initial" />';
		$html[] = '<label for="'.$this->id.'_dchk" style="display:inline;margin-right:1em">'.Text::_('JGLOBAL_USE_GLOBAL').'</label>';

		$html[] = '<span class="input-gmkb'.($this->value ? '' : ' hidden').'">';
		$html[] = '<input type="number" step="1" min="1" class="input-medium" id="' . $this->id . '_name" value="' . $uplsiz .'" onchange="GMKBff.sVal(this.parentNode)" onkeyup="GMKBff.sVal(this.parentNode)" style="width:4em;text-align:right" />';
		$html[] = '<select id="' . $this->id . '_gmkb" class="gkmb-sel" onchange="GMKBff.sVal(this.parentNode)" style="width:5em">';
		$html[] = '<option value="1024"'.($uplsizm==0?' selected="selected"':'').'>KB</option>';
		$html[] = '<option value="1048576"'.($uplsizm==1?' selected="selected"':'').'>MB</option>';
		$html[] = '<option value="1073741824"'.($uplsizm==2?' selected="selected"':'').'>GB</option>';
		$html[] = '</select>';
		$html[] = '<input type="hidden" class="gmkb-valu" id="' . $this->id . '_id"' . $class . ' name="' . $this->name . '" value="' . $this->value . '" />';
		$html[] = '</span>';

		$html[] = '<span class="gmkb-dflt'.($this->value ? ' hidden invisible' : '').'">'.$compdef.'</span>';

		static $scripted;
		if (!$scripted) {
			$scripted = true;
			$jdoc = Factory::getDocument();
			$script = '
var GMKBff = (function() {
	return {
		sDef: function (elm) {
			let pel = elm.parentElement;
			if (elm.checked) {
				pel.querySelector(".input-gmkb").classList.add("hidden");
				pel.querySelector(".gmkb-dflt").classList.remove("hidden","invisible");
				pel.querySelector(".gmkb-valu").value = null;
			} else {
				pel.querySelector(".gmkb-dflt").classList.add("hidden","invisible");
				pel.querySelector(".input-gmkb").classList.remove("hidden");
				this.sVal(pel.querySelector(".input-gmkb"));
			}
		},
		sVal: function (elm) {
			let vel = elm.querySelector(".gmkb-valu");
			let numb = +elm.querySelector(".input-medium").value;
			let shft = +elm.querySelector(".gkmb-sel").value;
			vel.value = numb * shft;
		}
	};
})();
'		;
			$jdoc->addScriptDeclaration($script);
			$jdoc->addStyleDeclaration('.gmkb-dflt { opacity:0.5;display:inline-block;padding-top:4px }');
		}
		return implode("\n", $html);
	}

	public function filter ($value, $group = null, Registry $input = null)
	{
        // Get the field filter type.
        $filter = (string) $this->element['filter'];
        if (!$filter) return $value;
        if ($filter=='zeronull') {
			$v = (int)$value;
			if ($v==0) $v = null;//	var_dump([$v,$value,$filter]); jexit();
			return $v;
        }
        return $value;
	}

	private function num2gmkv ($num)
	{
		$parts = explode('/', $num);
		if (isset($parts[1])) {
			$num = $this->compoptv($parts[1], (int)$parts[0]);
		} else {
			$num = (int)$num;
		}

		$sizm = 0;
		if ($num) {
			if ($num % 1073741824 == 0) {
				$sizm = 2;
				$num = $num >> 30;
			} elseif ($num % 1048576 == 0) {
				$sizm = 1;
				$num = $num >> 20;
			} else {
				$num = $num >> 10;
			}
		} else {
			$num = '';
		}
		return [$num, $sizm];
	}

	// get a component option value
	private function compoptv ($opt, $def)
	{
		static $opts = null;
		if (!$opts) {
			$mlnk = $this->form->getField('link')->value;
			if (preg_match('#option=(.+)&#', $mlnk, $m)) {
				$opts = ComponentHelper::getParams($m[1]);
			} else {
				$opts = new Registry();
			}
		//	file_put_contents('COMPOPT.txt',print_r($opts,true));
		}
		$val = (int)$opts->get($opt);
		return $val ?: $def;
	}

}
