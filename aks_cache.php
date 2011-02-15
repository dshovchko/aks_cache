<?php
 
#$plugin['name'] = 'aks_cache';
$plugin['version'] = '0.2.7f';
$plugin['author'] = 'makss, Dmitry Shovchko';
$plugin['author_uri'] = 'http://github.com/dshovchko/aks_cache';
$plugin['description'] = 'Partial caching web pages';
$plugin['type'] = '1';
$plugin['allow_html_help'] = '1';
 
if (!defined('txpinterface'))
	@include_once('zem_tpl.php');
 
if(0){
?>
# --- BEGIN PLUGIN HELP ---
<div id='aks'>
<div style='text-align:right;'><a href='?event=aks_cache_tab'>Plugin preferences</a><br />
<a href="http://textpattern.org.ua/plugins/aks_cache">Homepage</a></div>
<h1>aks_cache - partial caching web pages</h1>
<br />
    <h2>Summary</h2>

    <p>Caching all inside <code>&lt;txp:aks_cache id="unique block id"&gt;</code> Any content with TxP tags <code>&lt;/txp:aks_cache&gt;</code></p>

    <p>Good practice:
    </p><ul>
        <li>Cache some menu elements; recent articles list; last added articles list; any popular lists.</li>
        <li>Cache <code>cloud</code> tags</li>
        <li>Cache site <code>main page</code> or cache some ‘slow’ pages with many <span class="caps">TXP</span> tags.</li>

        <li>Cache output some slow or difficult tags.</li>
    </ul>

    <p>Bad practice:
    </p><ul>
        <li>Cache static content without TxP(or php include) tags</li>
        <li>Full cache every page</li>
        <li>Cache static <code>body</code> every page</li>

    </ul>

    <h2>Installation</h2>

    <ol>
        <li>Install this plugin.</li>
        <li>Go to Extension / <a href="?event=aks_cache_tab">aks_cache Tab</a></li>
    </ol>

    <p></p>


    <h2>Tags</h2>
    <p><code>&lt;txp:aks_cache&gt;</code> – Uses in page templates, forms, articles.</p>
    <table>
        <tbody><tr>
            <th>attributes</th>
            <th>default</th>
            <th>sample</th>
            <th>description</th>
        </tr>
        <tr>
            <td>id</td>
            <td><code>["REQUEST_URI"]</code></td>
            <td>‘’</td>
            <td>Unique ID for each cache block</td>
        </tr>
        <tr>
            <td>block</td>
            <td>‘’</td>
            <td>‘’</td>
            <td>for using without <code>id</code> attribute</td>
        </tr>
        <tr>
            <td>hour</td>
            <td>0</td>
            <td>‘’</td>
            <td>Cache time = hour*60+min</td>
        </tr>
        <tr>
            <td>min</td>
            <td>0</td>
            <td>‘’</td>
            <td>—//—</td>
        </tr>
        <tr>
            <td>noreset</td>
            <td>0</td>
            <td>1</td>
            <td>No reset cache for this block – ignore option “Reset cache if site was updated”</td>
        </tr>
        <tr>
            <td>disable</td>
            <td>0</td>
            <td>1</td>
            <td>Disable cache</td>
        </tr>
        <tr>

            <td>debug</td>
            <td>0</td>
            <td>1</td>
            <td>0 or 1</td>
        </tr>
    </tbody></table><br />

    <p><code>&lt;txp:aks_cache_disable /&gt;</code> – Used <strong><span class="caps">ONLY</span></strong> inside <code>&lt;txp:aks_cache&gt;</code> … <code>&lt;/txp:aks_cache&gt;</code> block.</p>


    <h2>Example</h2>

<pre>&lt;txp:aks_cache id="block1"&gt;Some content and txp tags...&lt;/txp:aks_cache&gt;
</pre>

    <p>Cache menu elements per section</p>

<pre>&lt;txp:aks_cache id='menu &lt;txp:section /&gt;'&gt;
&lt;h3&gt;Tags cloud&lt;/h3&gt;
&lt;rss_uc_cloud section='&lt;txp:section /&gt;' /&gt;
&lt;h3&gt;Last added articles&lt;/h3&gt;
&lt;txp:article_custom form="tf_excerpt" limit="20" section='&lt;txp:section /&gt;' sort="LastMod desc" /&gt;
&lt;/txp:aks_cache&gt;</pre>
</div>
<!-- *** BEGIN PLUGIN CSS *** -->
<style type="text/css">
#aks h1 { color: #000000; font: 20px sans-serif;}
#aks h2 { border-bottom: 1px solid black; padding:10px 0 0; color: #000000; font: 15px sans-serif; }
#aks table {width: 100%; border:1px solid; border-color:#ddd #000 #000 #ddd;}
#aks th {
    background-color: #E3E3DB;
    border:1px solid;
    border-color:#ddd #999 #888 #ddd;
    padding: 10px 1px 10px 1px;
}
#aks td { background-color: #F2F2ED; padding: 5px 1px 5px 1px;}

