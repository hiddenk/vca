<?php 

class User extends Guest {
	
	private $id;
	private $name;
	private $rank;
	private $mail;
	private $nbVps;
	private $key;
	private $language;
	
	/*** Get/Set ***/
	
	function __construct($id, $name='') {
		$this->id   = $id;
		$this->name = $name;
	}
	
	function getId() {
		return $this->id;
	}
	
	function getName() {
		return $this->name;
	}
	
	function setName($name) {
		$this->name = $name;
	}
	
	function getRank() {
		return $this->rank;
	}
	
	function setRank($rank) {
		$this->rank = $rank;
	}
	
	function getMail() {
		return $this->mail;
	}
	
	function setMail($mail) {
		$this->mail = $mail;
	}
	
	function getNbVps() {
		return $this->nbVps;
	}
	
	function setNbVps($nb) {
		$this->nbVps = $nb;
	}
	
	function getKey() {
		return $this->key;
	}
	
	function setKey($key) {
		$this->key = $key;
	}
	
	function getLanguage() {
		return $this->language;
	}
	
	function setLanguage($language) {
		$this->language = $language;
	}
	
	/*** VCA ***/
	
	/**
	 * VCA stats
	 */
	function vcaStats() {
		$link = Db::link();
			
		$sql = 'SELECT count(vps_id) as nb
		        FROM vps
		        WHERE vps_owner= :user_id AND nproc>0';
		$req = $link->prepare($sql);
		$req->execute(array('user_id' => $this->getId()));
		$do = $req->fetch(PDO::FETCH_OBJ);
		$nbVpsRun = $do->nb;
	
		$sql = 'SELECT count(vps_id) as nb
		        FROM vps
		        WHERE vps_owner= :user_id AND nproc=0';
		$req = $link->prepare($sql);
		$req->execute(array('user_id' => $this->getId()));
		$do = $req->fetch(PDO::FETCH_OBJ);
		$nbVpsStop = $do->nb;
		
		$sql = 'SELECT count(request_topic_id) as nb
		        FROM request_topic
		        WHERE request_topic_author= :user_id AND request_topic_resolved=0';
		$req = $link->prepare($sql);
		$req->execute(array('user_id' => $this->getId()));
		$do = $req->fetch(PDO::FETCH_OBJ);
		$request = $do->nb;
	
		return array(
				'nbVps'    => $nbVpsRun+$nbVpsStop,
				'nbVpsRun' => $nbVpsRun,
				'nbVpsStop'=> $nbVpsStop,
				'nbServer' => 0,
				'nbUser'   => 0,
				'request'  => $request
		);
	}
	
	/*** Users ***/
	function userProfile() {
		$link = Db::link();
		
		$sql = 'SELECT user_id, user_name, user_rank, user_mail,
		               if(v.nb IS NULL, 0, v.nb) as nb
		        FROM user
		        LEFT JOIN (SELECT vps_owner, count(vps_owner) as nb
		                   FROM vps GROUP BY vps_owner) v
		        ON v.vps_owner=user.user_id
		        WHERE user_id=:user_id';
		$req = $link->prepare($sql);
		$req->execute(array('user_id' => $this->getId()));
		$do = $req->fetch(PDO::FETCH_OBJ);
		
		return $do;
	}
	
	function userList() { return null; }
	
	/**
	 * Update user information
	 * @param user id
	 * @param user name
	 * @param user mail
	 * @return error
	 */
	function userUpdate($id, $user_name='', $user_mail='', $language='') {
		$link = Db::link();
		$id = $this->getId();
		
		if(empty($user_name) or empty($user_mail)) {
			return 1;
		}
		elseif(!filter_var($user_mail, FILTER_VALIDATE_EMAIL)) {
			return 9;
		}
		elseif(strlen($user_name) < 4 or strlen($user_name) > 25) {
			return 10;
		}
		
		$languageList = self::languageList();
		
		if(empty($language) or empty($languageList[$language])) {
			$language = $this->language;
		}
		
		$sql = 'UPDATE user
		        SET user_name= :user_name,
		            user_mail= :user_mail,
		            user_language=:user_language
		        WHERE user_id= :user_id';
		$req = $link->prepare($sql);
		$req->execute(array(
				'user_name' => $user_name,
				'user_mail' => $user_mail,
				'user_language' => $language,
				'user_id'   => $id
		));
	
		return 5;
	}
	
	function userDefinePassword($password, $id=0) {}
	
