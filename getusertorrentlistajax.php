<?php
require "include/bittorrent.php";
dbconn();
require_once(get_langfile_path());
//Send some headers to keep the user's browser from caching the response.
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" ); 
header("Cache-Control: no-cache, must-revalidate" ); 
header("Pragma: no-cache" );
header("Content-Type: text/xml; charset=utf-8");
function maketable($res, $mode = 'seeding')
{
	global $lang_getusertorrentlistajax,$CURUSER,$smalldescription_main;
	switch ($mode)
	{
		case 'uploaded': {
		$showsize = true;
		$showsenum = true;
		$showlenum = true;
		$showuploaded = true;
		$showdownloaded = false;
		$showratio = false;
		$showsetime = true;
		$showletime = false;
		$showcotime = false;
		$showanonymous = true;
		$columncount = 8;
		break;
		}
		case 'seeding': {
		$showsize = true;
		$showsenum = true;
		$showlenum = true;
		$showuploaded = true;
		$showdownloaded = true;
		$showratio = true;
		$showsetime = false;
		$showletime = false;
		$showcotime = false;
		$showanonymous = false;
		$columncount = 8;
		break;
		}
		case 'leeching': {
		$showsize = true;
		$showsenum = true;
		$showlenum = true;
		$showuploaded = true;
		$showdownloaded = true;
		$showratio = true;
		$showsetime = false;
		$showletime = false;
		$showcotime = false;
		$showanonymous = false;
		$columncount = 8;
		break;
		}
		case 'completed': {
		$showsize = false;
		$showsenum = false;
		$showlenum = false;
		$showuploaded = true;
		$showdownloaded = false;
		$showratio = false;
		$showsetime = true;
		$showletime = true;
		$showcotime = true;
		$showanonymous = false;
		$columncount = 8;
		break;
		}
		case 'incomplete': {
		$showsize = false;
		$showsenum = false;
		$showlenum = false;
		$showuploaded = true;
		$showdownloaded = true;
		$showratio = true;
		$showsetime = false;
		$showletime = true;
		$showcotime = false;
		$showanonymous = false;
		$columncount = 7;
		break;
		}
		default: break;
	}
	$ret = "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\" width=\"800\"><tr><td class=\"colhead\" style=\"padding: 0px\">".$lang_getusertorrentlistajax['col_type']."</td><td class=\"colhead\" align=\"center\">".$lang_getusertorrentlistajax['col_name']."</td>".
	($showsize ? "<td class=\"colhead\" align=\"center\"><img class=\"size\" src=\"pic/trans.gif\" alt=\"size\" title=\"".$lang_getusertorrentlistajax['title_size']."\" /></td>" : "").($showsenum ? "<td class=\"colhead\" align=\"center\"><img class=\"seeders\" src=\"pic/trans.gif\" alt=\"seeders\" title=\"".$lang_getusertorrentlistajax['title_seeders']."\" /></td>" : "").($showlenum ? "<td class=\"colhead\" align=\"center\"><img class=\"leechers\" src=\"pic/trans.gif\" alt=\"leechers\" title=\"".$lang_getusertorrentlistajax['title_leechers']."\" /></td>" : "").($showuploaded ? "<td class=\"colhead\" align=\"center\">".$lang_getusertorrentlistajax['col_uploaded']."</td>" : "") . ($showdownloaded ? "<td class=\"colhead\" align=\"center\">".$lang_getusertorrentlistajax['col_downloaded']."</td>" : "").($showratio ? "<td class=\"colhead\" align=\"center\">".$lang_getusertorrentlistajax['col_ratio']."</td>" : "").($showsetime ? "<td class=\"colhead\" align=\"center\">".$lang_getusertorrentlistajax['col_se_time']."</td>" : "").($showletime ? "<td class=\"colhead\" align=\"center\">".$lang_getusertorrentlistajax['col_le_time']."</td>" : "").($showcotime ? "<td class=\"colhead\" align=\"center\">".$lang_getusertorrentlistajax['col_time_completed']."</td>" : "").($showanonymous ? "<td class=\"colhead\" align=\"center\">".$lang_getusertorrentlistajax['col_anonymous']."</td>" : "")."</tr>\n";
	while ($arr = mysql_fetch_assoc($res))
	{
		$catimage = htmlspecialchars($arr["image"]);
		$catname = htmlspecialchars($arr["catname"]);

		$sphighlight = get_torrent_bg_color($arr['sp_state']);
		$sp_torrent = get_torrent_promotion_append($arr['sp_state']);

		//torrent name
		$dispname = $nametitle = htmlspecialchars($arr["torrentname"]);
		$count_dispname=mb_strlen($dispname,"UTF-8");
		$max_lenght_of_torrent_name=($CURUSER['fontsize'] == 'large' ? 70 : 80);
		if($count_dispname > $max_lenght_of_torrent_name)
			$dispname=mb_substr($dispname, 0, $max_lenght_of_torrent_name,"UTF-8") . "..";
		if ($smalldescription_main == 'yes'){
			//small description
			$dissmall_descr = htmlspecialchars(trim($arr["small_descr"]));
			$count_dissmall_descr=mb_strlen($dissmall_descr,"UTF-8");
			$max_lenght_of_small_descr=80; // maximum length
			if($count_dissmall_descr > $max_lenght_of_small_descr)
			{
				$dissmall_descr=mb_substr($dissmall_descr, 0, $max_lenght_of_small_descr,"UTF-8") . "..";
			}
		}
		else $dissmall_descr == "";
		$ret .= "<tr" .  $sphighlight  . "><td class=\"rowfollow nowrap\" valign=\"middle\" style='padding: 0px'>".return_category_image($arr['category'], "torrents.php?allsec=1&amp;")."</td>\n" .
		"<td class=\"rowfollow\" width=\"100%\" align=\"left\"><a href=\"".htmlspecialchars("details.php?id=".$arr[torrent]."&hit=1")."\" title=\"".$nametitle."\"><b>" . $dispname . "</b></a>". $sp_torrent .($dissmall_descr == "" ? "" : "<br />" . $dissmall_descr) . "</td>";
		//size
		if ($showsize)
			$ret .= "<td class=\"rowfollow\" align=\"center\">". mksize_compact($arr['size'])."</td>";
		//number of seeders
		if ($showsenum)
			$ret .= "<td class=\"rowfollow\" align=\"center\">".$arr['seeders']."</td>";
		//number of leechers
		if ($showlenum)
			$ret .= "<td class=\"rowfollow\" align=\"center\">".$arr['leechers']."</td>";
		//uploaded amount
		if ($showuploaded){
			$uploaded = mksize_compact($arr["uploaded"]);
			$ret .= "<td class=\"rowfollow\" align=\"center\">".$uploaded."</td>";
		}
		//downloaded amount
		if ($showdownloaded){
			$downloaded = mksize_compact($arr["downloaded"]);
			$ret .= "<td class=\"rowfollow\" align=\"center\">".$downloaded."</td>";
		}
		//ratio
		if ($showratio){
			if ($arr['downloaded'] > 0)
			{
				$ratio = number_format($arr['uploaded'] / $arr['downloaded'], 3);
				$ratio = "<font color=\"" . get_ratio_color($ratio) . "\">".$ratio."</font>";
			}
			elseif ($arr['uploaded'] > 0) $ratio = "Inf.";
			else $ratio = "---";
			$ret .= "<td class=\"rowfollow\" align=\"center\">".$ratio."</td>";
		}
		if ($showsetime){
			$ret .= "<td class=\"rowfollow\" align=\"center\">".mkprettytime($arr['seedtime'])."</td>";
		}
		if ($showletime){
			$ret .= "<td class=\"rowfollow\" align=\"center\">".mkprettytime($arr['leechtime'])."</td>";
		}
		if ($showcotime)
			$ret .= "<td class=\"rowfollow\" align=\"center\">"."". str_replace("&nbsp;", "<br />", gettime($arr['completedat'],false)). "</td>";
		if ($showanonymous)
			$ret .= "<td class=\"rowfollow\" align=\"center\">".$arr['anonymous']."</td>";
		$ret .="</tr>\n";
		
	}
	$ret .= "</table>\n";
	return $ret;
}