#aks pre { padding: 5px;
line-height: 1.6em;
font-family: Verdana, Arial;
font-size: 100%;
border: 1px dashed #000000;
margin: 1.5em 0;
}
</style>
<!-- *** END PLUGIN CSS *** -->

# --- END PLUGIN HELP ---
<?php
}
# --- BEGIN PLUGIN CODE ---
if(@txpinterface == 'admin') {
  add_privs('aks_cache_tab', '1,2');
  register_tab("extensions", "aks_cache_tab", "aks_cache");
  register_callback("aks_cache_tab", "aks_cache_tab");
  add_privs('plugin_prefs.aks_cache','1,2');
  register_callback('aks_cache_tab', 'plugin_prefs.aks_cache');
}

function aks_cache_disable(){ global $aks_cache_disable; $aks_cache_disable=1; }

function _aks_is_really_writable($file)
{	
	// If we're on a Unix server with safe_mode off we call is_writable
	if (DIRECTORY_SEPARATOR == '/' AND @ini_get("safe_mode") == FALSE)
	{
		return is_writable($file);
	}

	// For windows servers and safe_mode "on" installations we'll actually
	// write a file then read it.  Bah...
	if (is_dir($file))
	{
		$file = rtrim($file, '/').'/'.md5(rand(1,100));

		if (($fp = @fopen($file, 'ab')) === FALSE)
		{
			return FALSE;
		}

		fclose($fp);
		@chmod($file, 0777);
		@unlink($file);
		return TRUE;
	}
	elseif (($fp = @fopen($file, 'ab')) === FALSE)
	{
		return FALSE;
	}

	fclose($fp);
	return TRUE;
}

function _aks_cache_write($hid, $data, $expire = 0)
{
	global $prefs;

	$cache_path = txpath.$prefs['aks_cache_path'];
	
	if ( ! is_dir($cache_path) OR ! _aks_is_really_writable($cache_path))
	{
		return;
	}
	
	$cache_path .= $hid;

	if ( ! $fp = @fopen($cache_path, 'wb'))
	{
		echo "<!--Unable to write cache file: ".$cache_path."-->";
		return;
	}
	
	if ($expire == 0)
	{
		$expire = time() + ($prefs['aks_cache_time'] * 60);
	}
		
	if (flock($fp, LOCK_EX))
	{
		fwrite($fp, $expire.'TS--->'.$data);
		flock($fp, LOCK_UN);
	}
	else
	{
		echo "<!--Unable to secure a file lock for file at: ".$cache_path."-->";
		return;
	}
	fclose($fp);
	@chmod($cache_path, 0777);

}

function _aks_cache_read($hid)
{
	global $prefs;

	$cache_path = txpath.$prefs['aks_cache_path'];
	
	if ( ! is_dir($cache_path) OR ! _aks_is_really_writable($cache_path))
	{
		return false;
	}
	
	$filepath = $cache_path.$hid;
	
	if ( ! @file_exists($filepath))
	{
		return false;
	}
	
	if ( ! $fp = @fopen($filepath, 'rb'))
	{
		return false;
	}
	
	flock($fp, LOCK_SH);
	
	$cache = '';
	if (filesize($filepath) > 0)
	{
		$cache = fread($fp, filesize($filepath));
	}
	
	flock($fp, LOCK_UN);
	fclose($fp);

	// Strip out the embedded timestamp
	if ( ! preg_match("/(\d+TS--->)/", $cache, $match))
	{
		return false;
	}
	
	// Has the file expired? If so we'll delete it.
	if (time() >= trim(str_replace('TS--->', '', $match['1'])))
	{ 		
		@unlink($filepath);
		return false;
	}

	return str_replace($match['0'], '', $cache);
}

function _aks_cache_reset($clear = false)
{
	global $prefs;

	$cache_path = txpath.$prefs['aks_cache_path'];
	
	if ( ! is_dir($cache_path) OR ! _aks_is_really_writable($cache_path))
	{
		return;
	}
	
	$dirHandle = @opendir($cache_path);
	
	while( false !== ( $file = @readdir($dirHandle) ) )
	{
		if ($clear === false && substr($file, 0, 1) == '@')
		{
			continue;
		}
		if ( $file != '.' && $file != '..' && $file != 'index.html')
		{
			$tmpPath = $cache_path.$file;
			
			if ( ! is_dir($tmpPath) )
			{
				@unlink($tmpPath);
			}
		}
	}
	
	closedir($dirHandle);
}

