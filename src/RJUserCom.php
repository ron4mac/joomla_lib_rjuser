<?php
/**
* @package		lib_rjuser
* @copyright	Copyright (C) 2022-2024 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		1.3.5
*/
namespace RJCreations\Library;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Application\ApplicationHelper;

abstract class RJUserCom
{
	protected static $siteMenu = null;
	protected static $instObj = null;

	// sanity check for components needing certain requirement
	public static function Igaa ()
	{
		return 4;
	}


	public static function shout ()
	{
		echo 'READ ANY GOOD BOOKS LATELY?';
	}


	public static function getInstObject ($mid=null)	// SO
	{
		if (self::$instObj) return self::$instObj;

		$app = Factory::getApplication();
		if ($mid) {
			$params = $app->getMenu()->getItem($mid)->getParams();
			$menuid = $mid;
		} else {
			$params = $app->getParams();
			$menuid = $app->input->getInt('Itemid', 0);
		}	//echo'<pre>';var_dump($mid,$params);echo'</pre>';
		if (!$menuid) throw new \Exception('NOT ALLOWED: missing menu ID', 400);
		$user = $app->getIdentity();
		$uid = $user->get('id');
		$ugrps = $user->get('groups');
		$allperms = RJUserInstanceObject::CAN_CREA + RJUserInstanceObject::CAN_EDIT + RJUserInstanceObject::CAN_DELE;
		$ityp = $params->get('instance_type');
		$path = '';
		$perms = 0;
		switch ($ityp) {
			case 0:	//user
				if ($uid) $perms = $allperms;
				$path = '@'.$uid;
				break;
			case 1:	//group
				//$auth = $params->get('group_auth');
				$auth = $params->get('group_admin', []);
				//$path = '_'.$auth;
				$path = '_'.$params->get('group_owner', 123456789);
				if ($uid && array_intersect($ugrps, $auth)) $perms = $allperms;
				break;
			case 2:	//site
				$auth = $params->get('site_admin', []);
				$path = '_0';
				if ($uid && array_intersect($ugrps, $auth)) $perms = $allperms;
				break;
		}
		self::$instObj = new RJUserInstanceObject($ityp, $menuid, $uid, $path, $perms);
		//echo'<pre>';var_dump($ugrps,$auth,self::$instObj);echo'</pre>';
		return self::$instObj;
	}


	public static function getStorageBase ()
	{
		$results = Factory::getApplication()->triggerEvent('onRjuserDatapath');
		$sb = trim($results[0] ?? '');
		return ($sb ?: 'userstor');
	}


	public static function userDataPath ($instObj=null)
	{
		// alias
		return self::getStoragePath($instObj);
	}


	public static function getStoragePath ($instObj=null)
	{
		if (!$instObj) $instObj = self::getInstObject();
		$cmp = ApplicationHelper::getComponentName().'_'.$instObj->menuid;
		return self::getStorageBase().'/'.$instObj->path.'/'.$cmp;
	}


	public static function getDbPaths ($which, $dbname, $full=false, $cmp='')	// AO
	{
		$paths = [];
		if (!$cmp) $cmp = ApplicationHelper::getComponentName();
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
		$dpath = JPATH_SITE.'/'.self::getStorageBase().'/';
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
							$mob = self::$siteMenu->getItem($mnu);
							$mnut = ($mob ? $mob->title : '??')." ({$mnu})";
						} else echo "$cmpl $cmp_ $dir<br>";
						$ptf = $mid.'/'.$dbname.'.sql3';
						if (!file_exists($ptf)) $ptf = $mid.'/'.$dbname.'.db3';
						if (!file_exists($ptf)) $ptf = $mid.'/'.$dbname.'.sql3';
						if (!file_exists($ptf)) $ptf = $mid.'/'.$dbname.'.sqlite';
						if (file_exists($ptf)) $paths[$file][$mnu] = ['path'=>$full ? $ptf : $file, 'mnun' => $mnu, 'mnut'=>$mnut];		//$paths[$file] = $full ? $ptf : $file;
					}
				}
			}
			closedir($dh);
		}
		return $paths;
	}


	public static function getDb ($creok=false, $fnam=null, $fext='.db3')
	{
		$path = self::getStoragePath().'/';
		$cmpnam = explode('_',basename(JPATH_COMPONENT))[1];
		$dbfile = $path.($fnam??$cmpnam).$fext;
//var_dump($dbfile);jexit();
		if (file_exists($dbfile)) return self::openDb($dbfile);
//var_dump($dbfile);jexit();
		if (!$creok) throw new \Exception('NOT ALLOWED: create DB', 400);

		return self::createDb($path, $fnam, $fext);
	}


	public static function createDb ($path, $fnam=null, $fext='.db3')
	{
		$cmpnam = explode('_',basename(JPATH_COMPONENT))[1];
		$dbfile = $path.'/'.($fnam??$cmpnam).$fext;
		if (file_exists($dbfile)) return;
		$db = self::openDb($dbfile);
		$execs = explode(';', file_get_contents(JPATH_COMPONENT_ADMINISTRATOR.'/sql/'.$cmpnam.'.sql'));
		foreach ($execs as $exec) {
			$exec = trim($exec);
			if ($exec && $exec[0] != '#') $db->setQuery($exec)->execute();
		}
		return $db;
	}


	public static function getDbInfo ($udbPath, $table, $szcb=null)
	{
		if (!file_exists($udbPath)) return [];
		$size = filesize($udbPath);		//var_dump(explode('_',basename(JPATH_COMPONENT))[1]);
		$db = self::openDb($udbPath);
		$items = $db->setQuery('SELECT COUNT(*) FROM '.$table)->loadResult();
		$dbv = $db->setQuery('PRAGMA user_version')->loadResult();
		// get any extra storage useage as determined by the instance
		if ($szcb) $size += $szcb($db);
		return ['size'=>$size,'items'=>$items,'dbv'=>$dbv];
	}


	public static function updateDb ($udbPath)
	{
		if (!file_exists($udbPath)) return ['MISSING DATABASE FILE'];
		$db = self::openDb($udbPath);
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


/*	private static function getStorPath ()
	{
		$results = Factory::getApplication()->triggerEvent('onRjuserDatapath');
		$dsp = trim($results[0] ?? '');
		return ($dsp ?: 'userstor');
	}*/

	private static function openDb ($ptdbf)
	{
		$db = DatabaseDriver::getInstance(['driver'=>'sqlite','database'=>$ptdbf]);
		$db->connect();
		return $db;
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

