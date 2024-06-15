<?php
/**
* @package		lib_rjuser
* @copyright	Copyright (C) 2022-2024 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		1.3.1
*/

use Joomla\CMS\Factory;

abstract class RJUserCom
{
	protected static $siteMenu = null;

	public static function shout ()
	{
		echo 'READ ANY GOOD BOOKS LATELY?';
	}


	public static function getInstObject ($ityp, $mid=null)	// SO
	{
		$app = Factory::getApplication();
		if ($mid) {
			$params = $app->getMenu()->getItem($mid)->getParams();
			$menuid = $mid;
		} else {
			$params = $app->getParams();
			$menuid = $app->input->getInt('Itemid', 0);
		}
		if (!$menuid) throw new Exception('NOT ALLOWED: missing menu ID', 400);
		$user = $app->getIdentity();
		$uid = $user->get('id');
		$ugrps = $user->get('groups');
		$allperms = RJUserInstanceObject::CAN_CREA + RJUserInstanceObject::CAN_EDIT + RJUserInstanceObject::CAN_DELE;
		$path = '';
		$perms = 0;
		switch ($params->get($ityp)) {
			case 0:	//user
				if ($uid) $perms = $allperms;
				$path = '@'.$uid;
				break;
			case 1:	//group
				$auth = $params->get('group_auth');
				$path = '_'.$auth;
				if ($uid && in_array($auth, $ugrps)) $perms = $allperms;
				break;
			case 2:	//site
				$auth = $params->get('site_auth');
				$path = '_0';
				if ($uid && in_array($auth, $ugrps)) $perms = $allperms;
				break;
		}
		$obj = new RJUserInstanceObject($params->get($ityp), $menuid, $uid, $path, $perms);
		return $obj;
	}


	public static function userDataPath ($instObj)
	{
		$cmp = JApplicationHelper::getComponentName().'_'.$instObj->menuid;
		return self::getStorPath().'/'.$instObj->path.'/'.$cmp;
	}


	public static function getStoragePath ($instObj)
	{
		$cmp = JApplicationHelper::getComponentName().'_'.$instObj->menuid;
		return self::getStorPath().'/'.$instObj->path.'/'.$cmp;
	}


	public static function getDbPaths ($which, $dbname, $full=false, $cmp='')	// AO
	{
		$paths = [];
		if (!$cmp) $cmp = JApplicationHelper::getComponentName();
		$cmp_ = $cmp.'_';
		$cmpl = strlen($cmp_);
		switch ($which) {
			case 'a':
				$char1 = '*';
				break;
			case 'u':
				$char1 = '@';
				break;
			case 'g':
				$char1 = '_';
				break;
			default:
				$char1 = '@_';
				break;
		}
		$dpath = JPATH_SITE.'/'.self::getStorPath().'/';
		if (is_dir($dpath) && ($dh = opendir($dpath))) {
			if (!self::$siteMenu) {
				self::$siteMenu = Factory::getApplication()->getMenu('site');
			}
			while (($file = readdir($dh)) !== false) {
				if ($file[0]=='.') continue;
				if ($char1=='*' || strpos($char1, $file[0]) !== false) {
					if (!is_dir($dpath.$file)) continue;
					foreach (glob($dpath.$file.'/'.$cmp.'*') as $mid) {
						$dir = basename($mid);
						if ($dir==$cmp) {
							$mnut = 'OLD STORAGE LOCATION SCHEMA';
						} elseif (substr($dir,0,$cmpl)==$cmp_) {
							$mnu = (int)substr($dir,$cmpl);			//echo'<xmp>';var_dump(self::$siteMenu->getItem($mnu));echo'</xmp>';
							$mnut = self::$siteMenu->getItem($mnu)->title." ({$mnu})";
						} else echo "$cmpl $cmp_ $dir<br>";
						$ptf = $mid.'/'.$dbname.'.sql3';
						if (!file_exists($ptf)) $ptf = $mid.'/'.$dbname.'.db3';
						if (!file_exists($ptf)) $ptf = $mid.'/'.$dbname.'.sqlite';
						if (file_exists($ptf)) $paths[$file][$mnu] = ['path'=>$full ? $ptf : $file, 'mnun' => $mnu, 'mnut'=>$mnut];		//$paths[$file] = $full ? $ptf : $file;
					}
				}
			}
			closedir($dh);
		}
		return $paths;
	}


	public static function updateDb ($udbPath)
	{
		if (!file_exists($udbPath)) return ['MISSING DATABASE FILE'];
		$db = JDatabaseDriver::getInstance(['driver'=>'sqlite', 'database'=>$udbPath]);
		$dbver = $db->setQuery('PRAGMA user_version')->loadResult();
		$msgs = [];
		$updf = JPATH_COMPONENT_ADMINISTRATOR.'/sql/upd_'.$dbver.'.sql';
		if (file_exists($updf)) {
			$execs = explode(';', file_get_contents($updf));
			foreach ($execs as $exec) {
				$msg = null;
				$exec = trim($exec);
				if ($exec && $exec[0] != '#') $msg = self::dbnofail($db, $exec);
				if ($msg) $msgs[] = $msg;
			}
		}
		return $msgs;
	}


	private static function getStorPath ()
	{
		$results = Factory::getApplication()->triggerEvent('onRjuserDatapath');
		$dsp = trim($results[0] ?? '');
		return ($dsp ?: 'userstor');
	}

	private static function dbnofail ($db, $q)
	{
		try {
			$db->setQuery($q);
			$db->execute();
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

}


class RJUserInstanceObject	// SO
{
	protected $perms;
	public $type, $menuid, $uid, $path;
	public const CAN_CREA = 1;
	public const CAN_EDIT = 2;
	public const CAN_DELE = 4;

	public function __construct ($type, $menuid, $uid, $path, $perms)
	{
		$this->type = $type;
		$this->menuid = $menuid;
		$this->uid = $uid;
		$this->path = $path;
		$this->perms = $perms;
	}

	public function canCreate ()
	{
		return ($this->perms & self::CAN_CREA);
	}

	public function canEdit ()
	{
		return ($this->perms & self::CAN_EDIT);
	}

	public function canDelete ()
	{
		return ($this->perms & self::CAN_DELE);
	}

}