function aks_cache($atts,$thing='') {
	global $prefs, $aks_cache_disable;
	extract(lAtts(array(
		'block'		=> '',
		'id'		=> $_SERVER["REQUEST_URI"],
		'hour'		=> 0,
		'min'		=> 0,
		'noreset'	=> '',
		'disable'	=> 0,
		'debug'		=> ((strpos($prefs['aks_cache_opt'],'debug')===false)? 0:1)
	),$atts));

        $start = getmicrotime();
	if( !(strpos($prefs['aks_cache_opt'],'disable')===false) || $disable){ return parse($thing); }
	$prr=is_logged_in();
	if( !(strpos($prefs['aks_cache_opt'],'dis_adm')===false)&& $prr['privs']==1 ){ return parse($thing); }
	if( !(strpos($prefs['aks_cache_opt'],'dis_users')===false)&& $prr ){ return parse($thing); }

	if( !(strpos($prefs['aks_cache_opt'],'reset')===false)&& ($prefs['aks_cache_site_lastmod']!=$prefs['lastmod']) ){
		safe_update("txp_prefs", "val='".$prefs['lastmod']."'", "name='aks_cache_site_lastmod'");
		if ( strpos($prefs['aks_cache_opt'],'infile')===false )
		{
			safe_delete("aks_cache","infos not like 'noreset-%'");
		}
		else
		{
			_aks_cache_reset();
		}
		$prefs['aks_cache_site_lastmod']=$prefs['lastmod'];
	}
	if($noreset){ $noreset='noreset-'; }
	$diff=$hour*60+$min;
	if(!$diff){
		$diff=$prefs['aks_cache_time'];
	}
	$ttl2=time()+$diff*60;
	$hash=md5($noreset.$block.$id);
	$id2=mysql_real_escape_string($noreset.$block.$id);
	$act="from cache";

	if ( strpos($prefs['aks_cache_opt'],'infile')===false )
	{
		$rs = safe_row("ttl, data", "aks_cache", "hid='$hash'");
		
		if($rs){
			extract($rs);
			if($ttl<time() ){
				$data=parse($thing);
				$data2=mysql_real_escape_string($data);
				$act="update cache";
				safe_update("aks_cache", "ttl=$ttl2, data='$data2', infos='$id2|$diff|".strlen($data)."'", "hid='$hash'");
			}
		}else{
			$data=parse($thing);
			$data2=mysql_real_escape_string($data);
			$act="insert into cache";
			safe_insert("aks_cache", "hid='$hash', ttl=$ttl2, data='$data2', infos='$id2|$diff|".strlen($data)."'");
		}
	}
	else
	{
                if($noreset) {
		    $hash = "@".$hash;
		}
		if (($data = _aks_cache_read($hash)) === false){
			$data=parse($thing);
			_aks_cache_write($hash, $data, $ttl2);
			$act="insert into cache";
		}
	}
	
	$time = getmicrotime() - $start;
	if($debug){return "<!--txp:aks_cache block='$block' id='$id' action='$act' ttl='$diff minutes' hash='$hash' -->".$data."<!--/txp:aks_cache ($time)-->"; }
	return $data;
}


