<?php
/**
 * Copyright 2005-2011 MERETHIS
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give MERETHIS
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of MERETHIS choice, provided that
 * MERETHIS also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 */

require_once "../../require.php";
require_once $centreon_path . 'www/class/centreon.class.php';
require_once $centreon_path . 'www/class/centreonSession.class.php';
require_once $centreon_path . 'www/class/centreonDB.class.php';
require_once $centreon_path . 'www/class/centreonWidget.class.php';
require_once $centreon_path . 'www/class/centreonDuration.class.php';
require_once $centreon_path . 'www/class/centreonUtils.class.php';
require_once $centreon_path . 'www/class/centreonACL.class.php';
require_once $centreon_path . 'www/class/centreonHost.class.php';

session_start();
if (!isset($_SESSION['centreon']) || !isset($_REQUEST['widgetId']) || !isset($_REQUEST['page'])) {
    exit;
}
$db = new CentreonDB();
if (CentreonSession::checkSession(session_id(), $db) == 0) {
    exit;
}

require_once $centreon_path ."GPL_LIB/Smarty/libs/Smarty.class.php";
$path = $centreon_path . "www/widgets/host-monitoring/src/";
$template = new Smarty();
$template = initSmartyTplForPopup($path, $template, "./", $centreon_path);

$centreon = $_SESSION['centreon'];
$widgetId = $_REQUEST['widgetId'];
$page = $_REQUEST['page'];

$dbb = new CentreonDB("centstorage");
$widgetObj = new CentreonWidget($centreon, $db);
$preferences = $widgetObj->getWidgetPreferences($widgetId);


$res = $db->query("SELECT `key`, `value` FROM `options` WHERE `key` LIKE 'color%'");
$stateColors = array(0 => "#19EE11",
                     1 => "#F91E05",
                     2 => "#82CFD8",
                     4 => "#2AD1D4");
while ($row = $res->fetchRow()) {
    if ($row['key'] == "color_up") {
        $stateColors[0] = $row['value'];
    } elseif ($row['key'] == "color_down") {
        $stateColors[1] = $row['value'];
    } elseif ($row['key'] == "color_unreachable") {
        $stateColors[2] = $row['value'];
    } elseif ($row['key'] == "color_pending") {
        $stateColors[4] = $row['value'];
    }
}

$stateLabels = array(0 => "Up",
                     1 => "Down",
                     2 => "Unreachable",
                     4 => "Pending");

$query = "SELECT SQL_CALC_FOUND_ROWS host_id,
				 name,
				 state,
				 state_type,
				 address,
				 last_hard_state,
				 output,
				 scheduled_downtime_depth,
				 acknowledged,
				 notify,
				 active_checks,
				 passive_checks,
				 last_check,
				 last_state_change,
				 last_hard_state_change,
				 check_attempt,
				 max_check_attempts,
				 action_url,
				 notes_url ";
$query .= "FROM hosts ";
$query .= " WHERE enabled = 1 ";
$query .= " AND name NOT LIKE '_Module_%' ";
if (isset($preferences['host_name_search']) && $preferences['host_name_search'] != "") {
    $tab = split(" ", $preferences['host_name_search']);
    $op = $tab[0];
    if (isset($tab[1])) {
        $search = $tab[1];
    }
    if ($op && isset($search) && $search != "") {
        $query = CentreonUtils::conditionBuilder($query, "name ".CentreonUtils::operandToMysqlFormat($op)." '".$dbb->escape($search)."' ");
    }
}
$stateTab = array();
if (isset($preferences['host_up']) && $preferences['host_up']) {
    $stateTab[] = 0;
}
if (isset($preferences['host_down']) && $preferences['host_down']) {
    $stateTab[] = 1;
}
if (isset($preferences['host_unreachable']) && $preferences['host_unreachable']) {
    $stateTab[] = 2;
}
if (count($stateTab)) {
    $query = CentreonUtils::conditionBuilder($query, " state IN (" . implode(',', $stateTab) . ")");
}

if (isset($preferences['acknowledgement_filter']) && $preferences['acknowledgement_filter']) {
    if ($preferences['acknowledgement_filter'] == "ack") {
        $query = CentreonUtils::conditionBuilder($query, " acknowledged = 1");
    } elseif ($preferences['acknowledgement_filter'] == "nack") {
        $query = CentreonUtils::conditionBuilder($query, " acknowledged = 0");
    }
}