	/**
	 * Update password
	 * @param new password
	 * @param unused parameter
	 */
	function userPassword($old, $new) {
		$link = Db::link();
		
		$sql = 'SELECT user_id, user_password
		        FROM user
		        WHERE user_id=:user_id';
		$req = $link->prepare($sql);
		$req->execute(array('user_id' => $this->getId()));
		$do = $req->fetch(PDO::FETCH_OBJ);
		
		if($do->user_password == hash('sha512', $this->getId().$old)) {
			$sql = 'UPDATE user
			        SET user_password=:user_password
			        WHERE user_id=:user_id';
			$req = $link->prepare($sql);
			$req->execute(array(
					'user_password' => hash('sha512', $this->getId().$new),
					'user_id'       => $this->getId()
			));
			return 13;
		}
		else {
			return 11;
		}
	}
	
	function userNew($user_name='', $user_mail='', $user_password='') { return null; }
	function userDelete($id) { return null; }
	function userVps($id) { return $this->vpsList(); }
	
	function userLanguage($lang) {
		$link = Db::link();
		$langList = self::languageList();
		
		if(!empty($langList[$lang])) {
			$sql = 'UPDATE user
			        SET user_language= :user_language
			        WHERE user_id= :user_id';
			$req = $link->prepare($sql);
			$req->execute(array(
					'user_language' => $lang,
					'user_id'       => $id
			));
		}
	}
	
	/*** Language ***/
	
	static function languageList() {
		return array(
			'en_GB' => 'English',
			'fr_FR' => 'Français'
		);
	}
	
	/*** Requests ***/
	
	function requestList() {
		$list = array();
	
		$link = Db::link();
		$sql = 'SELECT request_topic_id, request_topic_title, request_topic_created,
		               request_topic_resolved
		        FROM request_topic
		        WHERE request_topic_author=:author
		        ORDER BY request_topic_id DESC';
		$req = $link->prepare($sql);
		$req->execute(array(
			'author' => $this->getId()
		));
		
		while ($do = $req->fetch(PDO::FETCH_OBJ)) {
			$list[$do->request_topic_id] = array(
				'topic'    => $do->request_topic_id,
				'title'    => $do->request_topic_title,
				'created'  => $do->request_topic_created,
				'resolved' => $do->request_topic_resolved
			);
		}
	
		return $list;
	}
	
	function requestNew($title, $message) {
		$link = Db::link();
		
		$sql = 'INSERT INTO request_topic
		        (request_topic_title, request_topic_created, request_topic_author)
		        VALUES
		        (:title, :created, :author)';
		$req = $link->prepare($sql);
		$req->execute(array(
			'title'   => ucfirst($title),
			'created' => $_SERVER['REQUEST_TIME'],
			'author'  => $this->getId()
		));
		
		$request = $link->lastInsertId();
		
		$sql = 'INSERT INTO request_message
		        (request_topic, request_message, request_message_date, request_message_user)
		        VALUES
		        (:topic, :message, :date, :user)';
		$req = $link->prepare($sql);
		$req->execute(array(
				'topic'   => $request,
				'message' => $message,
				'date'    => $_SERVER['REQUEST_TIME'],
				'user'    => $this->getId()
		));
	}
	
	function requestInfo($request) {
		$link = Db::link();
		$requestList = $this->requestList();
		
		if(empty($requestList[$request])) {
			return null;
		}
		
		$currentRequest = $requestList[$request];
		$messages = array();

		$sql = 'SELECT request_message, request_message_date,
		               user_name, request_message_user,
		               request_message_read, request_message_id
		        FROM request_message
				JOIN user ON request_message_user=user_id
				WHERE request_topic=:topic
		        ORDER BY request_message_id DESC';
		$req = $link->prepare($sql);
		$req->execute(array('topic' => $request));
		
		while ($do = $req->fetch(PDO::FETCH_OBJ)) {
			$messages[] = array(
				'message'  => $do->request_message,
				'date'     => $do->request_message_date,
				'user_id'  => $do->request_message_user,
				'user_name'=> $do->user_name
			);
			
			//Unread message
			if($do->request_message_read == 0 && $do->request_message_user != $this->getId()) {
				$sql = 'UPDATE request_message
				        SET request_message_read= :time
				        WHERE request_message_id= :id';
				$req = $link->prepare($sql);
				$req->execute(array(
						'time' => $_SERVER['REQUEST_TIME'],
						'id'   => $do->request_message_id
				));
			}
		}
				
		return array(
			'id'       => $request,
			'title'    => $currentRequest['title'],
			'resolved' => $currentRequest['resolved'],
			'messages' => $messages
		);
	}
	
