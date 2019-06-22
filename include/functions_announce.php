<?php
# IMPORTANT: Do not edit below unless you know what you are doing!
if(!defined('IN_TRACKER'))
	die('Hacking attempt!');
include_once($rootpath . 'include/globalfunctions.php');
include_once($rootpath . 'include/config.php');

function dbconn_announce() {
	global $mysql_host, $mysql_user, $mysql_pass, $mysql_db;

	if (!@mysql_connect($mysql_host, $mysql_user, $mysql_pass))
	{
		die('dbconn: mysql_connect: ' . mysql_error());
	}
	mysql_query("SET NAMES UTF8");
	mysql_query("SET collation_connection = 'utf8_general_ci'");
	mysql_query("SET sql_mode=''");
	mysql_select_db($mysql_db) or die('dbconn: mysql_select_db: ' + mysql_error());
}

function hash_where_arr($name, $hash_arr) {
	$new_hash_arr = Array();
	foreach ($hash_arr as $hash) {
		$new_hash_arr[] = sqlesc((urldecode($hash)));
	}
	return $name." IN ( ".implode(", ",$new_hash_arr)." )";
}

function emu_getallheaders() {
	foreach($_SERVER as $name => $value)
		if(substr($name, 0, 5) == 'HTTP_')
			$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
	return $headers;
}

function block_browser()
{
	$agent = $_SERVER["HTTP_USER_AGENT"];
	if (preg_match("/^Mozilla/", $agent) || preg_match("/^Opera/", $agent) || preg_match("/^Links/", $agent) || preg_match("/^Lynx/", $agent) )
		err("Browser access blocked!");
// check headers
	if (function_exists('getallheaders')){ //getallheaders() is only supported when PHP is installed as an Apache module
		$headers = getallheaders();
	//else
	//	$headers = emu_getallheaders();

	if($_SERVER["HTTPS"] != "on")
	{
		if (isset($headers["Cookie"]) || isset($headers["Accept-Language"]) || isset($headers["Accept-Charset"]))
			err("Anti-Cheater: You cannot use this agent");
	}
	}
}

