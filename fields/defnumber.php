<?php
/**
* @package		lib_rjuser
* @copyright	Copyright (C) 2022 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
*/
defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

class JFormFieldDefNumber extends Joomla\CMS\Form\Field\NumberField
{
	protected $type = 'DefNumber';

	protected function getInput ()
	{
		$compdef = $this->element['compdef'];
		$parts = explode('/', $compdef);
		if (isset($parts[1])) {
			$compdef = $this->compoptv($parts[1], (int)$parts[0]);
		}

		$html[] = '<input type="checkbox" id="'.$this->id.'_dchk" onclick="DEFNff.sDef(this)" '.($this->value ? '' : 'checked ').'style="vertical-align:initial" />';
		$html[] = '<label for="'.$this->id.'_dchk" style="display:inline;margin-right:1em">'.Text::_('JGLOBAL_USE_GLOBAL').'</label>';
		$html[] = '<span id="'.$this->id.'_spn" class="mydefn'.($this->value ? '' : ' hidden').'">';
		$html[] = parent::getInput();
		$html[] = '</span>';
		$html[] = '<input type="hidden" class="defn-valu" value="'.$compdef.'" />';
		$html[] = '<span class="defn-dflt'.($this->value ? ' hidden' : '').'">'.$compdef.'</span>';

		static $scripted;
		if (!$scripted) {
			$scripted = true;
			$jdoc = Factory::getDocument();
			$script = '
var DEFNff = (() => {
	return {
		sDef: (elm) => {
			let pare = elm.parentElement;
			let dflt = pare.querySelector(".defn-dflt");
			let numi = pare.querySelector(".mydefn input");
			let ngrp = pare.querySelector(".mydefn");
			let stor = pare.querySelector(".defn-valu");

			if (elm.checked) {
				ngrp.classList.add("hidden");
				dflt.classList.remove("hidden");
			//	let valu = stor.value;
				numi.value = 0;
			//	numi.value = valu;
			} else {
				let valu = stor.value;
				numi.value = valu;
				dflt.classList.add("hidden");
				ngrp.classList.remove("hidden");
			}
		}
	};
})();
';
			$jdoc->addScriptDeclaration($script);
			$style = [];
			$style[] = '.defn-dflt { opacity:0.5;padding-top:4px; }';
			$style[] = '.mydefn input { width:8em;display:inline;padding:.25rem .5rem; }';
			$jdoc->addStyleDeclaration(implode("\n", $style));
		}

		return implode("\n", $html);
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