	function requestAnswer($topic, $message) {
		$link = Db::link();
		$requestList = $this->requestList();
		
		if(empty($requestList[$topic]) or !empty($requestList[$topic]['resolved'])) {
			return null;
		}
		
		$sql = 'INSERT INTO request_message
		        (request_topic, request_message, request_message_date, request_message_user)
		        VALUES
		        (:topic, :message, :date, :user)';
		$req = $link->prepare($sql);
		$req->execute(array(
				'topic'   => $topic,
				'message' => $message,
				'date'    => $_SERVER['REQUEST_TIME'],
				'user'    => $this->getId()
		));
	}
	
	function requestClose($request) {
		$link = Db::link();
		$sql = 'UPDATE request_topic
		        SET request_topic_resolved= :resolved
		        WHERE request_topic_id= :request';
		$req = $link->prepare($sql);
		$req->execute(array(
			'request'  => $request,
			'resolved' => $_SERVER['REQUEST_TIME']
		));
	}
	
	/*** IP ***/
	function ipFree() { return null; }
	function ipList() { return null; }
	function ipNew($ip) { return null; }
	function ipDelete($ip) { return null; }
	
	/*** Serveur ***/
	function serverList() { return null; }
	function serverAdd($name,$address,$port,$key,$description='') { return null; }
	function serverDelete($id) { }
	function serverUpdate($id, $var)  { return null; }
	function serverReload($id=0) { return null; }
	function serverRestart($id=0) { }
	function serverTemplate($id=0) { return null; }
	function serverTemplateRename($id, $old, $new) { return null; }
	function serverTemplateAdd($id, $name) { return null; }
	function serverTemplateDelete($id, $name) { return null; }
	function serverBackup($server) { return null; }
	function serverBackupDelete($server, $idVps, $name) { return null; }
	
	/*** VPS ***/
	
	/**
	 * Return Vps list
	 * @param server id
	 */
	function vpsList($server=0) {
	
		$list = array();
		$link = Db::link();
	
		$sql = 'SELECT vps_id, vps_name, vps_ipv4, vps_description,
		               user_id, server_id, last_maj, ram,
		               ram_current, ostemplate, diskspace, nproc,
		               vps.server_id, user_name, vps_cpus as cpus,
		               diskspace_current, loadavg,
		               swap, onboot, diskinodes, vps_cpulimit,
				       vps_cpuunits, backup_limit, vps_protected
		        FROM vps
				LEFT JOIN user ON vps_owner=user_id
				WHERE vps_owner=:id';
		$req = $link->prepare($sql);
		$req->execute(array('id' => $this->getId()));
		
		while ($do = $req->fetch(PDO::FETCH_OBJ)) {
			$list[$do->vps_id] = array(
				'id'          => $do->vps_id,
				'name'        => $do->vps_name,
				'ipv4'        => $do->vps_ipv4,
				'description' => $do->vps_description,
				'ostemplate'  => $do->ostemplate,
				'ram'         => $do->ram,
				'ramCurrent'  => $do->ram_current,
				'disk'        => $do->diskspace,
				'nproc'       => $do->nproc,
				'serverId'    => 0,
				'ownerId'     => $do->user_id,
				'ownerName'   => $do->user_name,
				'loadavg'     => $do->loadavg,
				'cpus'        => $do->cpus,
				'diskspace'   => $do->diskspace,
				'diskspaceCurrent' => $do->diskspace_current,
				'swap'        => $do->swap,
				'onboot'      => $do->onboot,
				'protected'   => $do->vps_protected,
				'diskinodes'  => $do->diskinodes,
				'cpulimit'    => $do->vps_cpulimit,
				'cpuunits'    => $do->vps_cpuunits,
				'backup_limit'=> $do->backup_limit
			);
		}
	
		if(sizeof($list) == 0) {
			return '';
		}
		else {
			return $list;
		}
	}
	
	function vpsAdd($id, $var) {}
	