function benc_resp($d)
{
	benc_resp_raw(benc(array('type' => 'dictionary', 'value' => $d)));
}
function benc_resp_raw($x) {

	header("Content-Type: text/plain; charset=utf-8");
	header("Pragma: no-cache");

	if ($_SERVER["HTTP_ACCEPT_ENCODING"] == "gzip" && function_exists('gzencode')) {
		header("Content-Encoding: gzip");
		echo gzencode($x, 9, FORCE_GZIP);
	} 
	else
		echo $x;
}
function err($msg, $userid = 0, $torrentid = 0)
{
	benc_resp(array('failure reason' => array('type' => 'string', 'value' => $msg)));
	exit();
}
function check_cheater($userid, $torrentid, $uploaded, $downloaded, $anctime, $seeders=0, $leechers=0){
	global $cheaterdet_security,$nodetect_security;

	$time = date("Y-m-d H:i:s");
	$upspeed = ($uploaded > 0 ? $uploaded / $anctime : 0);

	if ($uploaded > 1073741824 && $upspeed > (104857600/$cheaterdet_security)) //Uploaded more than 1 GB with uploading rate higher than 100 MByte/S (For Consertive level). This is no doubt cheating.
	{
		$comment = "User account was automatically disabled by system";
		mysql_query("INSERT INTO cheaters (added, userid, torrentid, uploaded, downloaded, anctime, seeders, leechers, comment) VALUES (".sqlesc($time).", $userid, $torrentid, $uploaded, $downloaded, $anctime, $seeders, $leechers, ".sqlesc($comment).")") or err("Tracker error 51");
		mysql_query("UPDATE users SET enabled = 'no' WHERE id=$userid") or err("Tracker error 50"); //automatically disable user account;
		err("We believe you're trying to cheat. And your account is disabled.");
		return true;
	}
	if ($uploaded > 1073741824 && $upspeed > (10485760/$cheaterdet_security)) //Uploaded more than 1 GB with uploading rate higher than 10 MByte/S (For Consertive level). This is likely cheating.
	{
		$secs = 24*60*60; //24 hours
		$dt = sqlesc(date("Y-m-d H:i:s",(strtotime(date("Y-m-d H:i:s")) - $secs))); // calculate date.
		$countres = mysql_query("SELECT id FROM cheaters WHERE userid=$userid AND torrentid=$torrentid AND added > $dt");
		if (mysql_num_rows($countres) == 0)
		{
			$comment = "Abnormally high uploading rate";
			mysql_query("INSERT INTO cheaters (added, userid, torrentid, uploaded, downloaded, anctime, seeders, leechers, hit, comment) VALUES (".sqlesc($time).", $userid, $torrentid, $uploaded, $downloaded, $anctime, $seeders, $leechers, 1,".sqlesc($comment).")") or err("Tracker error 52");
		}
		else{
			$row = mysql_fetch_row($countres);
			mysql_query("UPDATE cheaters SET hit=hit+1, dealtwith = 0 WHERE id=".$row[0]);
		}
		//mysql_query("UPDATE users SET downloadpos = 'no' WHERE id=$userid") or err("Tracker error 53"); //automatically remove user's downloading privileges;
		return false;
	}
if ($cheaterdet_security > 1){// do not check this with consertive level
	if ($uploaded > 1073741824 && $upspeed > 1048576 && $leechers < (2 * $cheaterdet_security)) //Uploaded more than 1 GB with uploading rate higher than 1 MByte/S when there is less than 8 leechers (For Consertive level). This is likely cheating.
	{
		$secs = 24*60*60; //24 hours
		$dt = sqlesc(date("Y-m-d H:i:s",(strtotime(date("Y-m-d H:i:s")) - $secs))); // calculate date.
		$countres = mysql_query("SELECT id FROM cheaters WHERE userid=$userid AND torrentid=$torrentid AND added > $dt");
		if (mysql_num_rows($countres) == 0)
		{
			$comment = "User is uploading fast when there is few leechers";
			mysql_query("INSERT INTO cheaters (added, userid, torrentid, uploaded, downloaded, anctime, seeders, leechers, comment) VALUES (".sqlesc($time).", $userid, $torrentid, $uploaded, $downloaded, $anctime, $seeders, $leechers, ".sqlesc($comment).")") or err("Tracker error 52");
		}
		else
		{
			$row = mysql_fetch_row($countres);
			mysql_query("UPDATE cheaters SET hit=hit+1, dealtwith = 0 WHERE id=".$row[0]);
		}
		//mysql_query("UPDATE users SET downloadpos = 'no' WHERE id=$userid") or err("Tracker error 53"); //automatically remove user's downloading privileges;
		return false;
	}
	if ($uploaded > 10485760 && $upspeed > 102400 && $leechers == 0) //Uploaded more than 10 MB with uploading speed faster than 100 KByte/S when there is no leecher. This is likely cheating.
	{
		$secs = 24*60*60; //24 hours
		$dt = sqlesc(date("Y-m-d H:i:s",(strtotime(date("Y-m-d H:i:s")) - $secs))); // calculate date.
		$countres = mysql_query("SELECT id FROM cheaters WHERE userid=$userid AND torrentid=$torrentid AND added > $dt");
		if (mysql_num_rows($countres) == 0)
		{
			$comment = "User is uploading when there is no leecher";
			mysql_query("INSERT INTO cheaters (added, userid, torrentid, uploaded, downloaded, anctime, seeders, leechers, comment) VALUES (".sqlesc($time).", $userid, $torrentid, $uploaded, $downloaded, $anctime, $seeders, $leechers, ".sqlesc($comment).")") or err("Tracker error 52");
		}
		else
		{
			$row = mysql_fetch_row($countres);
			mysql_query("UPDATE cheaters SET hit=hit+1, dealtwith = 0 WHERE id=".$row[0]);
		}
		//mysql_query("UPDATE users SET downloadpos = 'no' WHERE id=$userid") or err("Tracker error 53"); //automatically remove user's downloading privileges;
		return false;
	}
}
	return false;
}
function portblacklisted($port)
{
	// direct connect
	if ($port >= 411 && $port <= 413) return true;
	// bittorrent
	if ($port >= 6881 && $port <= 6889) return true;
	// kazaa
	if ($port == 1214) return true;
	// gnutella
	if ($port >= 6346 && $port <= 6347) return true;
	// emule
	if ($port == 4662) return true;
	// winmx
	if ($port == 6699) return true;
	return false;
}

function ipv4_to_compact($ip, $port)
{
	$compact = pack("Nn", sprintf("%d",ip2long($ip)), $port);
	return $compact;
}