if (isset($preferences['downtime_filter']) && $preferences['downtime_filter']) {
    if ($preferences['downtime_filter'] == "downtime") {
        $query = CentreonUtils::conditionBuilder($query, " scheduled_downtime_depth	> 0 ");
    } elseif ($preferences['downtime_filter'] == "ndowntime") {
        $query = CentreonUtils::conditionBuilder($query, " scheduled_downtime_depth	= 0 ");
    }
}

if (isset($preferences['state_type_filter']) && $preferences['state_type_filter']) {
    if ($preferences['state_type_filter'] == "hardonly") {
        $query = CentreonUtils::conditionBuilder($query, " state_type = 1 ");
    } elseif ($preferences['state_type_filter'] == "softonly") {
        $query = CentreonUtils::conditionBuilder($query, " state_type = 0 ");
    }
}

if (isset($preferences['hostgroup']) && $preferences['hostgroup']) {
    $query = CentreonUtils::conditionBuilder($query, " host_id IN
    												  (SELECT host_host_id
    												   FROM ".$conf_centreon['db'].".hostgroup_relation
    												   WHERE hostgroup_hg_id = ".$dbb->escape($preferences['hostgroup']).") ");
}
if (!$centreon->user->admin) {
    $pearDB = $db;
    $aclObj = new CentreonACL($centreon->user->user_id, $centreon->user->admin);
    $query .= $aclObj->queryBuilder("AND", "host_id", $aclObj->getHostsString("ID", $dbb));
}
$orderby = "name ASC";
if (isset($preferences['order_by']) && $preferences['order_by'] != "") {
    $orderby = $preferences['order_by'];
}
$query .= " ORDER BY $orderby";
$query .= " LIMIT ".($page * $preferences['entries']).",".$preferences['entries'];
$res = $dbb->query($query);
$nbRows = $dbb->numberRows();
$data = array();
$outputLength = $preferences['output_length'] ? $preferences['output_length'] : 50;
$hostObj = new CentreonHost($db);
while ($row = $res->fetchRow()) {
    foreach ($row as $key => $value) {
        if ($key == "last_check") {
            $value = date("Y-m-d H:i:s", $value);
        } elseif ($key == "last_state_change" || $key == "last_hard_state_change") {
            $value = time() - $value;
            $value = CentreonDuration::toString($value);
        } elseif ($key == "check_attempt") {
            $value = $value . "/" . $row['max_check_attempts'];
        } elseif ($key == "state") {
            $data[$row['host_id']]['color'] = $stateColors[$value];
            $value = $stateLabels[$value];
        } elseif ($key == "output") {
            $value = substr($value, 0, $outputLength);
        } elseif (($key == "action_url" || $key == "notes_url") && $value) {
            $value = $hostObj->replaceMacroInString($row['name'], $value);
        }
        $data[$row['host_id']][$key] = $value;
    }
}
$template->assign('centreon_web_path', trim($centreon->optGen['oreon_web_path'], "/"));
$template->assign('preferences', $preferences);
$template->assign('data', $data);
$template->display('index.ihtml');
?>
<script type="text/javascript">
	var nbRows = <?php echo $nbRows;?>;
	var currentPage = <?php echo $page;?>;
	var orderby = '<?php echo $orderby;?>';
	var nbCurrentItems = <?php echo count($data);?>;

	$(function () {
		$("#HostTable").styleTable();
		if (nbRows > itemsPerPage) {
            $("#pagination").pagination(nbRows, {
                							items_per_page	: itemsPerPage,
                							current_page	: pageNumber,
                							callback		: paginationCallback
            							}).append("<br/>");
		}

		$("#nbRows").html(nbCurrentItems+"/"+nbRows);

		$(".selection").each(function() {
			var curId = $(this).attr('id');
			if (typeof(clickedCb[curId]) != 'undefined') {
				this.checked = clickedCb[curId];
			}
		});

		var tmp = orderby.split(' ');
		var icn = 'n';
		if (tmp[1] == "DESC") {
			icn = 's';
		}
		$("[name="+tmp[0]+"]").append('<span style="position: relative; float: right;" class="ui-icon ui-icon-triangle-1-'+icn+'"></span>');
    });

    function paginationCallback(page_index, jq)
    {
		if (page_index != pageNumber) {
        	pageNumber = page_index;
        	clickedCb = new Array();
    		loadPage();
		}
    }
</script>