	/**
	 * Set VPS configuration
	 * @param unknown $id
	 * @param unknown $var
	 */
	function vpsUpdate($id, $var) {
		$link = Db::link();
	
		//Trim
		$var = array_map('trim', $var);
		
		$para = array();
		$vpsList = $this->vpsList();
		$vps = $vpsList[$id];
		
		if(empty($vps)) {
			return null;
		}
		
		if(!empty($var['name']) && $var['name'] != $vps['name'] && checkHostname($var['name'])) {
			$para['name'] = $var['name'];
		}
		
		if(isset($var['onboot'])) {
			if(!empty($var['onboot'])) {
				$para['onboot'] = 1;
			}
			else {
				$para['onboot'] = 0;
			}
		}
		
		$sql = 'SELECT server.server_id, server_address, vps_id,
		               server_key, server_port
		        FROM vps
				JOIN server ON vps.server_id=server.server_id
		        WHERE vps_id=:vps_id';
		$req = $link->prepare($sql);
		$req->execute(array('vps_id' => $vps['id']));		
		$do = $req->fetch(PDO::FETCH_OBJ);
		
		if(!empty($do->vps_id)) {
			$server = new Server($do->server_id);
			$server -> setAddress($do->server_address);
			$server -> setPort($do->server_port);
			$server -> setKey($do->server_key);
			$server -> vpsUpdate($do->vps_id, $para);
		}
	}
	
	/**
	 * Reload server information
	 * @param number $id
	 */
	function vpsReload($id=0) {
	
		$link = Db::link();
		$sql = 'SELECT vps.server_id, server_name, server_address,
	               server_description, server_key, server_port
		        FROM vps
				JOIN server ON server.server_id=vps.server_id
		        WHERE vps_id= :vps_id';
		$req = $link->prepare($sql);
		$req->execute(array('vps_id' => $id));
		
		while($do = $req->fetch(PDO::FETCH_OBJ)) {
			$server = new Server($do->server_id);
			$server -> setAddress($do->server_address);
			$server -> setPort($do->server_port);
			$server -> setKey($do->server_key);
			$server -> vpsReload();
		}
	}
	
	function vpsDelete($id) {}
	
	/**
	 * Start Vps
	 * @param vps id
	 */
	function vpsStart($id) {
		$link = Db::link();
	
		$sql = 'SELECT vps_id, server.server_id, server_address,
		               server_key, vps_owner, server_port
		        FROM vps
		        JOIN server ON server.server_id=vps.server_id
		        WHERE vps_id= :id';
		$req = $link->prepare($sql);
		$req->execute(array('id' => $id));
		$do = $req->fetch(PDO::FETCH_OBJ);
	
		if(!empty($do->server_id) && $do->vps_owner == $this->getId()) {
			$server = new Server($do->server_id);
			$server -> setAddress($do->server_address);
			$server -> setPort($do->server_port);
			$server -> setKey($do->server_key);
			$server -> start($do->vps_id);
		}
	}
	
	/**
	 * Stop Vps
	 * @param vps id
	 */
	function vpsStop($id) {
		$link = Db::link();
	
		$sql = 'SELECT vps_id, server.server_id, server_address,
		               server_key, vps_owner, server_port
		        FROM vps
		        JOIN server ON server.server_id=vps.server_id
		        WHERE vps_id= :id';
		$req = $link->prepare($sql);
		$req->execute(array('id' => $id));
		$do = $req->fetch(PDO::FETCH_OBJ);
	
		if(!empty($do->server_id) && $do->vps_owner == $this->getId()) {
			$server = new Server($do->server_id);
			$server -> setAddress($do->server_address);
			$server -> setPort($do->server_port);
			$server -> setKey($do->server_key);
			$server -> stop($do->vps_id);
		}
	}
	
	/**
	 * Restart Vps
	 * @param vps id
	 */
	function vpsRestart($id) {
		$link = Db::link();
	
		$sql = 'SELECT vps_id, server.server_id, server_address,
		               server_key, vps_owner, server_port
		        FROM vps
		        JOIN server ON server.server_id=vps.server_id
		        WHERE vps_id= :id';
		$req = $link->prepare($sql);
		$req->execute(array('id' => $id));
		$do = $req->fetch(PDO::FETCH_OBJ);
	
		if(!empty($do->server_id) && $do->vps_owner == $this->getId()) {
			$server = new Server($do->server_id);
			$server -> setAddress($do->server_address);
			$server -> setPort($do->server_port);
			$server -> setKey($do->server_key);
			$server -> restart($do->vps_id);
		}
	}
	
	function vpsClone($idVps, $ip, $hostname) {}
	function vpsCmd($idVps, $command) {}
	
