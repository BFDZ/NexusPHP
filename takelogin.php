<?php
require_once("include/bittorrent.php");
header("Content-Type: text/html; charset=utf-8");
if (!mkglobal("username:password"))
	die();
dbconn();
require_once(get_langfile_path("", false, get_langfolder_cookie()));
failedloginscheck ();
cur_user_check () ;

function bark($text = "")
{
  global $lang_takelogin;
  $text =  ($text == "" ? $lang_takelogin['std_login_fail_note'] : $text);
  stderr($lang_takelogin['std_login_fail'], $text,false);
}
if ($iv == "yes")
	check_code ($_POST['imagehash'], $_POST['imagestring'],'login.php',true);
$res = sql_query("SELECT id, passhash, secret, enabled, status FROM users WHERE username = " . sqlesc($username));
$row = mysql_fetch_array($res);

if (!$row)
	failedlogins();
if ($row['status'] == 'pending')
	failedlogins($lang_takelogin['std_user_account_unconfirmed']);

if ($row["passhash"] != md5($row["secret"] . $password . $row["secret"]))
	login_failedlogins();

if ($row["enabled"] == "no")
	bark($lang_takelogin['std_account_disabled']);

if ($_POST["securelogin"] == "yes")
{
	$securelogin_indentity_cookie = true;
	$passh = md5($row["passhash"].$_SERVER["REMOTE_ADDR"]);
}
else
{
	$securelogin_indentity_cookie = false;
	$passh = md5($row["passhash"]);
}

if ($securelogin=='yes' || $_POST["ssl"] == "yes")
{
	$pprefix = "https://";
	$ssl = true;
}
else
{
	$pprefix = "http://";
	$ssl = false;
}
if ($securetracker=='yes' || $_POST["trackerssl"] == "yes")
{
	$trackerssl = true;
}
else
{
	$trackerssl = false;
}
if ($_POST["logout"] == "yes")
{
	logincookie($row["id"], $passh,1,900,$securelogin_indentity_cookie, $ssl, $trackerssl);
	//sessioncookie($row["id"], $passh,true);
}
else 
{
	logincookie($row["id"], $passh,1,0x7fffffff,$securelogin_indentity_cookie, $ssl, $trackerssl);
	//sessioncookie($row["id"], $passh,false);
}

if (!empty($_POST["returnto"]))
	header("Location: " . $pprefix . "$BASEURL/$_POST[returnto]");
else
	header("Location: " . $pprefix . "$BASEURL/index.php");
?>
