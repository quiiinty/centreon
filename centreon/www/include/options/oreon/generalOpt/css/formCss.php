<?
/**
Oreon is developped with GPL Licence 2.0 :
http://www.gnu.org/licenses/gpl.txt
Developped by : Julien Mathis - Romain Le Merlus

The Software is provided to you AS IS and WITH ALL FAULTS.
OREON makes no representation and gives no warranty whatsoever,
whether express or implied, and without limitation, with regard to the quality,
safety, contents, performance, merchantability, non-infringement or suitability for
any particular or intended purpose of the Software found on the OREON web site.
In no event will OREON be liable for any direct, indirect, punitive, special,
incidental or consequential damages however they may arise and even if OREON has
been previously advised of the possibility of such damages.

For information : contact@oreon-project.org
*/

	if (!isset($oreon))
		exit();



	$css_default = "blue.css";
	$rq = "SELECT * FROM css_color_menu";
	$DBRESULT =& $pearDB->query($rq);
	if (PEAR::isError($DBRESULT))
		print ($DBRESULT->getMessage());
	$tab_css = array();
	for($i = 0; $DBRESULT->numRows() && $DBRESULT->fetchInto($elem);$i++){
		$tab_css[$i] = $elem;


		if(isset($_GET["css_color_".$elem["id_css_color_menu"]])){
			$name = $_GET["css_color_".$elem["id_css_color_menu"]];			
			$id = $elem["id_css_color_menu"];
			$rq = "UPDATE `css_color_menu` SET `css_name` = '".$name."' WHERE `id_css_color_menu` = $id";
			$res =& $pearDB->query($rq);
			if (PEAR::isError($res))
				print ($res->getMessage() . "<br>");
		}		
	}
	
	$rq = "SELECT topology_id,topology_name,topology_page FROM topology WHERE topology_parent IS NULL AND topology_id IN (".$oreon->user->lcaTStr.") AND topology_show = '1' ORDER BY topology_order";
	$DBRESULT =& $pearDB->query($rq);
	if (PEAR::isError($DBRESULT))
		print ($DBRESULT->getMessage());
	$tab_menu = array();
	for(; $DBRESULT->numRows() && $DBRESULT->fetchInto($elem);){
		$tab_menu[$elem["topology_page"]] = $elem;
	}

	## insert new menu in table css_color_menu
	$tab_create_menu = array();
	foreach($tab_menu as $key => $val)
	{
		if(!isset($tab_css[$tab_menu[$key]["topology_page"]]))
		{
			$rq = "INSERT INTO `css_color_menu` ( `id_css_color_menu` , `menu_nb` , `css_name` )" .
					"VALUES ( NULL , ".$tab_menu[$key]["topology_page"].", '".$css_default."' )";
			$DBRESULT =& $pearDB->query($rq);
			if (PEAR::isError($DBRESULT))
				print ($DBRESULT->getMessage());
		}
	}


	#
	## Get css_file_name list
	#
	# Skin path
	$DBRESULT =& $pearDB->query("SELECT template FROM general_opt LIMIT 1");
	if (PEAR::isError($DBRESULT))
		print "DB error : ".$DBRESULT->getDebugInfo()."<br>";
	$DBRESULT->fetchInto($data);
	$skin = "./Themes/".$data["template"]."/";
	
	$tab_file_css = array();
	$i = 0;
	if ($handle  = @opendir($skin."Color"))	{
		while ($file = @readdir($handle)){
			if (is_file($skin."Color"."/$file"))	{
				$tab_file_css[$i++] = $file;
			}
		}
		@closedir($handle);
	}

/*
	$tab_file_css[0] = "blue_css.php";
	$tab_file_css[1] = "yellow_css.php";
	$tab_file_css[2] = "green_css.php";
	$tab_file_css[3] = "red_css.php";
	$tab_file_css[4] = "pink_css.php";
*/


	#
	## Get menu_css_bdd list
	#
	$rq = "SELECT * FROM css_color_menu";
	$DBRESULT =& $pearDB->query($rq);
	if (PEAR::isError($DBRESULT))
		print ($DBRESULT->getMessage());
	$elemArr = array();
	#Different style between each lines
	$style = "one";


	
	for($i = 0; $DBRESULT->numRows() && $DBRESULT->fetchInto($elem);$i++)
	{
		$select_list =	'<select name="css_color_'. $elem["id_css_color_menu"] .'">';
		for($j=0;isset($tab_file_css[$j]);$j++){
			$selected = ($elem["css_name"] == $tab_file_css[$j]) ? "selected=selected": "";
			$select_list .= '<option value="'.$tab_file_css[$j].'"   "' . $selected . '">'.$tab_file_css[$j].'</option>';
		}
		$select_list .= '</select>';


		$elemArr[$i] = array("MenuClass"=>"list_".$style,
							 "select"=> $select_list,
							 "menuName"=> $lang[$tab_menu[$elem["menu_nb"]]["topology_name"]],
							 "css_name"=> $elem["css_name"]);
		$style != "two" ? $style = "two" : $style = "one";
	}

	//print_r($elemArr);
	



	# Smarty template Init
	$tpl = new Smarty();
	$tpl = initSmartyTpl($path.'css/', $tpl);

	$tpl->assign("elemArr", $elemArr);


	## Apply a template definition


	$tpl->assign('nameTitle', $lang["genOpt_menu_name"]);
	$tpl->assign('fileTitle', $lang["genOpt_file_name"]);
	$tpl->assign('o', $o);
	$tpl->assign('p', $p);
	$tpl->display("formCss.ihtml");
	
?>