$id = 0+$_GET['userid'];
$type = $_GET['type'];
if (!in_array($type,array('uploaded','seeding','leeching','completed','incomplete')))
die;
if(get_user_class() < $torrenthistory_class && $id != $CURUSER["id"])
permissiondenied();

switch ($type)
{
	case 'uploaded':
	{
		$res = sql_query("SELECT torrents.id AS torrent, torrents.name as torrentname, small_descr, seeders, leechers, anonymous, categories.name AS catname, categories.image, category, sp_state, size, snatched.seedtime, snatched.uploaded FROM torrents LEFT JOIN snatched ON torrents.id = snatched.torrentid LEFT JOIN categories ON torrents.category = categories.id WHERE torrents.owner=$id AND snatched.userid=$id " . (($CURUSER["id"] != $id)?((get_user_class() < $viewanonymous_class) ? " AND anonymous = 'no'":""):"") ." ORDER BY torrents.added DESC") or sqlerr(__FILE__, __LINE__);
		$count = mysql_num_rows($res);
		if ($count > 0)
		{
			$torrentlist = maketable($res, 'uploaded');
		}
		break;
	}

	// Current Seeding
	case 'seeding':
	{
		$res = sql_query("SELECT torrent,added,snatched.uploaded,snatched.downloaded,torrents.name as torrentname, torrents.small_descr, torrents.sp_state, categories.name as catname,size,image,category,seeders,leechers FROM peers LEFT JOIN torrents ON peers.torrent = torrents.id LEFT JOIN categories ON torrents.category = categories.id LEFT JOIN snatched ON torrents.id = snatched.torrentid WHERE peers.userid=$id AND snatched.userid = $id AND peers.seeder='yes' ORDER BY torrents.added DESC") or sqlerr();
		$count = mysql_num_rows($res);
		if ($count > 0){
			$torrentlist = maketable($res, 'seeding');
		}
		break;
	}

	// Current Leeching
	case 'leeching':
	{
		$res = sql_query("SELECT torrent,snatched.uploaded,snatched.downloaded,torrents.name as torrentname, torrents.small_descr, torrents.sp_state, categories.name as catname,size,image,category,seeders,leechers FROM peers LEFT JOIN torrents ON peers.torrent = torrents.id LEFT JOIN categories ON torrents.category = categories.id LEFT JOIN snatched ON torrents.id = snatched.torrentid WHERE peers.userid=$id AND snatched.userid = $id AND peers.seeder='no' ORDER BY torrents.added DESC") or sqlerr();
		$count = mysql_num_rows($res);
		if ($count > 0){
			$torrentlist = maketable($res, 'leeching');
		}
		break;
	}

	// Completed torrents
	case 'completed':
	{
		$res = sql_query("SELECT torrents.id AS torrent, torrents.name AS torrentname, small_descr, categories.name AS catname, categories.image, category, sp_state, size, snatched.uploaded, snatched.seedtime, snatched.leechtime, snatched.completedat FROM torrents LEFT JOIN snatched ON torrents.id = snatched.torrentid LEFT JOIN categories on torrents.category = categories.id WHERE snatched.finished='yes' AND torrents.owner != $id AND userid=$id ORDER BY snatched.completedat DESC") or sqlerr();
		$count = mysql_num_rows($res);
		if ($count > 0)
		{
			$torrentlist = maketable($res, 'completed');
		}
		break;
	}

	// Incomplete torrents
	case 'incomplete':
	{
		$res = sql_query("SELECT torrents.id AS torrent, torrents.name AS torrentname, small_descr, categories.name AS catname, categories.image, category, sp_state, size, snatched.uploaded, snatched.downloaded, snatched.leechtime FROM torrents LEFT JOIN snatched ON torrents.id = snatched.torrentid LEFT JOIN categories on torrents.category = categories.id WHERE snatched.finished='no' AND userid=$id AND torrents.owner != $id ORDER BY snatched.startdat DESC") or sqlerr();
		$count = mysql_num_rows($res);
		if ($count > 0)
		{
			$torrentlist = maketable($res, 'incomplete');
		}
		break;
	}
	default: 
	{
		$count = 0;
		$torrentlist = "";
		break;
	}
}

if ($count)
echo "<b>".$count."</b>".$lang_getusertorrentlistajax['text_record'].add_s($count)."<br />".$torrentlist;
else
echo $lang_getusertorrentlistajax['text_no_record'];
?>
