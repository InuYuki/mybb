<?php
/**
 * MyBB 1.2
 * Copyright © 2007 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybboard.net
 * License: http://www.mybboard.net/license.php
 *
 * $Id$
 */

/**
 * Upgrade Script: 1.2.3
 */

/** NEEDS TO BE CHANGED PENDING FINAL RELEASE **/
$upgrade_detail = array(
	"revert_all_templates" => 0,
	"revert_all_themes" => 0,
	"revert_all_settings" => 0,
	"requires_deactivated_plugins" => 1,
);

@set_time_limit(0);

function upgrade9_dbchanges()
{
	global $db, $output, $mybb;

	$output->print_header("Performing Queries");

	echo "<p>Performing necessary upgrade queries..</p>";
	
	$db->query("ALTER TABLE ".TABLE_PREFIX."privatemessages ADD INDEX ( `uid` )");
	$db->query("ALTER TABLE ".TABLE_PREFIX."posts ADD INDEX ( `visible` )");
	
	if($db->field_exists('recipients', "privatemessages"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."privatemessages DROP recipients;");
	}	
	$db->query("ALTER TABLE ".TABLE_PREFIX."privatemessages ADD recipients text NOT NULL AFTER fromid");
	
	if($db->field_exists('deletetime', "privatemessages"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."privatemessages DROP deletetime;");
	}
	$db->query("ALTER TABLE ".TABLE_PREFIX."privatemessages ADD deletetime bigint(30) NOT NULL default '0' AFTER dateline");
	
	
	if($db->field_exists('maxpmrecipients', "usergroups"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups DROP maxpmrecipients;");
	}
	$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD maxpmrecipients int(4) NOT NULL default '5' AFTER pmquota");


	$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD canwarnusers char(3) NOT NULL default '' AFTER cancustomtitle");
	$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD canreceivewarnings char(3) NOT NULL default '' AFTER canwarnusers");
	$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD maxwarningsday int(3) NOT NULL default '3' AFTER canreceivewarnings");
	$db->query("UPDATE ".TABLE_PREFIX."usergroups SET canreceivewarnings='no' WHERE cancp='yes' OR gid=1");
	$db->query("UPDATE ".TABLE_PREFIX."usergroups SET maxwarningsday=3, canwarnusers='yes' WHERE cancp='yes' OR issupermod='yes' OR gid='6'"); // Admins, Super Mods and Mods
	if($db->field_exists('newpms', "users"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."users DROP newpms;");
	}
	
	if($db->field_exists('keywords', "searchlog"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."searchlog DROP keywords;");
	}
	$db->query("ALTER TABLE ".TABLE_PREFIX."searchlog ADD keywords text NOT NULL AFTER querycache");
	
	$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups CHANGE canaddpublicevents canaddevents char(3) NOT NULL default '';");
	$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups DROP canaddprivateevents;");
	$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD canbypasseventmod char(3) NOT NULL default '' AFTER canaddevents;");
	$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD canmoderateevents char(3) NOT NULL default '' AFTER canbypasseventmod;");
	$db->query("UPDATE ".TABLE_PREFIX."usergroups SET canbypasseventmod='yes', canmoderateevents='yes' WHERE cancp='yes' OR issupermod='yes'");
	$db->query("UPDATE ".TABLE_PREFIX."usergroups SET canbypasseventmod='no', canmoderateevents='no' WHERE cancp='no' AND issupermod='no'");
	$db->query("UPDATE ".TABLE_PREFIX."usergroups SET canaddevents='no' WHERE gid=1;");

	$db->drop_table("maillogs");	
	$db->drop_table("mailerrors");
	$db->drop_table("promotions");
	$db->drop_table("promotionlogs");
		
	$db->query("CREATE TABLE ".TABLE_PREFIX."maillogs (
		mid int unsigned NOT NULL auto_increment,
		subject varchar(200) not null default '',
		message text NOT NULL default '',
		dateline bigint(30) NOT NULL default '0',
		fromuid int unsigned NOT NULL default '0',
		fromemail varchar(200) not null default '',
		touid bigint(30) NOT NULL default '0',
		toemail varchar(200) NOT NULL default '',
		tid int unsigned NOT NULL default '0',
		ipaddress varchar(20) NOT NULL default '',
		PRIMARY KEY(mid)
	) TYPE=MyISAM;");

	$db->query("CREATE TABLE ".TABLE_PREFIX."mailerrors(
		eid int unsigned NOT NULL auto_increment,
		subject varchar(200) NOT NULL default '',
		message TEXT NOT NULL,
		toaddress varchar(150) NOT NULL default '',
		fromaddress varchar(150) NOT NULL default '',
		dateline bigint(30) NOT NULL default '0',
		error text NOT NULL,
		smtperror varchar(200) NOT NULL default '',
		smtpcode int(5) NOT NULL default '0',
		PRIMARY KEY(eid)
 	) TYPE=MyISAM;");
	
	$db->query("CREATE TABLE ".TABLE_PREFIX."promotions(
		pid int unsigned NOT NULL auto_increment,
		title varchar(120) NOT NULL default '',
		description text NOT NULL,
		enabled int(1) NOT NULL default '1',
		logging int(1) NOT NULL default '0',
		posts int NOT NULL default '0',
		posttype varchar(120) NOT NULL default '',
		registered int NOT NULL default '0',
		registeredtype varchar(2) NOT NULL default '',
		reputations int NOT NULL default '0',
		reputationtype varchar(120) NOT NULL default '',
		requirements varchar(2) NOT NULL default '',
		originalusergroup smallint NOT NULL default '0',
		newusergroup smallint unsigned NOT NULL default '0',
		usergrouptype varchar(120) NOT NULL default '0',
		PRIMARY KEY(pid)
 	) TYPE=MyISAM;");
	
	$db->query("CREATE TABLE ".TABLE_PREFIX."promotionlogs(
		plid int unsigned NOT NULL auto_increment,
		pid int unsigned NOT NULL default '0',
		uid int unsigned NOT NULL default '0',
		oldusergroup smallint unsigned NOT NULL default '0',
		newusergroup smallint unsigned NOT NULL default '0',
		dateline bigint(30) NOT NULL default '0',
		PRIMARY KEY(plid)
 	) TYPE=MyISAM;");

	if($db->field_exists('maxemails', "usergroups"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups DROP maxemails;");
	}
	$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD maxemails int(3) NOT NULL default '5' AFTER cansendemail");
	
	if($db->field_exists('parseorder', "mycode"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."mycode DROP parseorder;");
	}
	$db->query("ALTER TABLE ".TABLE_PREFIX."mycode ADD parseorder smallint unsigned NOT NULL default '0' AFTER active");
	
	if($db->field_exists('mod_edit_posts', "forums"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."forums DROP mod_edit_posts;");
	}
	$db->query("ALTER TABLE ".TABLE_PREFIX."forums ADD mod_edit_posts char(3) NOT NULL default '' AFTER modthreads");

	if($db->field_exists('pmnotice', "users"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."users DROP pmnotice;");
	}
	$db->query("ALTER TABLE ".TABLE_PREFIX."users CHANGE pmpopup pmnotice char(3) NOT NULL default ''");
	
	$db->drop_table("tasks");
	$db->drop_table("tasklog");

	$db->query("CREATE TABLE ".TABLE_PREFIX."tasks (
		tid int unsigned NOT NULL auto_increment,
		title varchar(120) NOT NULL default '',
		description text NOT NULL,
		file varchar(30) NOT NULL default '',
		minute varchar(200) NOT NULL default '',
		hour varchar(200) NOT NULL default '',
		day varchar(100) NOT NULL default '',
		month varchar(30) NOT NULL default '',
		weekday varchar(15) NOT NULL default '',
		nextrun bigint(30) NOT NULL default '0',
		lastrun bigint(30) NOT NULL default '0',
		enabled int(1) NOT NULL default '1',
		logging int(1) NOT NULL default '0',
		locked bigint(30) NOT NULL default '0',
		PRIMARY KEY(tid)
	) TYPE=MyISAM;");


	$db->query("CREATE TABLE ".TABLE_PREFIX."tasklog (
		lid int unsigned NOT NULL auto_increment,
		tid int unsigned NOT NULL default '0',
		dateline bigint(30) NOT NULL default '0',
		data text NOT NULL,
		PRIMARY KEY(lid)
	) TYPE=MyISAM;");


	include_once MYBB_ROOT."inc/functions_task.php";
	$tasks = file_get_contents(INSTALL_ROOT.'resources/tasks.xml');
	$parser = new XMLParser($tasks);
	$parser->collapse_dups = 0;
	$tree = $parser->get_tree();

	// Insert scheduled tasks
	foreach($tree['tasks'][0]['task'] as $task)
	{
		$new_task = array(
			'title' => $db->escape_string($task['title'][0]['value']),
			'description' => $db->escape_string($task['description'][0]['value']),
			'file' => $db->escape_string($task['file'][0]['value']),
			'minute' => $db->escape_string($task['minute'][0]['value']),
			'hour' => $db->escape_string($task['hour'][0]['value']),
			'day' => $db->escape_string($task['day'][0]['value']),
			'weekday' => $db->escape_string($task['weekday'][0]['value']),
			'month' => $db->escape_string($task['month'][0]['value']),
			'enabled' => $db->escape_string($task['enabled'][0]['value']),
			'logging' => $db->escape_string($task['logging'][0]['value'])
		);

		$new_task['nextrun'] = fetch_next_run($new_task);

		$db->insert_query("tasks", $new_task);
		$taskcount++;
	}

	if($db->table_exists("favorites") && !$db->table_exists("threadsubscriptions"))
	{
		$db->query("RENAME TABLE ".TABLE_PREFIX."favorites TO ".TABLE_PREFIX."threadsubscriptions");
	}
	
	if($db->field_exists('fid', "threadsubscriptions"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."privatemessages DROP recipients;");
		$db->query("ALTER TABLE ".TABLE_PREFIX."threadsubscriptions CHANGE fid sid int unsigned NOT NULL auto_increment");
	}
	
	if($db->field_exists('type', "threadsubscriptions"))
	{
		$db->query("UPDATE ".TABLE_PREFIX."threadsubscriptions SET type='0' WHERE type='f'");
		$db->query("UPDATE ".TABLE_PREFIX."threadsubscriptions SET type='1' WHERE type='s'");
		$db->query("ALTER TABLE ".TABLE_PREFIX."threadsubscriptions CHANGE type notification int(1) NOT NULL default '0'");
	}
	
	if($db->field_exists('dateline', "threadsubscriptions"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."threadsubscriptions DROP dateline;");
	}
	$db->query("ALTER TABLE ".TABLE_PREFIX."threadsubscriptions ADD dateline bigint(30) NOT NULL default '0'");
		
	if($db->field_exists('dateline', "threadsubscriptions"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."threadssubscriptions DROP dateline;");
	}
	$db->query("ALTER TABLE ".TABLE_PREFIX."threadsubscriptions ADD subscriptionkey varchar(32) NOT NULL default ''");

	if($db->field_exists('emailnotify', "users"))
	{
		$db->query("UPDATE ".TABLE_PREFIX."users SET emailnotify='1' WHERE emailnotify='no'");
		$db->query("UPDATE ".TABLE_PREFIX."users SET emailnotify='2' WHERE emailnotify='yes'");
		$db->query("ALTER TABLE ".TABLE_PREFIX."users CHANGE emailnotify subscriptionmethod int(1) NOT NULL default '0'");
	}

	$db->query("CREATE TABLE ".TABLE_PREFIX."warninglevels (
		lid int unsigned NOT NULL auto_increment,
		percentage int(3) NOT NULL default '0',
		action text NOT NULL,
		PRIMARY KEY(lid)
	) TYPE=MyISAM;");

	$db->query("CREATE TABLE ".TABLE_PREFIX."warningtypes (
		tid int unsigned NOT NULL auto_increment,
		title varchar(120) NOT NULL default '',
		points int unsigned NOT NULL default '0',
		expirationtime bigint(30) NOT NULL default '0',
		PRIMARY KEY(tid)
	) TYPE=MyISAM;");

	$db->query("CREATE TABLE ".TABLE_PREFIX."warnings (
		wid int unsigned NOT NULL auto_increment,
		uid int unsigned NOT NULL default '0',
		tid int unsigned NOT NULL default '0',
		pid int unsigned NOT NULL default '0',
		title varchar(120) NOT NULL default '',
		points int unsigned NOT NULL default '0',
		dateline bigint(30) NOT NULL default '0',
		issuedby int unsigned NOT NULL default '0',
		expires bigint(30) NOT NULL default '0',
		expired int(1) NOT NULL default '0',
		daterevoked bigint(30) NOT NULL default '0',
		revokedby int unsigned NOT NULL default '0',
		revokereason text NOT NULL,
		notes text NOT NULL,
		PRIMARY KEY(wid)
	) TYPE=MyISAM;");

	if($db->field_exists('warningpoints', "users"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."users DROP warningpoints;");
	}
	$db->query("ALTER TABLE ".TABLE_PREFIX."users ADD warningpoints int(3) NOT NULL default '0' AFTER unreadpms");
	$db->query("ALTER TABLE ".TABLE_PREFIX."users ADD moderateposts int(1) NOT NULL default '0' AFTER warningpoints");
	$db->query("ALTER TABLE ".TABLE_PREFIX."users ADD moderationtime bigint(30) NOT NULL default '0' AFTER moderateposts");
	$db->query("ALTER TABLE ".TABLE_PREFIX."users ADD suspendposting int(1) NOT NULL default '0' AFTER moderationtime");
	$db->query("ALTER TABLE ".TABLE_PREFIX."users ADD suspensiontime bigint(30) NOT NULL default '0' AFTER suspendposting");

	$contents = "Done</p>";
	$contents .= "<p>Click next to continue with the upgrade process.</p>";
	$output->print_contents($contents);
	$output->print_footer("9_dbchanges2");
}

function upgrade9_dbchanges2()
{
	global $db, $output, $mybb;

	$output->print_header("Converting Ban Filters");

	echo "<p>Converting existing banned IP addresses, email addresses and usernames..</p>";
	
	$db->drop_table("banfilters");
	
	$db->query("CREATE TABLE ".TABLE_PREFIX."banfilters (
	  fid int unsigned NOT NULL auto_increment,
	  filter varchar(200) NOT NULL default '',
	  type int(1) NOT NULL default '0',
	  lastuse bigint(30) NOT NULL default '0',
	  dateline bigint(30) NOT NULL default '0',
	  PRIMARY KEY  (fid)
	) TYPE=MyISAM;");

	// Now we convert all of the old bans in to the new system!
	$ban_types = array('bannedips','bannedemails','bannedusernames');
	foreach($ban_types as $type)
	{
		$bans = explode(",", $mybb->settings[$type]);
		$bans = array_map("trim", $bans);
		foreach($bans as $ban)
		{
			if($type == "bannedips")
			{
				$ban_type = 1;
			}
			else if($type == "bannedusernames")
			{
				$ban_type = 2;
			}
			else if($type == "bannedemails")
			{
				$ban_type = 3;
			}
			$new_ban = array(
				"filter" => $db->escape_string($banned_ip),
				"type" => $ban_type,
				"dateline" => time()
			);
			$db->insert_query("banfilters", $new_ban);
		}
	}

	$contents = "Done</p>";
	$contents .= "<p>Click next to continue with the upgrade process.</p>";
	$output->print_contents($contents);
	$output->print_footer("9_dbchanges3");
}

function upgrade9_dbchanges3()
{
	global $db, $output, $mybb;

	$output->print_header("Performing Queries");

	echo "<p>Performing necessary upgrade queries..</p>";
	
	$db->drop_table("spiders");

	$db->query("CREATE TABLE ".TABLE_PREFIX."spiders (
		sid int unsigned NOT NULL auto_increment,
		name varchar(100) NOT NULL default '',
		theme int unsigned NOT NULL default '0',
		language varchar(20) NOT NULL default '',
		usergroup int unsigned NOT NULL default '0',
		useragent varchar(200) NOT NULL default '',
		lastvisit bigint(30) NOT NULL default '0',
		PRIMARY KEY(sid)
	) TYPE=MyISAM;");

	$db->query("INSERT INTO ".TABLE_PREFIX."spiders (name,useragent) VALUES ('GoogleBot','google');");
	$db->query("INSERT INTO ".TABLE_PREFIX."spiders (name,useragent) VALUES ('Lycos','lycos');");
	$db->query("INSERT INTO ".TABLE_PREFIX."spiders (name,useragent) VALUES ('Ask Jeeves','ask jeeves');");
	$db->query("INSERT INTO ".TABLE_PREFIX."spiders (name,useragent) VALUES ('Hot Bot','slurp@inktomi');");
	$db->query("INSERT INTO ".TABLE_PREFIX."spiders (name,useragent) VALUES ('What You Seek','whatuseek');");
	$db->query("INSERT INTO ".TABLE_PREFIX."spiders (name,useragent) VALUES ('Archive.org','is_archiver');");
	$db->query("INSERT INTO ".TABLE_PREFIX."spiders (name,useragent) VALUES ('Altavista','scooter');");
	$db->query("INSERT INTO ".TABLE_PREFIX."spiders (name,useragent) VALUES ('Alexa','ia_archiver');");
	$db->query("INSERT INTO ".TABLE_PREFIX."spiders (name,useragent) VALUES ('MSN Search','msnbot');");
	$db->query("INSERT INTO ".TABLE_PREFIX."spiders (name,useragent) VALUES ('Yahoo!','yahoo slurp');");

	// DST correction changes
	$db->query("UPDATE ".TABLE_PREFIX."users SET dst=1 WHERE dst='yes'");
	$db->query("UPDATE ".TABLE_PREFIX."users SET dst=0 WHERE dst='no'");
	$db->query("ALTER TABLE ".TABLE_PREFIX."users CHANGE dst dst INT(1) NOT NULL default '0'");
	if($db->field_exists('dstcorrection', "users"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."users DROP dstcorrection;");
	}
	$db->query("ALTER TABLE ".TABLE_PREFIX."users ADD dstcorrection INT(1) NOT NULL default '0' AFTER dst");

	$db->query("UPDATE ".TABLE_PREFIX."users SET dstcorrection=2;");	
	$db->query("ALTER TABLE ".TABLE_PREFIX."adminoptions CHANGE permsset permissions text NOT NULL default ''");
	
	$adminoptions = file_get_contents(INSTALL_ROOT.'resources/adminoptions.xml');
	$parser = new XMLParser($adminoptions);
	$parser->collapse_dups = 0;
	$tree = $parser->get_tree();
	
	// Fetch default permissions list
	$default_permissions = array();
	foreach($tree['adminoptions'][0]['user'] as $users)
	{
		$uid = $users['attributes']['uid'];
		if($uid == 0)
		{
			foreach($users['permissions'][0]['module'] as $module)
			{
				foreach($module['permission'] as $permission)
				{
					$default_permissions[$module['attributes']['name']][$permission['attributes']['name']] = $permission['value'];
				}
			}
			break;
		}
	}
	
	$convert_permissions = array(
		"caneditsettings" => array(
				"module" => "config",
				"permission" => "settings"
			),
		"caneditann" => array(
				"module" => "forum",
				"permission" => "announcements",
			),
		"caneditforums" => array(
				"module" => "forum",
				"permission" => "management",
			),
		"canmodposts" => array(
				"module" => "forum",
				"permission" => "moderation_queue",
			),
		"caneditsmilies" => array(
				"module" => "config",
				"permission" => "smilies",
			),
		"caneditpicons" => array(
				"module" => "config",
				"permission" => "post_icons",
			),
		"caneditthemes" => array(
				"module" => "style",
				"permission" => "themes",
			),
		"canedittemps" => array(
				"module" => "style",
				"permission" => "templates",
			),
		"caneditusers" => array(
				"module" => "user",
				"permission" => "view",
			),
		"caneditpfields" => array(
				"module" => "config",
				"permission" => "profile_fields",
			),
		"caneditugroups" => array(
				"module" => "user",
				"permission" => "groups",
			),
		"caneditaperms" => array(
				"module" => "user",
				"permission" => "admin_permissions",
			),
		"caneditutitles" => array(
				"module" => "user",
				"permission" => "titles",
			),
		"caneditattach" => array(
				"module" => "forum",
				"permission" => "attachments",
			),
		"canedithelp" => array(
				"module" => "config",
				"permission" => "help_documents",
			),
		"caneditlangs" => array(
				"module" => "config",
				"permission" => "languages",
			),
		"canrunmaint" => array(
				"module" => "tools",
				"permission" => "recount_rebuild",
			),
		"canrundbtools" => array(
				"module" => "tools",
				"permission" => "backupdb",
			),
	);
	
	$new_permissions = $default_permissions;
	
	$query = $db->simple_select("adminoptions");
	while($adminoption = $db->fetch_array($query))
	{
		foreach($adminoption as $field => $value)
		{
			if(strtolower(substr($field, 0, 3)) != "can")
			{
				continue;
			}
			
			if(array_key_exists($field, $convert_permissions))
			{
				if($value == "yes")
				{
					$value = 1;
				}
				else
				{
					$value = $default_permissions[$convert_permissions[$field]['module']][$convert_permissions[$field]['permission']];
				}
				$new_permissions[$convert_permissions[$field]['module']][$convert_permissions[$field]['permission']] = $value;
			}
		}
		
		$db->update_query("adminoptions", array('permissions' => serialize($new_permissions)), "uid = '{$adminoption['uid']}'");
		
		$new_permissions = array();	
	}
	
	foreach($convert_permissions as $field => $value)
	{
		if($db->field_exists($field, "adminoptions"))
		{
			$db->query("ALTER TABLE ".TABLE_PREFIX."adminoptions DROP {$field}");
		}
	}
	

	$contents = "Done</p>";
	$contents .= "<p>Click next to continue with the upgrade process.</p>";
	$output->print_contents($contents);
	$output->print_footer("9_dbchanges4");
}

function upgrade9_dbchanges4()
{
	global $db, $output, $mybb;

	$output->print_header("Performing Queries");

	echo "<p>Performing necessary upgrade queries..</p>";

	if($db->field_exists('statustime', "privatemessages"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."privatemessages DROP statustime;");
	}
	$db->query("ALTER TABLE ".TABLE_PREFIX."privatemessages ADD statustime bigint(30) NOT NULL default '0' AFTER status");

	$db->query("CREATE TABLE ".TABLE_PREFIX."calendars (
	  cid int unsigned NOT NULL auto_increment,
	  name varchar(100) NOT NULL default '',
	  disporder int unsigned NOT NULL default '0',
	  startofweek int(1) NOT NULL default '0',
	  showbirthdays int(1) NOT NULL default '0',
	  eventlimit int(3) NOT NULL default '0',
	  moderation int(1) NOT NULL default '0',
	  allowhtml char(3) NOT NULL default '',
	  allowmycode char(3) NOT NULL default '',
	  allowimgcode char(3) NOT NULL default '',
	  allowsmilies char(3) NOT NULL default '',
	  PRIMARY KEY(cid)
	) TYPE=MyISAM;");

	$db->query("INSERT INTO ".TABLE_PREFIX."calendars (name,disporder,startofweek,showbirthdays,eventlimit,moderation,allowhtml,allowmycode,allowimgcode,allowsmilies) VALUES ('Default Calendar',1,0,1,4,0,'no','yes','yes','yes');");

	$db->query("CREATE TABLE ".TABLE_PREFIX."calendarpermissions (
	  cid int unsigned NOT NULL default '0',
	  gid int unsigned NOT NULL default '0',
	  canviewcalendar char(3) NOT NULL default '',
	  canaddevents char(3) NOT NULL default '',
	  canbypasseventmod char(3) NOT NULL default '',
	  canmoderateevents char(3) NOT NULL default ''
	) TYPE=MyISAM;");

	$db->query("CREATE TABLE ".TABLE_PREFIX."forumsread (
	  fid int unsigned NOT NULL default '0',
	  uid int unsigned NOT NULL default '0',
	  dateline int(10) NOT NULL default '0',
	  KEY dateline (dateline),
	  UNIQUE KEY fid (fid,uid)
	) TYPE=MyISAM;");


	$contents = "Done</p>";
	$contents .= "<p>Click next to continue with the upgrade process.</p>";
	$output->print_contents($contents);
	$output->print_footer("9_done");
}

function upgrade9_dbchanges5()
{
	global $db, $output;

	$output->print_header("Event Conversion");

	if(!$_POST['eventspage'])
	{
		$epp = 50;
	}
	else
	{
		$epp = $_POST['eventspage'];
	}

	if($_POST['eventstart'])
	{
		$startat = $_POST['eventstart'];
		$upper = $startat+$epp;
		$lower = $startat;
	}
	else
	{
		$startat = 0;
		$upper = $epp;
		$lower = 1;
	}

	$query = $db->query("SELECT COUNT(eid) AS eventcount FROM ".TABLE_PREFIX."events");
	$cnt = $db->fetch_array($query);

	$contents .= "<p>Converting events $lower to $upper (".$cnt['attachcount']." Total)</p>";
	echo "<p>Converting events $lower to $upper (".$cnt['attachcount']." Total)</p>";
	
	// Just started - add fields
	if(!$db->field_exists("donecon", "events"))
	{
		// Add temporary column
		$db->query("ALTER TABLE ".TABLE_PREFIX."events ADD donecon smallint(1) NOT NULL;");

		// Got structural changes?		
		$db->query("ALTER TABLE ".TABLE_PREFIX."events ADD cid int unsigned NOT NULL default '0' AFTER eid");
		$db->query("ALTER TABLE ".TABLE_PREFIX."events CHANGE author uid int unsigned NOT NULL default '0'");
		$db->query("ALTER TABLE ".TABLE_PREFIX."events CHANGE subject name varchar(120) NOT NULL default ''");
		$db->query("ALTER TABLE ".TABLE_PREFIX."events ADD visible int(1) NOT NULL default '0' AFTER description");
		$db->query("UPDATE ".TABLE_PREIX."events SET private=1 WHERE private='yes'");
		$db->query("UPDATE ".TABLE_PREIX."events SET private=0 WHERE private='no'");
		$db->query("ALTER TABLE ".TABLE_PREFIX."events CHANGE private int(1) NOT NULL default '0'");
		$db->query("ALTER TABLE ".TABLE_PREFIX."events ADD dateline int(10) unsigned NOT NULL default '0' AFTER private");
		$db->query("ALTER TABLE ".TABLE_PREFIX."events ADD starttime int(10) unsigned NOT NULL default '0' AFTER dateline");
		$db->query("ALTER TABLE ".TABLE_PREFIX."events ADD endtime int(10) unsigned NOT NULL default '0' AFTER starttime");
		$db->query("ALTER TABLE ".TABLE_PREFIX."events ADD timezone int(3) NOT NULL default '0' AFTER endtime");
		$db->query("ALTER TABLE ".TABLE_PREFIX."events ADD ignoretimezone int(1) NOT NULL default '0' AFTER timezone");
		$db->query("ALTER TABLE ".TABLE_PREFIX."events ADD usingtime int(1) NOT NULL default '0' AFTER dst");
		$db->query("ALTER TABLE ".TABLE_PREFIX."events ADD repeats text NOT NULL AFTER usingtime");
	}

	$query = $db->simple_select("events", "*", "donecon!=1", array("order_by" => "eid", "limit" => $epp));
	while($event = $db->fetch_array($query))
	{
		$e_date = explode("-", $event['date']);
		$starttime = gmmktime(0, 0, 0, $e_date[1], $e_date[0], $e_date[1]);
		$updated_event = array(
			"cid" => 1,
			"visible" => 1,
			"starttime" => $starttime,
			"dateline" => $starttime
		);
	}
	
	echo "<p>Done.</p>";
	$query = $db->query("events", "COUNT(eid) AS remaining", "donecon!=1");
	$remaining = $db->fetch_field($query, "remaining");	
	if($remaining)
	{
		$nextact = "9_dbchanges5";
		$startat = $startat+$epp;
		$contents .= "<p><input type=\"hidden\" name=\"eventspage\" value=\"$epp\" /><input type=\"hidden\" name=\"eventstart\" value=\"$startat\" />Done. Click Next to move on to the next set of events.</p>";
	}
	else
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."events DROP donecon");
		$db->query("ALTER TABLE ".TABLE_PREFIX."events DROP date");
		$nextact = "9_done";
		$contents .= "<p>Done</p><p>All events have been converted to the new calendar system. Click next to continue.</p>";
	}
	$output->print_contents($contents);
	$output->print_footer($nextact);

}
?>