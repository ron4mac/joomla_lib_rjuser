<?php
/**
* @package		lib_rjuser
* @copyright	Copyright (C) 2022-2024 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		1.3.1
*/
defined('JPATH_BASE') or die;

use Joomla\CMS\Language\Text;

class JFormFieldOptGroupList extends Joomla\CMS\Form\Field\UsergrouplistField
{
	protected $type = 'OptGroupList';

	// Just adds an option so NO usergroup can be selected
	protected function getOptions ()
	{
		$opts = parent::getOptions();
		array_unshift($opts, (object)array('text'=>Text::_('JSELECT'),'value'=>'','level'=>0));
		return $opts;
	}

}
