<?php

use Joomla\CMS\Factory;

abstract class RJUserCom
{
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
		switch ($which) {
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
			while (($file = readdir($dh)) !== false) {
				if (strpos($char1, $file[0]) !== false) {
					foreach (glob($dpath.$file.'/'.$cmp.'*') as $mid) {
						$ptf = $mid.'/'.$dbname.'.sql3';
						if (file_exists($ptf)) $paths[] = $full ? $ptf : $file;
						$ptf = $mid.'/'.$dbname.'.db3';
						if (file_exists($ptf)) $paths[] = $full ? $ptf : $file;
						$ptf = $mid.'/'.$dbname.'.sqlite';
						if (file_exists($ptf)) $paths[] = $full ? $ptf : $file;
					}
				}
			}
			closedir($dh);
		}
		return $paths;
	}

	private static function getStorPath ()
	{
		$results = Factory::getApplication()->triggerEvent('onRjuserDatapath');
		$dsp = isset($results[0]) ? trim($results[0]) : false;
		return ($dsp ?: 'userstor');
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

