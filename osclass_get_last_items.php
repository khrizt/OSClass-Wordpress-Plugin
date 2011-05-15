<?php
/*
Plugin Name: OSClass-lasts-items
Plugin URI: http://osclass.org/
Description: A simple plugin to get the last ads from an osclass installation
Version: 0.1
Author: Christian Fuentes
Author URI: http://www.christianfuentes.net
License: GPL
*/

add_action('init', 'osclass_get_last_items') ;

function osclass_get_last_items()
{
	$db_host = get_option('osclass_db_host') ;
	$db_name = get_option('osclass_db_name') ;
	$db_user = get_option('osclass_db_user') ;
	$db_password = get_option('osclass_db_password') ;

	if (!$db_host || !$db_name || !$db_user || !$db_password) {
		echo 'Could not get the last items from OSClass installation. Check your database configuration.' ;
		return ;
	}

	if (!($db = mysql_connect($db_host, $db_user, $db_password))) {
		echo 'Could not connect to OSClass database, please check your database configuration.' ;
		return ;
	}
	
	if (!mysql_select_db($db_name, $db)) {
		echo 'Can not select the specified OSClass database, please check your database configuration.' ;
		return ;
	}
	$num_items = get_option('osclass_num_items') ;
	$currency_before = get_option('osclass_currency_before') ;

	$query = 'SELECT pk_i_id, fk_i_category_id, dt_pub_date, f_price, fk_c_currency_code ' ;	
	$query .= 'FROM oc_i_item ' ;
	$query .= 'WHERE e_status = \'ACTIVE\' ';
	$query .= 'ORDER BY dt_pub_date desc ' ;
	$query .= 'LIMIT '.($num_items ? $num_items : '10') ;
	if (!($result = mysql_query($query))) {
		echo 'No items found.' ;
		return ;
	}

	$item_ids = '' ;
	$items = array() ;
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if($items_ids != '') {
			$items_ids .= ',' ;
		}
		$items_ids .= $row['pk_i_id'] ;
	    $items[$row['pk_i_id']] = $row ;
	}

	$query = 'SELECT fk_i_item_id, s_title, s_description, s_what ' ;	
	$query .= 'FROM oc_i_item_description ' ;
	$query .= 'WHERE fk_i_item_id IN('.$items_ids.') ';
	if (!($result = mysql_query($query))) {
		echo 'Items found but could not get the data for the items.' ;
		return ;
	}

	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	    $items[$row['pk_i_id']] = array_merge($items[$row['pk_i_id']], $row) ;
	}

	$html = '<div class="osclass_wrapper">' ;
	foreach ($items as $id => $data) {
		$html .= '<div class="">' ;
		$html .= '<a href="">'.$data['s_title'] ;
		if ($data['f_price'] != '' && $data['f_price'] != 0) {
			if (!$currency_before) {
				$html .= ' - '.$data['f_price'].$data['fk_c_currency_code'] ;
			} else {
				$html .= ' - '.$data['fk_c_currency_code'].$data['f_price'] ;
			}
		}
		$html .= '</a>' ;
		$html .= '<p>'.substr($data['s_description'], 0, 50).'...</p>' ;
		$html .= '</div>' ;
	}
	$html .= '</div>' ;
}

/* Runs when plugin is activated */
register_activation_hook(__FILE__, 'osclas_get_last_items_install');

function osclas_get_last_items_install()
{
	/* Creates new database fields */
	add_option('osclass_db_host', '', '', 'yes');
	add_option('osclass_db_name', '', '', 'yes');
	add_option('osclass_db_user', '', '', 'yes');
	add_option('osclass_db_password', '', '', 'yes');
	add_option('osclass_num_items', 10, '', 'yes');
	add_option('osclass_currency_before', 0, '', 'yes');
}

register_deactivation_hook(__FILE__, 'osclas_get_last_items_remove');

function osclas_get_last_items_install()
{
	/* Removes plugin database files */
	delete_option('osclass_db_host') ;
	delete_option('osclass_db_host');
	delete_option('osclass_db_name');
	delete_option('osclass_db_user');
	delete_option('osclass_db_password');
	delete_option('osclass_num_items');
	delete_option('osclass_currency_before');
}

if (is_admin()) {
	/* Call the html code */
	add_action('admin_menu', 'osclass_get_last_items_admin_menu');

	function osclass_get_last_items_admin_menu()
	{
		add_options_page('OSClass Get last items settings page', 'OSClass Last Items', 'administrator',
			'osclass-get-last-items', 'osclass_get_last_items_admin_page');
	}
	
	function osclass_get_last_items_admin_page() {
		?>
		<div>
		<h2>OSClass Get last items Options</h2>
		<form method="post" action="options.php">
		<?php wp_nonce_field('update-options'); ?>
		<table width="510">
			<tr valign="top">
				<th width="92" scope="row">OSClass Database Host</th>
				<td width="406">
					<input name="osclass_db_host" type="text" id="osclass_db_host" value="<?php echo get_option('osclass_db_host'); ?>" /> (ex. localhost or localhost:3306)
				</td>
			</tr>
			<tr valign="top">
				<th width="92" scope="row">OSClass Database Name</th>
				<td width="406">
					<input name="osclass_db_name" type="text" id="osclass_db_name" value="<?php echo get_option('osclass_db_name'); ?>" /> (ex. osclass)
				</td>
			</tr>
			<tr valign="top">
				<th width="92" scope="row">OSClass Database User</th>
				<td width="406">
					<input name="osclass_db_user" type="text" id="osclass_db_user" value="<?php echo get_option('osclass_db_user'); ?>" /> (ex. joe)
				</td>
			</tr>
			<tr valign="top">
				<th width="92" scope="row">OSClass Database Password</th>
				<td width="406">
					<input name="osclass_db_password" type="text" id="osclass_db_password" value="<?php echo get_option('osclass_db_password'); ?>" /> (ex. joe)
				</td>
			</tr>
			<tr valign="top">
				<th width="92" scope="row">Number of items to show</th>
				<td width="406">
					<input name="osclass_num_items" type="text" id="osclass_num_items" value="<?php echo get_option('osclass_num_items'); ?>" /> (ex. joe)
				</td>
			</tr>
			<tr valign="top">
				<th width="92" scope="row">Show currency before?</th>
				<td width="406">
					<input name="osclass_currency_before" type="checkbox" id="osclass_currency_before" value="1" <?php if(get_option('osclass_currency_before')) { echo 'checked="checked"'; } ?> /> (ex. joe)
				</td>
			</tr>
		</table>
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="osclass_db_host,osclass_db_name,osclass_db_user,osclass_db_password,osclass_num_items,osclass_currency_before" />
		<p>
			<input type="submit" value="<?php _e('Save Changes') ?>" />
		</p>
		</form>
		</div>
		<?php
	}
}

?>