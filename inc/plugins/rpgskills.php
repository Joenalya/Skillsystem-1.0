<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB")){die("Direct initialization of this file is not allowed.");}

// hooks
$plugins->add_hook('usercp_start', 'rpgskills_usercp');
$plugins->add_hook('usercp_menu', 'rpgskills_nav', 30);
$plugins->add_hook("member_profile_end", "rpgskills_profile");
$plugins->add_hook ('fetch_wol_activity_end', 'rpgskills_user_activity');
$plugins->add_hook ('build_friendly_wol_location_end', 'rpgskills_location_activity');


function rpgskills_info()
{
    return array(
        "name"            => "Skillsystem (RPG-Plugin)",
        "description"    => "Ermöglicht es Usern für ihren Charakter Skills zu erstellen, diese können im Profil angezeigt werden.",
        "website"        => "https://github.com/Joenalya",
        "author"        => "Joenalya aka. Anne",
        "authorsite"    => "https://github.com/Joenalya",
        "version"        => "1.0",
        "codename"        => "rpgskills",
        "compatibility" => "18"
    );
}

function rpgskills_install()
{
	global $db, $mybb, $cache;
	
	// create database
	$db->query("CREATE TABLE ".TABLE_PREFIX."rpgskills (
	`skillid` int(11) NOT NULL AUTO_INCREMENT,
	`skilluid` varchar(155) NOT NULL,
	`skillname` varchar(155) NOT NULL,
	`skilltype` int(11) NOT NULL,
	`skillgain` int(11) NOT NULL,
	`skillsecret` int(11) NOT NULL,
	PRIMARY KEY (`skillid`),
	KEY `skillid` (`skillid`)
   ) ENGINE=MyISAM".$db->build_create_table_collation());
   
	// create settinggroup
	$setting_group = array(
    	'name' => 'rpgskillscp',
    	'title' => 'Skillsystem',
    	'description' => 'Einstellungen für das Skillsystem.',
    	'disporder' => -1, // The order your setting group will display
    	'isdefault' => 0
	);
	
	// insert settinggroup into database
	$gid = $db->insert_query("settinggroups", $setting_group);
	
	// create settings
	$setting_array = array(
    	'rpgskillscp_activate' => array(
        	'title' => 'Soll das Skillsystem aktiviert werden?',
        	'description' => '',
        	'optionscode' => 'yesno',
        	'value' => '0', // Default
        	'disporder' => 1
    	),
    	'rpgskillscp_mode' => array(
        	'title' => 'Modus',
        	'description' => 'In welchem Modus willst du das Skillsystem benutzten?',
			'optionscode'	=> 'select \n 1=Normal \n 2=Normal + Geheime Skills \n 3=Skills nur sichtbar für Team',
        	'value' => 1, // Default
        	'disporder' => 2
    	),	
    	'rpgskillscp_category' => array(
        	'title' => 'Kategorien',
        	'description' => 'In welche Kategorien sollen die Skills geteilt werden? Bitte mit ", " trennen.',
			'optionscode'	=> 'text',
        	'value' => 'Mental, Physisch', // Default
        	'disporder' => 3
    	),	
    	'rpgskillscp_ranks' => array(
        	'title' => 'Fähigkeitsrang',
        	'description' => 'Welche Fähigkeitsränge sollen genutzt werden? Bitte mit ", " trennen.',
			'optionscode'	=> 'text',
        	'value' => 'Amateur, Fortgeschritten, Professionell, Meister, Paragon, Mythisch', // Default
        	'disporder' => 4
    	),			
	);

	// insert settings into database
	foreach($setting_array as $name => $setting)
	{
    	$setting['name'] = $name;
    	$setting['gid'] = $gid;

    	$db->insert_query('settings', $setting);
	}

	// Don't forget this!
	rebuild_settings();
	
    // templates
    $insert_array = array(
        'title'        => 'rpgskills_nav',
        'template'    => $db->escape_string('<tr><td class="trow1 smalltext"><a href="usercp.php?action=rpgskills" class="usercp_nav_item usercp_nav_options">Skills bearbeiten</a></td></tr>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);	
	
    $insert_array = array(
        'title'        => 'rpgskills_point_active',
        'template'    => $db->escape_string('<td width="0.02%">⚫</td>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);	
	
    $insert_array = array(
        'title'        => 'rpgskills_point_inactive',
        'template'    => $db->escape_string('<td width="0.02%">⚪</td>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);	
	
    $insert_array = array(
        'title'        => 'member_profile_rpgskills',
        'template'    => $db->escape_string('<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder tfixed">
<tr>
<td class="thead"><strong>Skills</strong></td>
</tr>
<tr>
<td class="trow1">
<div style="display: flex;gap: 20px 15px;margin: 15px 0;">{$skills_type_bit}</div>	
</td>
</tr>
</table>
<br />'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);	
	
    $insert_array = array(
        'title'        => 'rpgskills_usercp_bit',
        'template'    => $db->escape_string('<tr>
	<td width="49%">{$name}</td>
	{$skillpoints}
	<td width="0.02%">{$skilldelete}</td>
</tr>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);	
	
    $insert_array = array(
        'title'        => 'member_profile_rpgskills_bit',
        'template'    => $db->escape_string('<tr>
	<td width="49%">{$name}</td>
	{$skillpoints}
	<td width="0.02%">{$skilldelete}</td>
</tr>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);	
	
    $insert_array = array(
        'title'        => 'rpgskills_usercp_types',
        'template'    => $db->escape_string('<fieldset class="trow2" style="flex-grow: 1;">
<legend><strong>{$type}</strong></legend>
<table cellspacing="0" cellpadding="5" width="100%">
<tr>
{$skills_bit}
</tr>
</table>
</fieldset>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);	
	
    $insert_array = array(
        'title'        => 'member_profile_rpgskills_types',
        'template'    => $db->escape_string('<fieldset class="trow2" style="flex-grow: 1;">
<legend><strong>{$type}</strong></legend>
<table cellspacing="0" cellpadding="5" width="100%">
<tr>
{$skills_bit}
</tr>
</table>
</fieldset>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	
    $insert_array = array(
        'title'        => 'rpgskills_usercp_add',
        'template'    => $db->escape_string('<fieldset class="trow2" style="margin-top:15px;">
	<legend><strong>Skill hinzufügen</strong></legend>
	<form id="skilladd" action="usercp.php?action=doskills" method="post">
		<input type="text" name="skillname" id="skillname" class="textbox" size="0" maxlength="255" placeholder="Skillname" style="width: 46%;padding: 3px;">
		<select name="skillgain" required id="skillgain" style="width: 25%">
			<option value="">Level auswählen</option>
			{$rank_select}
		</select>

		<select name="skilltype" required id="skilltype" style="width: 25%">
			<option value="">Typ auswählen</option>
			{$type_select}
		</select>
		<br>{$secretbox}<br>
		<input type="hidden" name="uid" value="{$mybb->user[\'uid\']}">
		<center><input type="submit" name="submit" id="submit" class="button" /></center>
	</form>
</fieldset>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);	
	
    $insert_array = array(
        'title'        => 'rpgskills_usercp',
        'template'    => $db->escape_string('<html>
<head>
<title>{$lang->user_cp} - Skills bearbeiten</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
{$usercpnav}
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>Skills bearbeiten</strong></td>
</tr>
<tr>
<td class="trow1" colspan="{$colspan}">
Hier könnt ihr euer Aufteilung erklären und andere Informationen geben.
	
<div style="display: flex;gap: 20px 15px;margin: 15px 0;">{$skills_type_bit}</div>	
{$skills_add}	
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);		
}

function rpgskills_is_installed()
{
    global $db;
    if($db->table_exists("rpgskills"))
    {
        return true;
    }
    return false;
}

function rpgskills_uninstall() 
{
	global $db, $cache;
	
	// drop database
	$db->query("DROP TABLE ".TABLE_PREFIX."rpgskills");
	
    // drop templates
    $db->delete_query("templates", "title LIKE '%rpgskills%'");
	
	// drop settings
	$db->delete_query('settings', "name LIKE '%rpgskillscp_%'");
	$db->delete_query('settinggroups', "name = 'rpgskillscp'");
}

function rpgskills_activate()
{
    global $mybb;
	
 	// edit templates
 	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_profile", "#".preg_quote('{$awaybit}')."#i", '{$awaybit} {$member_profile_skills}');
}
	
function rpgskills_deactivate()
{
    global $mybb;
	
	// edit templates
	require MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_profile", "#".preg_quote('{$member_profile_skills}')."#i", '', 0);
	  
	// Don't forget this
	rebuild_settings();
}

function rpgskills_usercp() {
	global $mybb, $db, $cache, $plugins, $templates, $theme, $lang, $header, $headerinclude, $footer, $usercpnav;
	
	if($mybb->input['action'] == "rpgskills"){
		
		$rpgskills_active = (int)$mybb->settings['rpgskillscp_activate'];
		
		if($rpgskills_active != "1"){
			 error_no_permission();
		} else {
		
			$mode_type = (int)$mybb->settings['rpgskillscp_mode'];
			
			$skillid = $mybb->user['uid'];
				
			$skill_category = $mybb->settings['rpgskillscp_category'];
			$skill_category = explode(", ", $skill_category);	
			
			$skill_rank = $mybb->settings['rpgskillscp_ranks'];
			$skill_rank = explode(", ", $skill_rank);
			$rank_count = 0;
			foreach ($skill_rank as $rank) {
				$rank_count = $rank_count + 1;
				$rank_select .= "<option value='{$rank_count}'>{$rank}</option>";
			}
			
			$category_count = 0;
			foreach ($skill_category as $cator) {
				$category_count = $category_count + 1;
				$type_select .= "<option value='{$category_count}'>{$cator}</option>";
			}

			$type_count = 0;
			foreach($skill_category as $type) {
				$skills_bit = "";
				$type_count = $type_count + 1;
				
				$query = $db->query("
				SELECT * FROM ".TABLE_PREFIX."rpgskills
				WHERE skilltype LIKE '$type_count'
				AND skilluid LIKE '$skillid'
				ORDER BY skillname ASC");
				while($skill = $db->fetch_array($query)) {
					
					$id = $skill['skillid'];
					
					if($mode_type == "2" && $skill['skillsecret'] == '1') { 
						$name = "{$skill['skillname']}<br><b style=\"font-size: 8px;display: block;margin-top: -2px;text-transform: uppercase;\">Geheimer Skill</b>";
					} else {
						$name = $skill['skillname'];
					}
					
					$skillpoints = "";
					$skillpoints_base = $mybb->settings['rpgskillscp_ranks'];
					$skillpoints_base = explode(", ", $skillpoints_base);
					$skillpoint = $skill['skillgain'];
					$base_count = 0;
					$skillpoint = $skill['skillgain'];					
					foreach ($skillpoints_base as $rank) {
						$base_count = $base_count + 1;
						
                        if($base_count <= $skillpoint){
                            eval("\$skillpoints .= \"".$templates->get("rpgskills_point_active")."\";");
                        } else {
                            eval("\$skillpoints .= \"".$templates->get("rpgskills_point_inactive")."\";");
                        } 

					}
					
					$skilldelete =  "<a href=\"usercp.php?action=rpgskills&delskill={$id}\">x</a>";
					
					eval("\$skills_bit .= \"".$templates->get("rpgskills_usercp_bit")."\";");
				}
				if(empty($skills_bit)) {$skills_bit = "Du hast keine Skills eingetragen.";};
				eval("\$skills_type_bit .= \"".$templates->get("rpgskills_usercp_types")."\";");	
			}
			
			if($mode_type == "2") { $secretbox = "<input type=\"checkbox\" class=\"checkbox\" name=\"skillsecret\" value=\"1\"> Soll dieser Skill nur für das Team sichtbar sein?";}
			eval("\$skills_add = \"".$templates->get("rpgskills_usercp_add")."\";");
			
			
			$delskill = $mybb->input['delskill'];
			if($delskill) {
				$check = $db->fetch_field($db->query("SELECT skilluid FROM ".TABLE_PREFIX."rpgskills WHERE skillid = '$delskill'"), "skilluid");
					  
				if($mybb->user['uid'] == $check){
					$db->delete_query("rpgskills", "skillid = '$delskill'");
					redirect("usercp.php?action=rpgskills");
				}
			}
			
			eval("\$page= \"".$templates->get("rpgskills_usercp")."\";");
			output_page($page);
		}
		
	}
	
	if($mybb->input['action'] == "doskills") {
		
		$skilladd = $mybb->input['skilladd'];
		if($mybb->request_method == "post") {
			
			$skillname = $db->escape_string($_POST['skillname']);
			$skillgain = $db->escape_string($_POST['skillgain']);
			$skilltype = $db->escape_string($_POST['skilltype']);
			$skilluid = $db->escape_string($_POST['uid']);
			$skillsecret = $db->escape_string($_POST['skillsecret']);
			
			$check = $db->fetch_field($db->query("SELECT skillid FROM ".TABLE_PREFIX."rpgskills WHERE skilluid = '$skilluid' AND skillname = '$skillname'"), "skillid");
			
			
			if($mybb->input['skillname'] == "") {
				error("Es muss ein Skill angegeben werden!");
			}
			elseif($mybb->input['skillgain'] == "") {
				error("Es muss ein Level ausgewählt werden!");
			}
			elseif($mybb->input['skilltype'] == "") {
				error("Es muss ein Typ ausgewählt werden!");
			}
			else {
				$new_record = array(
				  "skilluid" => $db->escape_string($skilluid),				
				  "skillname" => $db->escape_string($skillname),
				  "skilltype" => $db->escape_string($skilltype),				  
				  "skillgain" => $db->escape_string($skillgain),
				  "skillsecret" => $db->escape_string($skillsecret)
				);
				
				if($check){
					$db->update_query("rpgskills", $new_record, "skillid = '$check'");
				} else {
					$db->insert_query("rpgskills", $new_record);
				}
				redirect("usercp.php?action=rpgskills");
			}
			
		}
	}	
}

function rpgskills_profile()
{
	global $mybb, $db, $templates, $theme, $parser, $lang, $memprofile, $member_profile_skills;
	
	$skillplayer = $memprofile['uid'];
	$mode_type = (int)$mybb->settings['rpgskillscp_mode'];
	
	if($mode_type == "3" && ($mybb->usergroup['cancp'] != "1" || $skillplayer != $mybb->user['uid'])) {
	} else{
		$skill_category = $mybb->settings['rpgskillscp_category'];
		$skill_category = explode(", ", $skill_category);	
		
		$type_count = 0;
		foreach($skill_category as $type) {
			$skills_bit = "";
			$type_count = $type_count + 1;

			if($mode_type == "2"){
				if($mybb->usergroup['cancp'] != "1" || $skillplayer != $mybb->user['uid']) {
					$skillsql = "AND skillsecret NOT LIKE '1'";
				} else {
					$skillsql = "";
				}
			};
			
			$query = $db->query("
			SELECT * FROM ".TABLE_PREFIX."rpgskills
			WHERE skilltype LIKE '$type_count'
			AND skilluid LIKE '$skillplayer'
			{$skillsql}
			ORDER BY skillname ASC");
			while($skill = $db->fetch_array($query)) {
						
				$id = $skill['skillid'];
				
				if($mode_type == "2" && $skill['skillsecret'] == '1') { 
					$name = "{$skill['skillname']}<br><b style=\"font-size: 8px;display: block;margin-top: -2px;text-transform: uppercase;\">Geheimer Skill</b>";
				} else {
					$name = $skill['skillname'];
				}
				
				$skillpoints = "";
				$skillpoints_base = $mybb->settings['rpgskillscp_ranks'];
				$skillpoints_base = explode(", ", $skillpoints_base);
				$skillpoint = $skill['skillgain'];
				$base_count = 0;
				$skillpoint = $skill['skillgain'];					
				
				foreach ($skillpoints_base as $rank) {
					$base_count = $base_count + 1;
					if($base_count <= $skillpoint){
						eval("\$skillpoints .= \"".$templates->get("rpgskills_point_active")."\";");
					} else {
						eval("\$skillpoints .= \"".$templates->get("rpgskills_point_inactive")."\";");
					} 

				}	
				eval("\$skills_bit .= \"".$templates->get("member_profile_rpgskills_bit")."\";");
			}
			if(empty($skills_bit)) {$skills_bit = "{$memprofile['username']} hat keine Skills eingetragen.";};
			eval("\$skills_type_bit .= \"".$templates->get("member_profile_rpgskills_types")."\";");	
		}
		eval("\$member_profile_skills = \"".$templates->get("member_profile_rpgskills")."\";");
	}
}

function rpgskills_user_activity($user_activity)
{
    global $user;

    if (my_strpos ($user['location'], "usercp.php?action=rpgskills") !== false) {
        $user_activity['activity'] = "rpgskills";
    }

    return $user_activity;
}

function rpgskills_location_activity($plugin_array)
{
    global $db, $mybb, $lang;

    if ($plugin_array['user_activity']['activity'] == "rpgskills") {
        $plugin_array['location_name'] = "Bearbeitet die eigenen <b><a href='usercp.php?action=rpgskills'>Skills</a></b>.";
    }

    return $plugin_array;
}

function rpgskills_nav() {
	global $mybb, $templates, $lang, $usercpmenu;
	eval("\$usercpmenu .= \"".$templates->get("rpgskills_nav")."\";");
}
?>