	/**
	 * Modifie Vps root password
	 * @param vps id
	 * @param new password
	 */
	function vpsPassword($idVps, $password) {
		$link = Db::link();
	
		$sql = 'SELECT vps_id, server.server_id, server_address,
		               server_key, vps_owner, server_port
		        FROM vps
		        JOIN server ON server.server_id=vps.server_id
		        WHERE vps_id= :id';
		$req = $link->prepare($sql);
		$req->execute(array('id' => $idVps));
		$do = $req->fetch(PDO::FETCH_OBJ);
	
		if(!empty($do->server_id) && $do->vps_owner == $this->getId()) {
			$connect = new Socket($do->server_address, $do->server_port, $do->server_key);
			$connect -> write('password', $do->vps_id, $password);
			$data = json_decode($connect -> read());
		}
	}
	
	/**
	 * Return server template
	 * @param server id
	 * @return template list
	 */
	function vpsTemplate($id=0) {
		$link = Db::link();
		$sql = 'SELECT vps.server_id, server_name, server_address,
		               server_description, server_key, server_port
		        FROM vps
				JOIN server ON vps.server_id=server.server_id
		        WHERE vps_id= :server_id';
		$req = $link->prepare($sql);
		$req->execute(array('server_id' => $id));
	
		$do = $req->fetch(PDO::FETCH_OBJ);
	
		if(!empty($do->server_id)) {
			$server = new Server($do->server_id);
			$server -> setAddress($do->server_address);
			$server -> setPort($do->server_port);
			$server -> setKey($do->server_key);
			$list = array();
	
			$tpl_list = $server -> templateList();
	
			if(empty($tpl_list)) {
				return null;
			}
	
			//Get all templates names without extension
			foreach ($tpl_list[2] as $template) {
				if(substr($template, -7) == '.tar.gz') {
					$list[] = substr($template, 0, -7);
				}
				elseif(substr($template, -8) == '.tar.bz2') {
					$list[] = substr($template, 0, -8);
				}
				elseif(substr($template, -7) == '.tar.xz') {
					$list[] = substr($template, 0, -7);
				}
				elseif(substr($template, -4) == '.tar') {
					$list[] = substr($template, 0, -4);
				}
				elseif(substr($template, -3) == '.xz') {
					$list[] = substr($template, 0, -3);
				}
				else {
					$list[] = $template;
				}
			}
	
			return $list;
		}
	
		return null;
	}
	
	/**
	 * Reinstall a VPS
	 * @param vps id
	 * @param operating system
	 */
	function vpsReinstall($idVps, $os) {
		$link = Db::link();
	
		$sql = 'SELECT vps_id, server.server_id, server_address,
		               server_key, vps_owner, server_port,
		               vps_protected
		        FROM vps
		        JOIN server ON server.server_id=vps.server_id
		        WHERE vps_id= :id';
		$req = $link->prepare($sql);
		$req->execute(array('id' => $idVps));
		$do = $req->fetch(PDO::FETCH_OBJ);
	
		if(!empty($do->server_id) && $do->vps_owner == $this->getId() && empty($do->vps_protected)) {
			$templates = $this->vpsTemplate($do->vps_id);
	
			if(!empty($templates) && in_array($os, $templates)) {
				$connect = new Socket($do->server_address, $do->server_port, $do->server_key);
				$connect -> write('reinstall', $do->vps_id, $os);
				return $data = json_decode($connect -> read());
			}
		}
	
		return null;
	}
	
	function vpsNb() {
		$link = Db::link();
		
		$sql = 'SELECT count(vps_id) as nb
		        FROM vps
		        WHERE vps_owner= :user_id';
		$req = $link->prepare($sql);
		$req->execute(array('user_id' => $this->getId()));
		$do = $req->fetch(PDO::FETCH_OBJ);
		
		return $do->nb;
	}
	
	static function vpsBackup($idVps) {
		$link = Db::link();
	
		$sql = 'SELECT vps_id, server.server_id, server_address,
		               server_key, vps_owner, server_port
		        FROM vps
		        JOIN server ON server.server_id=vps.server_id
		        WHERE vps_id= :id';
		$req = $link->prepare($sql);
		$req->execute(array('id' => $idVps));
		$do = $req->fetch(PDO::FETCH_OBJ);
	
		if(!empty($do->server_id)) {
			$connect = new Socket($do->server_address, $do->server_port, $do->server_key);
			$connect -> write('backupList', $do->vps_id);
			return $data = json_decode($connect -> read());
		}
	}
	