function check_client($peer_id, $agent, &$agent_familyid)
{
	global $BASEURL, $Cache;

	if (!$clients = $Cache->get_value('allowed_client_list')){
		$clients = array();
		$res = mysql_query("SELECT * FROM agent_allowed_family ORDER BY hits DESC") or err("check err");
		while ($row = mysql_fetch_array($res))
			$clients[] = $row;
		$Cache->cache_value('allowed_client_list', $clients, 86400);
	}
	foreach ($clients as $row_allowed_ua)
	{
		$allowed_flag_peer_id = false;
		$allowed_flag_agent = false;
		$version_low_peer_id = false;
		$version_low_agent = false;

		if($row_allowed_ua['peer_id_pattern'] != '')
		{
			if(!preg_match($row_allowed_ua['peer_id_pattern'], $row_allowed_ua['peer_id_start'], $match_bench))
			err("regular expression err for: " . $row_allowed_ua['peer_id_start'] . ", please ask sysop to fix this");

			if(preg_match($row_allowed_ua['peer_id_pattern'], $peer_id, $match_target))
			{
				if($row_allowed_ua['peer_id_match_num'] != 0)
				{
					for($i = 0 ; $i < $row_allowed_ua['peer_id_match_num']; $i++)
					{
						if($row_allowed_ua['peer_id_matchtype'] == 'dec')
						{
							$match_target[$i+1] = 0 + $match_target[$i+1];
							$match_bench[$i+1] = 0 + $match_bench[$i+1];
						}
						else if($row_allowed_ua['peer_id_matchtype'] == 'hex')
						{
							$match_target[$i+1] = hexdec($match_target[$i+1]);
							$match_bench[$i+1] = hexdec($match_bench[$i+1]);
						}

						if ($match_target[$i+1] > $match_bench[$i+1])
						{
							$allowed_flag_peer_id = true;
							break;
						}
						else if($match_target[$i+1] < $match_bench[$i+1])
						{
							$allowed_flag_peer_id = false;
							$version_low_peer_id = true;
							$low_version = "Your " . $row_allowed_ua['family'] . " 's version is too low, please update it after " . $row_allowed_ua['start_name'];
							break;
						}
						else if($match_target[$i+1] == $match_bench[$i+1])//equal
						{
							if($i+1 == $row_allowed_ua['peer_id_match_num'])		//last
							{
								$allowed_flag_peer_id = true;
							}
						}
					}
				}
				else // no need to compare version
				$allowed_flag_peer_id = true;
			}
		}
		else	// not need to match pattern
		$allowed_flag_peer_id = true;

		if($row_allowed_ua['agent_pattern'] != '')
		{
			if(!preg_match($row_allowed_ua['agent_pattern'], $row_allowed_ua['agent_start'], $match_bench))
			err("regular expression err for: " . $row_allowed_ua['agent_start'] . ", please ask sysop to fix this");

			if(preg_match($row_allowed_ua['agent_pattern'], $agent, $match_target))
			{
				if( $row_allowed_ua['agent_match_num'] != 0)
				{
					for($i = 0 ; $i < $row_allowed_ua['agent_match_num']; $i++)
					{
						if($row_allowed_ua['agent_matchtype'] == 'dec')
						{
							$match_target[$i+1] = 0 + $match_target[$i+1];
							$match_bench[$i+1] = 0 + $match_bench[$i+1];
						}
						else if($row_allowed_ua['agent_matchtype'] == 'hex')
						{
							$match_target[$i+1] = hexdec($match_target[$i+1]);
							$match_bench[$i+1] = hexdec($match_bench[$i+1]);
						}

						if ($match_target[$i+1] > $match_bench[$i+1])
						{
							$allowed_flag_agent = true;
							break;
						}
						else if($match_target[$i+1] < $match_bench[$i+1])
						{
							$allowed_flag_agent = false;
							$version_low_agent = true;
							$low_version = "Your " . $row_allowed_ua['family'] . " 's version is too low, please update it after " . $row_allowed_ua['start_name'];
							break;
						}
						else //equal
						{
							if($i+1 == $row_allowed_ua['agent_match_num'])		//last
							$allowed_flag_agent = true;
						}
					}
				}
				else // no need to compare version
				$allowed_flag_agent = true;
			}
		}
		else
		$allowed_flag_agent = true;

		if($allowed_flag_peer_id && $allowed_flag_agent)
		{
			$exception = $row_allowed_ua['exception'];
			$family_id = $row_allowed_ua['id'];
			$allow_https = $row_allowed_ua['allowhttps'];
			break;
		}
		elseif(($allowed_flag_peer_id || $allowed_flag_agent) || ($version_low_peer_id || $version_low_agent))	//client spoofing possible
		;//add anti-cheat code here
	}

	if($allowed_flag_peer_id && $allowed_flag_agent)
	{
		if($exception == 'yes')
		{
			if (!$clients_exp = $Cache->get_value('allowed_client_exception_family_'.$family_id.'_list')){
				$clients_exp = array();
				$res = mysql_query("SELECT * FROM agent_allowed_exception WHERE family_id = $family_id") or err("check err");
				while ($row = mysql_fetch_array($res))
					$clients_exp[] = $row;
				$Cache->cache_value('allowed_client_exception_family_'.$family_id.'_list', $clients_exp, 86400);
			}
			if($clients_exp)
			{
				foreach ($clients_exp as $row_allowed_ua_exp)
				{
					if($row_allowed_ua_exp['agent'] == $agent && preg_match("/^" . $row_allowed_ua_exp['peer_id'] . "/", $peer_id))
					return "Client " . $row_allowed_ua_exp['name'] . " is banned due to: " . $row_allowed_ua_exp['comment'] . ".";
				}
			}
			$agent_familyid = $row_allowed_ua['id'];
		}
		else
		{
			$agent_familyid = $row_allowed_ua['id'];
		}

		if($_SERVER["HTTPS"] == "on")
		{
			if($allow_https == 'yes')
			return 0;
			else
			return "This client does not support https well, Please goto $BASEURL/faq.php#id29 for a list of proper clients";
		}
		else
		return 0;	// no exception found, so allowed or just allowed
	}
	else
	{
		if($version_low_peer_id && $version_low_agent)
		return $low_version;
		else
		return "Banned Client, Please goto $BASEURL/faq.php#id29 for a list of acceptable clients";
	}
}
?>
