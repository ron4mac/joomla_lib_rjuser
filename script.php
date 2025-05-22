<?php
/**
* @package		lib_rjuser
* @copyright	Copyright (C) 2015-2025 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		1.3.6
*/
defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerScript;

class lib_rjuserInstallerScript extends InstallerScript
{
	protected $minimumJoomla = '4.0';
	protected $com_name = 'lib_rjuser';
	protected $deleteFiles = ['libraries/rjuser/com.php'];

	public function install ($parent) 
	{
	}

	public function uninstall ($parent) 
	{
	}

	public function update ($parent) 
	{
	}

	public function preflight ($type, $parent) 
	{
	}

	public function postflight ($type, $parent) 
	{
		if ($type === 'update') {
			$this->removeFiles();
		}
		return true;
	}

}