	static function vpsBackupAdd($idVps) {
		$link = Db::link();
	
		$sql = 'SELECT vps_id, server.server_id, server_address,
		               server_key, vps_owner, server_port,
		               backup_limit
		        FROM vps
		        JOIN server ON server.server_id=vps.server_id
		        WHERE vps_id= :id';
		$req = $link->prepare($sql);
		$req->execute(array('id' => $idVps));
		$do = $req->fetch(PDO::FETCH_OBJ);
	
		if(!empty($do->backup_limit)) {
			$list = self::vpsBackup($do->vps_id);
			if(sizeof($list) >= $do->backup_limit) {
				return null;
			}
		}
		
		if(!empty($do->server_id)) {
			$connect = new Socket($do->server_address, $do->server_port, $do->server_key);
			$connect -> write('backupAdd', $do->vps_id);
			return $data = json_decode($connect -> read());
		}
	}
	
	function vpsBackupRestore($idVps, $name) {
		$link = Db::link();
	
		$sql = 'SELECT vps_id, server.server_id, server_address,
		               server_key, vps_owner, server_port
		        FROM vps
		        JOIN server ON server.server_id=vps.server_id
		        WHERE vps_id= :id';
		$req = $link->prepare($sql);
		$req->execute(array('id' => $idVps));
		$do = $req->fetch(PDO::FETCH_OBJ);
	
		if(!empty($do->server_id)) {
			$connect = new Socket($do->server_address, $do->server_port, $do->server_key);
			$connect -> write('backupRestore', $do->vps_id, $name);
			return $data = json_decode($connect -> read());
		}
	}
	
	function vpsBackupDelete($idVps, $name) {
		$link = Db::link();
	
		$sql = 'SELECT vps_id, server.server_id, server_address,
		               server_key, vps_owner, server_port
		        FROM vps
		        JOIN server ON server.server_id=vps.server_id
		        WHERE vps_id= :id';
		$req = $link->prepare($sql);
		$req->execute(array('id' => $idVps));
		$do = $req->fetch(PDO::FETCH_OBJ);
	
		if(!empty($do->server_id)) {
			$connect = new Socket($do->server_address, $do->server_port, $do->server_key);
			$connect -> write('backupDelete', $do->vps_id, $name);
			return $data = json_decode($connect -> read());
		}
	}
	
	function vpsSchedule($idVps) {
		$link = Db::link();
		$list = array();
		
		$sql = 'SELECT schedule_id, name, minute, hour, dayw, dayn, month
		        FROM schedule
		        WHERE schedule_vps= :id';
		$req = $link->prepare($sql);
		$req->execute(array('id' => $idVps));
		
		while($do = $req->fetch(PDO::FETCH_OBJ)) {
			$list[$do->schedule_id] = clone $do;
		}
		
		return $list;
	}
	
	function vpsScheduleAdd($vps, $save, $name, $minute, $hour, $dayw, $dayn, $month) {
		$link = Db::link();
		
		if(empty($save)) {
			$sql = 'INSERT INTO schedule
			        (schedule_vps, name, minute, hour, dayw, dayn, month)
			        VALUES
			        (:vps, :name, :minute, :hour, :dayw, :dayn, :month)';
			$req = $link->prepare($sql);
			$req->execute(array(
				'vps'   => $vps,
				'name'  => $name,
				'minute'=> $minute,
				'hour'  => $hour,
				'dayw'  => $dayw,
				'dayn'  => $dayn,
				'month' => $month
			));
		}
		else {
			$sql = 'UPDATE schedule
			        SET name= :name,
			            minute= :minute,
			            hour= :hour,
			            dayw= :dayw,
			            dayn= :dayn,
			            month= :month
			        WHERE schedule_id=:save AND schedule_vps=:vps';
			$req = $link->prepare($sql);
			$req->execute(array(
				'save'  => $save,
				'vps'   => $vps,
				'name'  => $name,
				'minute'=> $minute,
				'hour'  => $hour,
				'dayw'  => $dayw,
				'dayn'  => $dayn,
				'month' => $month
			));
		}
	}
	
	function vpsScheduleDelete($save) {
		$link = Db::link();
		
		$sql = 'DELETE FROM schedule
		        WHERE schedule_id=:save';
		$req = $link->prepare($sql);
		$req->execute(array(
			'save'  => $save
		));
	}
}

?>