function aks_cache_tab($event, $step) {
	global $prefs;
	
	$tcheck = getRows("SHOW TABLES LIKE '".PFX."aks_cache'");
	if(!$tcheck) {
		$qc="CREATE TABLE `".PFX."aks_cache` (";
		$qc.= <<<EOF
		`hid` binary(32) NOT NULL default '',
		`ttl` bigint(20) unsigned default '0',
		`data` mediumtext,
		`infos` varchar(255) default '',
		PRIMARY KEY  (`hid`),
		KEY `ind_ttl` (`ttl`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;
EOF;
		safe_query($qc);
	}
	
	if(!isset($prefs['aks_cache_time'])){
		$rs = safe_insert('txp_prefs', "name='aks_cache_time', val='60', prefs_id='1'");
	}
	$isnw=false;
	if(!isset($prefs['aks_cache_path'])){
		$rs = safe_insert('txp_prefs', "name='aks_cache_path', val='/aks_cache/', prefs_id='1'");
	} else {
		$cache_path=txpath.$prefs['aks_cache_path'];
		if ( !is_dir($cache_path) OR !_aks_is_really_writable($cache_path))
		{
		    $isnw=true;
		}
	}
	if(!isset($prefs['aks_cache_opt'])){
		$rs = safe_insert('txp_prefs', "name='aks_cache_opt',val='', prefs_id='1'");
		$qq='';
	}else {
		$qq=$prefs['aks_cache_opt'];
	}
	if(!isset($prefs['aks_cache_site_lastmod'])){
		$rs = safe_insert('txp_prefs', "name='aks_cache_site_lastmod', val='', prefs_id='1'");
	}

	safe_delete("aks_cache","ttl<UNIX_TIMESTAMP()");

        if (ps("save")) {
		pagetop("aks_cache Prefs", "Preferences Saved");
		safe_update("txp_prefs", "val = '".addslashes(ps('aks_cache_time'))."'","name = 'aks_cache_time' and prefs_id ='1'");
		$path=ps('aks_cache_path');
		if (substr($path,0,1) != "/") $path = "/".$path;
		if (substr($path,-1) != "/") $path .= "/";
		safe_update("txp_prefs", "val = '".addslashes($path)."'","name = 'aks_cache_path' and prefs_id ='1'");
		if(isset($_POST['aks_cache_opt'])){ $opt=implode(',',$_POST['aks_cache_opt']); }else{ $opt=""; }
		safe_update("txp_prefs", "val = '".addslashes($opt)."'","name = 'aks_cache_opt' and prefs_id ='1'");
		header("Location: index.php?event=aks_cache_tab");
        } else {
		pagetop("aks_cache Prefs");
        }
	if(ps("reset_cache")){
		_aks_cache_reset();
		safe_delete("aks_cache","infos not like 'noreset-%'"); 
	}
	if(ps("clear_cache")){
		_aks_cache_reset(true);
		safe_query("truncate table `".PFX."aks_cache`"); 
	}
	$GLOBALS['prefs'] = get_prefs();
	$qcount=safe_count('aks_cache','1=1');

        echo '<table width="100%" border="0"><tr valign="top"><td width="35%">'.startTable('list').
        form(
        tr(tdcs("<div style='text-align:right;'>[<a href='?event=plugin&step=plugin_help&name=aks_cache'>Plugin help</a>]</div>".hed("aks_cache Preferences",1),3)).
        tr(tda(gTxt('Disable aks_cache:'), ' style="text-align:right;vertical-align:middle"').tda(checkbox('aks_cache_opt[]','disable',((strpos($qq,'disable')===false)? 0:1) ), ' ') ).
        tr(tda(gTxt('Debug:'), ' style="text-align:right;vertical-align:middle"').tda(checkbox('aks_cache_opt[]','debug',((strpos($qq,'debug')===false)? 0:1) ), ' ') ).
        tr(tda(gTxt('<b>Reset cache</b> if site was updated:'), ' style="text-align:right;vertical-align:middle"').tda(checkbox('aks_cache_opt[]','reset',((strpos($qq,'reset')===false)? 0:1) ), ' ') ).
        tr(tda(gTxt('Disable cache for admin:'), ' style="text-align:right;vertical-align:middle"').tda(checkbox('aks_cache_opt[]','dis_adm',((strpos($qq,'dis_adm')===false)? 0:1) ), ' ') ).
        tr(tda(gTxt('Disable cache for users:'), ' style="text-align:right;vertical-align:middle"').tda(checkbox('aks_cache_opt[]','dis_users',((strpos($qq,'dis_users')===false)? 0:1) ), ' ') ).
        tr(tda(gTxt('Cache in file:'), ' style="text-align:right;vertical-align:middle"').tda(checkbox('aks_cache_opt[]','infile',((strpos($qq,'infile')===false)? 0:1) ), ' ') ).
        tr(tda(gTxt('Default cache time in minutes:'), ' style="text-align:right;vertical-align:middle"').tda(fInput("text","aks_cache_time",$prefs['aks_cache_time'],'edit','','','10'), ' ') .tda("") ).
        tr(tda(gTxt('Cache path:'), ' style="text-align:right;vertical-align:middle"').tda(fInput("text","aks_cache_path",$prefs['aks_cache_path'],'edit','','','20'), ' ') .tda("") ).
	(($isnw===true)?tr(tda(gTxt('Cache path is not writable!!!'), ' colspan="2" class="not-ok"') ):'').
        tr(
		tda(fInput("submit","save",gTxt("save_button"),"publish").eInput("aks_cache_tab").sInput('saveprefs'), " class=\"noline\"")
	).

	tr(tda('<br /><br /><br />').tda(" ")).

	tr(
            tda('Items in cache:', ' style="text-align:right;vertical-align:middle"').tda("<b>$qcount</b>")
	).
	tr(
            tda(fInput("submit","clear_cache","Clear all cache","publish")).tda(fInput("submit","reset_cache","Reset cache","publish"))
	)). endTable();

	$qinfos=safe_column("infos", "aks_cache", "1=1 order by infos asc");
	echo '</td><td><table width="98%"><tr><th>cache block_id</th><th>ttl(minutes)</th><th>size(bytes)</th></tr>';
	foreach($qinfos as $qs){ $qs=preg_replace('/\|/', '</td><td style="text-align:right;">', $qs); echo "<tr><td>$qs</td></tr>"; }
	echo "</table></td></tr></table>";
}

# --- END PLUGIN CODE ---
?>