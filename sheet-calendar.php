<?php

if(!defined('ABSPATH')) exit;

/*
Plugin Name: Sheet Calendar
Description: Printable calendar from Google Sheets
Version: 1.1
Author: Sheila Carr
*/

define('SC_VERSION','1.1');

if(!defined('SC_PRO')){
    define('SC_PRO', false);
}

function sc_is_pro(){
    return SC_PRO;
}

function sc_calendar_check_version(){

$stored = get_option('sc_calendar_version');

if($stored !== SC_VERSION){

update_option('sc_calendar_version', SC_VERSION);

}

}

add_action('plugins_loaded','sc_calendar_check_version');


function sc_get_sheet_data(){
	
if(isset($_GET['sc_clear_cache'])){
    delete_transient('sc_calendar_events');
}

$cached = get_transient('sc_calendar_events');
if($cached !== false){
    return $cached;
}

$url = get_option('sc_sheet_url');

if(empty($url)){
    return [];
}

if(!$url){
return [];
}

$response = wp_remote_get($url);

if(is_wp_error($response)){

    $fallback = get_transient('sc_calendar_events');

    if($fallback !== false){
        return $fallback;
    }

    return [];
}

$body = wp_remote_retrieve_body($response);

$rows = array_map('str_getcsv', explode("\n",$body));

set_transient('sc_calendar_events',$rows,HOUR_IN_SECONDS);
update_option('sc_calendar_last_fetch', current_time('timestamp'));
return $rows;
}


function sc_parse_events(){

$cached = get_transient('sc_calendar_events');

if($cached !== false){
return $cached;
}

$data = sc_get_sheet_data();

if(empty($data)) return [];

$header = array_shift($data);

$normalized = [];

foreach($header as $index=>$column){

$key = strtolower(trim($column));
$key = str_replace([" ","?"],["_",""],$key);

$normalized[$key] = $index;

}

$events = [];

$excluded_events = get_option('sc_excluded_events', []);

foreach($data as $row){

$title = $row[$normalized['title']] ?? '';
$date_raw = $row[$normalized['start_date']] ?? '';
$time_raw = $row[$normalized['start_time']] ?? '';
$end_raw = $row[$normalized['end_date']] ?? '';
$instructor_raw = $row[$normalized['instructor']] ?? '';
$link_url = $row[$normalized['link_url']] ?? '';

$sections = strtolower(trim($row[$normalized['sections']] ?? ''));
$calendar = strtolower(trim($row[$normalized['calendar']] ?? ''));

if($calendar !== 'yes'){
continue;
}

$start = $date_raw ? strtotime($date_raw) : false;
$end   = $end_raw ? strtotime($end_raw) : $start;

if(!$title || !$start) continue;

$time = $time_raw ? date('g:i A',strtotime($time_raw)) : '';

$instructor = '';

if($instructor_raw){

$parts = preg_split('/\s+/',trim($instructor_raw));

$first = substr($parts[0],0,1);
$last = end($parts);

$instructor = $first.'. '.$last;

}

$days = floor(($end-$start)/86400);

$is_long = $days >= 5;

for($d=0;$d<=$days;$d++){

$current = date('Y-m-d',$start+($d*86400));

if($is_long){

if($d==0){

$entry = "• <span class='sc-title'>$title Begins</span>";
$id = md5($title.$start."begin");

}

elseif($d==$days){

$entry = "• <span class='sc-title'>$title Ends</span>";
$id = md5($title.$start."end");

}

else{

continue; // skip middle days completely

}
}

else{

if($d==0){
	
$id = md5($title.$current);

if(!empty($link_url) && !isset($_GET['print'])){
$link = esc_url($link_url);
$entry = "• <span class='sc-title'><a class='sc-event-link' href='{$link}' target='_blank'>".esc_html($title)."</a></span>";
}else{
$entry = "• <span class='sc-title'>".esc_html($title)."</span>";
}

if($time){
$entry.=" <span class='sc-time'>$time</span>";
}

if($instructor){
$entry.=" <span class='sc-inst'>($instructor)</span>";
}

}

else{

$entry = "• <span class='sc-title'>$title Day ".($d+1)."</span>";

}
}

$events[$current][] = [
'id' => $id,
'html' => $entry
];

} 
}

set_transient('sc_calendar_events',$events,600);

return $events;

}


function sc_generate_calendar_table($month,$year,$events,$class=''){

$firstDay = mktime(0,0,0,$month,1,$year);

$daysInMonth = date('t',$firstDay);

$startDay = date('w',$firstDay);

$output = "<div class='sc-calendar'>";	
$output .= "<table class='sc-calendar {$class}'>

<colgroup>
<col style='width:10.75%'>
<col style='width:15.8%'>
<col style='width:15.8%'>
<col style='width:15.8%'>
<col style='width:15.8%'>
<col style='width:10.25%'>
<col style='width:15.8%'>
</colgroup>";

$output .= "<tr>
<th>Sun</th>
<th>Mon</th>
<th>Tue</th>
<th>Wed</th>
<th>Thu</th>
<th>Fri</th>
<th>Sat</th>
</tr>";

$output .= "<tr>";

for($i=0;$i<$startDay;$i++){
$output .= "<td></td>";
}

for($day=1;$day<=$daysInMonth;$day++){

$date = $year."-".str_pad($month,2,"0",STR_PAD_LEFT)."-".str_pad($day,2,"0",STR_PAD_LEFT);

$output .= "<td>";

$output .= "<strong>$day</strong>";

if(isset($events[$date])){

foreach($events[$date] as $event){

$id = $event['id'];
$html = $event['html'];

$group = preg_replace('/middle\d+$/','middle',$id);

$link = $event['link_url'] ?? '';

if(!empty($link) && !isset($_GET['print'])){
$link = esc_url($link);
$output .= "<div class='sc-event' style='margin-left:3px' onclick=\"window.open('{$link}','_blank')\" data-event='{$id}' data-group='{$group}' data-date='{$date}'>{$html}</div>";
}else{
$output .= "<div class='sc-event' style='margin-left:3px' data-event='{$id}' data-group='{$group}' data-date='{$date}'>{$html}</div>";
}

}

}

$output .= "</td>";

if(($day+$startDay)%7==0){
$output .= "</tr><tr>";
}

}

/* ensure calendar always has 6 weeks */

$currentRows = ceil(($daysInMonth + $startDay)/7);

$remaining = (7 - (($daysInMonth + $startDay) % 7)) % 7;

for($i=0;$i<$remaining;$i++){
$output .= "<td></td>";
}

$output .= "</tr>";

while($currentRows < 5){

$output .= "<tr>";

for($i=0;$i<7;$i++){
$output .= "<td class='sc-empty'></td>";
}

$output .= "</tr>";

$currentRows++;

}

$output .= "<tr><td colspan='7' class='sc-powered-by'><a href='https://weehours.studio' target='_blank'>Powered by Sheet Calendar</a></td></tr>";

$output .= "</table>";
	
$output .= "
<div class='sc-modal'>
  <div class='sc-modal-content'>
    <button class='sc-modal-close sc-close-top'>✕</button>

    <h3>Exclude Events</h3>
	<center><p>Hides events from both web display and print</p></center>

    <div class='sc-exclude-list'></div>

<div class='sc-modal-footer'>
  <button class='sc-modal-close sc-btn sc-btn-print'>Close</button>
</div>
  </div>
</div>
";
	
$output .= "</div>";

return $output;

}


function sc_generate_calendar($month,$year,$events,$prev_url,$next_url,$class=''){
	
	    if(!is_array($events)){
        $events = [];
    }
	
	do_action('sc_calendar_before_render');

$firstDay = mktime(0,0,0,$month,1,$year);

$daysInMonth = date('t',$firstDay);

$startDay = date('w',$firstDay);

$monthName = date('F Y',$firstDay);

$logo = get_option('sc_logo');
$footer = get_option('sc_footer_text');
$address = get_option('sc_address');
$phone = get_option('sc_phone');
$website = get_option('sc_website');

$output = "<div class='sc-print-area'>";

$output .= "<div class='sc-header'>";

if($logo){
$output .= "<div class='sc-logo'><img src='".esc_url($logo)."'></div>";
}

$output .= "<div class='sc-month-wrap'><h2 class='sc-month'>$monthName</h2></div>";

$output .= "</div>";

if(!$class){
$output .= "<div class='sc-print'>";
}

if(!isset($_GET['print']) && current_user_can('edit_posts')){

$output .= "<span class='sc-month-label'>Print Month:</span> ";
$output .= "<select class='sc-nav-btn sc-month-select' onchange='scChangeMonth(this)'>";

for($m=1;$m<=12;$m++){

$selected = ($m==$month) ? "selected" : "";

$name = date('F', mktime(0,0,0,$m,1,$year));

$output .= "<option value='{$m}' {$selected}>{$name}</option>";

}

$output .= "</select>";

}

$public_print = get_option('sc_public_print');
$is_admin = current_user_can('edit_posts');

if($public_print || $is_admin){
$output .= "<button class='sc-btn sc-btn-print' onclick=\"window.open('?print=1&sc_month={$month}&sc_year={$year}','_blank')\">Print</button>";
}

if($is_admin){
$output .= "<button class='sc-btn sc-btn-print' onclick=\"window.open('?print=2&sc_month={$month}&sc_year={$year}','_blank')\">2-Up</button>";
$output .= "<button class='sc-btn sc-btn-exclude sc-exclude-toggle'>Exclude Events</button>";
}

if(!$class){
$output .= "</div>";
}
	
if(empty($events)){
    $output .= "<div class='sc-calendar-error'>Calendar failed to load. Please refresh the page.</div>";
}else{
    $output .= sc_generate_calendar_table($month,$year,$events,$class);
}

$output .= "<div class='sc-footer'>";

/* QR LEFT */

$enable_qr = get_option('sc_enable_qr');
$qr_link = get_option('sc_qr');

if(!$qr_link){ $qr_link=$website; }

if($enable_qr && $qr_link){

$qr = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=".urlencode($qr_link);

$output .= "<div class='sc-footer-qr'><img src='".esc_url($qr)."'></div>";

}

/* TEXT CENTER */

$output .= "<div class='sc-footer-text'>".nl2br(esc_html($footer))."</div>";

/* CONTACT RIGHT */

$output .= "<div class='sc-footer-info'>";

if($address){ $output.="<div>".esc_html($address)."</div>"; }
if($phone){ $output.="<div class='sc-phone'>".esc_html($phone)."</div>"; }
if($website){
if(!isset($_GET['print'])){
$output.="<div><a href='".esc_url($website)."' target='_blank'>".esc_html($website)."</a></div>";
}else{
$output.="<div>".esc_html($website)."</div>";
}
}

$output .= "</div>";

if(!$class){
$output .= "</div>";
}

	$output = apply_filters('sc_calendar_after_render', $output);
	
return $output;

}

function sc_calendar_shortcode($atts){
	
	$atts = shortcode_atts([
    'month' => '',
    'year' => ''
], $atts);

if(!empty($atts['month'])){
    $month = intval($atts['month']);
} elseif(isset($_GET['sc_month'])){
    $month = intval($_GET['sc_month']);
} else {
    $month = date('n');
}
	
if(!empty($atts['year'])){
    $year = intval($atts['year']);
} elseif(isset($_GET['sc_year'])){
    $year = intval($_GET['sc_year']);
} else {
    $year = date('Y');
}

$events = sc_parse_events();

$current = get_permalink();

return sc_generate_calendar($month,$year,$events,'','');

}

add_shortcode('sheet_calendar','sc_calendar_shortcode');


function sc_calendar_color_css(){

$primary = get_option('sc_primary_color','#00b2b2');

echo "<style>
:root{
--sc-primary: {$primary};
}
</style>";

}

function sc_pro_badge(){
    if(!sc_is_pro()){
        echo "<span class='sc-pro-badge'>Pro</span>";
    }
}

add_action('wp_head','sc_calendar_color_css');


function sc_calendar_enqueue_styles(){

wp_enqueue_style(
'sheet-calendar-style',
plugin_dir_url(__FILE__) . 'sheet-calendar.css',
[],
SC_VERSION
);

}

add_action('wp_enqueue_scripts','sc_calendar_enqueue_styles');


function sc_calendar_enqueue_scripts(){

wp_enqueue_script(
'sheet-calendar-js',
plugin_dir_url(__FILE__) . 'sheet-calendar.js',
[],
SC_VERSION,
true
);

$excluded = get_option('sc_excluded_events', []);
wp_localize_script('sheet-calendar-js', 'scCalendar', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'excluded' => $excluded,
    'isAdmin' => current_user_can('edit_posts') ? 1 : 0,
]);

}

add_action('wp_enqueue_scripts','sc_calendar_enqueue_scripts');


function sc_trim_calendar_weeks($html,$mode){

libxml_use_internal_errors(true);

$dom = new DOMDocument();
$dom->loadHTML($html);

$xpath = new DOMXPath($dom);
$rows = $xpath->query("//table[contains(@class,'sc-calendar')]//tr");

/* FRONT when 6 weeks (show weeks 1–3) */
if($mode == "front6"){

foreach($rows as $i=>$row){
if($i==0) continue;
if($i > 3){
$row->parentNode->removeChild($row);
}
}

}

/* FRONT when 5 weeks (show weeks 1–2) */
if($mode == "front5"){

foreach($rows as $i=>$row){
if($i==0) continue;
if($i > 2){
$row->parentNode->removeChild($row);
}
}

}

/* BACK when 6 weeks (show weeks 4–6 but hide empty week 6) */

if($mode == "back6"){

foreach($rows as $i=>$row){

if($i==0) continue;

/* remove weeks 1–3 */

if($i <= 3){
$row->parentNode->removeChild($row);
continue;
}

/* remove completely empty week */

$tds = $row->getElementsByTagName("td");

$hasContent = false;

foreach($tds as $td){

if(trim($td->textContent) !== ""){
$hasContent = true;
break;
}
}

if(!$hasContent){
$row->parentNode->removeChild($row);
}
}
}

/* BACK when 5 weeks (show weeks 3–5) */
if($mode == "back5"){

foreach($rows as $i=>$row){
if($i==0) continue;
if($i <= 2){
$row->parentNode->removeChild($row);
}
}

}

return $dom->saveHTML();
}


function sc_calendar_print_mode(){

if(!isset($_GET['print'])) return;

$month = isset($_GET['sc_month']) ? intval($_GET['sc_month']) : date('n');
$year  = isset($_GET['sc_year'])  ? intval($_GET['sc_year'])  : date('Y');

$events = sc_parse_events();

echo "<html><head>";
echo "<title>Calendar</title>";
echo "<link rel='stylesheet' href='".plugins_url('sheet-calendar.css',__FILE__)."'>";
$excluded = get_option('sc_excluded_events', []);
if(!empty($excluded)){
    $selectors = array_map(function($id){
        return ".sc-event[data-event='{$id}']";
    }, $excluded);
    echo "<style>".implode(',',$selectors)."{display:none !important;}</style>";
}
echo "</head><body>";

if($_GET['print'] == 2){

echo "<div class='sc-print-sheet'>";

/* FRONT PAGE */

echo "<div class='sc-sheet-front'>";

$front = sc_generate_calendar($month,$year,$events,'','');
$firstDay = mktime(0,0,0,$month,1,$year);
$daysInMonth = date('t',$firstDay);
$startDay = date('w',$firstDay);

$totalWeeks = ceil(($daysInMonth + $startDay) / 7);

if($totalWeeks == 6){
    $front = sc_trim_calendar_weeks($front,"front6");
}else{
    $front = sc_trim_calendar_weeks($front,"front5");
}

echo "<div class='sc-half'>$front</div>";
echo "<div class='sc-half'>$front</div>";

echo "</div>";

/* BACK PAGE */

echo "<div class='sc-sheet-back'>";

$back = sc_generate_calendar($month,$year,$events,'','');
if($totalWeeks == 6){
    $back = sc_trim_calendar_weeks($back,"back6");
}else{
    $back = sc_trim_calendar_weeks($back,"back5");
}

echo "<div class='sc-half sc-back'>$back</div>";
echo "<div class='sc-half sc-back'>$back</div>";

echo "</div>";

echo "</div>";

}else{

echo sc_generate_calendar($month,$year,$events,'','');

}

echo "<script>

document.addEventListener('DOMContentLoaded',function(){

const excluded = JSON.parse(localStorage.getItem('scExcluded') || '[]');

excluded.forEach(id => {
document.querySelectorAll(\".sc-event[data-event='\"+id+\"']\").forEach(ev=>{
ev.style.display='none';
});
});

setTimeout(function(){
window.print();
},200);

});

</script>";

echo "</body></html>";

exit;

}

add_action('template_redirect','sc_calendar_print_mode');

/* ================================
   SETTINGS PAGE
================================ */

function sc_calendar_admin_menu(){

add_options_page(
'Sheet Calendar Settings',
'Sheet Calendar',
'manage_options',
'sheet-calendar',
'sc_calendar_settings_page'
);

}

add_action('admin_menu','sc_calendar_admin_menu');


function sc_calendar_settings_page(){

if(isset($_POST['sc_clear_cache'])){

    check_admin_referer('sc_calendar_settings-options');

    delete_transient('sc_calendar_events');

    echo '<div class="updated"><p>Calendar cache cleared.</p></div>';
}
	
if(isset($_POST['sc_test_sheet']) && $_POST['sc_test_sheet'] == '1'){

$url = get_option('sc_sheet_url');

if(empty($url)){
echo '<div class="error"><p>No Sheet URL configured.</p></div>';
return;
}

$response = wp_remote_get($url);

if(is_wp_error($response)){
echo '<div class="error"><p>Connection failed. Check the sheet URL.</p></div>';
}else{
echo '<div class="updated"><p>Connection successful. Sheet is reachable.</p></div>';
}

}
	
?>

<div class="wrap">
<h1>Sheet Calendar Settings</h1>
	
<?php

$last = get_option('sc_calendar_last_fetch');

if($last){
echo '<p><em>Last sheet refresh: '.human_time_diff($last,current_time('timestamp')).' ago</em></p>';
}

?>
	
<?php

$url = get_option('sc_sheet_url');

if(empty($url)){
echo '<div class="notice notice-warning"><p><strong>Sheet Calendar:</strong> No Google Sheet URL configured yet.</p></div>';
}

?>

<form method="post" action="options.php">
<?php wp_nonce_field('sc_calendar_settings-options'); ?>

<?php
settings_fields('sc_calendar_settings');
do_settings_sections('sc_calendar_settings');
?>

<table class="form-table">

<tr>
<th>Sheet URL</th>
<td>
<input type="text" name="sc_sheet_url"
value="<?php echo esc_attr(get_option('sc_sheet_url')); ?>"
size="80"><br>
<p class="description">Must be Shared to Web, Public, in CSV format. View readme.txt for instructions.</p>
</td>
</tr>

<tr>
<th>Logo URL</th>
<td>
<input type="text" name="sc_logo"
value="<?php echo esc_attr(get_option('sc_logo')); ?>"
size="80"><br>
<p class="description">JPG or PNG</p>
</td>
</tr>

<tr>
<th>Accent Color</th>
<td>
<input type="text" name="sc_primary_color"
value="<?php echo esc_attr(get_option('sc_primary_color','#00b2b2')); ?>"><br>
<p class="description">Defaults to black if left blank</p>
</td>
</tr>

<tr>
<th>Footer Text</th>
<td>
<textarea name="sc_footer_text" rows="4" cols="60"><?php echo esc_textarea(get_option('sc_footer_text')); ?></textarea><br>
<p class="description">For optimal printing: limit to 3-4 short lines. Use <code>Option + 8</code> (Mac) or <code>Alt + 0149</code> (PC) for bullet points.</p>
</td>
</tr>

<tr>
<th>Address</th>
<td>
<input type="text" name="sc_address"
value="<?php echo esc_attr(get_option('sc_address')); ?>"
size="80"><br>
<p class="description">(Optional)</p>
</td>
</tr>

<tr>
<th>Contact Phone</th>
<td>
<input type="text" name="sc_phone"
value="<?php echo esc_attr(get_option('sc_phone')); ?>"><br>
<p class="description">(Optional)</p>
</td>
</tr>

<tr>
<th>Website URL</th>
<td>
<input type="text" name="sc_website"
value="<?php echo esc_attr(get_option('sc_website')); ?>"><br>
<p class="description">(Optional)</p>
</td>
</tr>

<tr>
<th>Enable QR Code</th>
<td>
<input type="checkbox" name="sc_enable_qr" value="1"
<?php checked(1,get_option('sc_enable_qr'),true); ?>>
</td>
</tr>

<tr>
<th>QR Link</th>
<td>
<input type="text" name="sc_qr"
value="<?php echo esc_attr(get_option('sc_qr')); ?>"
size="80"><br>
<p class="description">Leave blank to use Website URL</p>
</td>
</tr>

<tr>
<th scope="row">Public Printing</th>
<td>
<label>
<input type="hidden" name="sc_public_print" value="0">
<input type="checkbox" name="sc_public_print" value="1" <?php checked(get_option('sc_public_print'),1); ?>>
Allow visitors to Print?
</label>
<p class="description">If disabled, only logged-in editors/admins can see Print options.</p>
</td>
</tr>
<tr>
<th scope="row">Shortcode</th>
<td>
<p>Add the calendar to any page using:</p>
<input type="text" value="[sheet_calendar]" id="sc-shortcode" readonly style="width:200px;" />
<button type="button" class="button button-secondary"
onclick="navigator.clipboard.writeText(document.getElementById('sc-shortcode').value); this.innerText='Copied!'; setTimeout(()=>this.innerText='Copy',1500);">
Copy
</button>
</td>
</tr>
<tr>
<th scope="row">Sheet Template</th>
<td>
<p><a href="https://docs.google.com/spreadsheets/d/1d8GZJO-yALYlTFZYaD-QI2l4gl26VC6_vnxhD7bcyIQ/edit?usp=sharing" target="_blank" class="button button-secondary">Open Google Sheet Template</a></p>
<p class="description">Go to File → Make a copy to use this as your own sheet. Required columns: <code>title</code>, <code>start_date</code>, <code>calendar</code>. All others are optional.</p>
</td>
</tr>
<tr>
<th scope="row">Coming Soon</th>
<td>
<p class="description">
Sheet Calendar Pro in development:<br>
• multiple calendars per page<br>
• recurring events<br>
• category filters and styling<br>
• additional layouts<br>
• customizable stylesheets
• priority support
</p>
</td>
</tr>
</table>

<?php submit_button(); ?>
</form>
<button type="button" class="button" onclick="scClearCache()">Clear Calendar Cache</button><br>
	<p class="description">1 hr cache time. Use this to push sheet updates instantly.</p>
	
	<button type="button" id="sc-test-sheet" class="button">Test Sheet Connection</button><br>
	<p class="description">Make sure your sheet URL is properly connected.</p>
	
<script>
document.getElementById('sc-test-sheet').addEventListener('click', function(){

    fetch(ajaxurl + '?action=sc_test_sheet_connection')
    .then(res => res.text())
    .then(msg => alert(msg));

});

function scClearCache(){
    fetch(ajaxurl + '?action=sc_clear_calendar_cache')
    .then(res => res.text())
    .then(msg => alert(msg));
}
</script>
	
</div>

<div class="description">
<h2>💛 Built for a Nonprofit</h2>
<p>
Sheet Calendar was built for the Gateway Art Gallery, a nonprofit art gallery in Lake City, Florida.
<br>
If it helps you, please consider making a donation to help support further development. Thank you!
</p>
<p><a href="https://www.paypal.com/donate/?hosted_button_id=US7CYBDSUEQDU" target="_blank" class="button button-primary">
Support this project
</a></p>
<p style="opacity:.7;font-size:13px;">
Developed by Sheila Carr — www.weehours.studio
</p>
</div>

<?php
}


function sc_calendar_register_settings(){

register_setting('sc_calendar_settings','sc_sheet_url');
register_setting('sc_calendar_settings','sc_logo');
register_setting('sc_calendar_settings','sc_primary_color');
register_setting('sc_calendar_settings','sc_footer_text');
register_setting('sc_calendar_settings','sc_address');
register_setting('sc_calendar_settings','sc_phone');
register_setting('sc_calendar_settings','sc_website');
register_setting('sc_calendar_settings','sc_enable_qr');
register_setting('sc_calendar_settings','sc_qr');
register_setting('sc_calendar_settings','sc_public_print');
register_setting('sc_calendar_settings','sc_excluded_events');
}

add_action('admin_init','sc_calendar_register_settings');

add_action('wp_ajax_sc_save_excluded_events','sc_save_excluded_events');

function sc_save_excluded_events(){
    if(!current_user_can('edit_posts')) wp_die('Unauthorized');
    $excluded = isset($_POST['excluded']) ? $_POST['excluded'] : [];
    $excluded = array_map('sanitize_text_field', $excluded);
    update_option('sc_excluded_events', $excluded);
    echo 'Saved.';
    wp_die();
}

add_action('wp_ajax_sc_clear_calendar_cache','sc_clear_calendar_cache');

function sc_clear_calendar_cache(){
    if(!current_user_can('manage_options')) wp_die('Unauthorized');
    delete_transient('sc_calendar_events');
    delete_transient('sc_calendar_parsed');
    echo 'Cache cleared successfully.';
    wp_die();
}

add_action('wp_ajax_sc_test_sheet_connection','sc_test_sheet_connection');

function sc_test_sheet_connection(){

$url = get_option('sc_sheet_url');

if(empty($url)){
echo 'No Sheet URL configured.';
wp_die();
}

$response = wp_remote_get($url);

if(is_wp_error($response)){
echo 'Connection failed. Check the sheet URL.';
}else{

$body = wp_remote_retrieve_body($response);

$rows = array_map('str_getcsv', explode("\n", $body));

if(empty($rows) || empty($rows[0])){
echo 'Sheet is reachable but appears empty.';
wp_die();
}

$headers = array_map('trim', $rows[0]);

$required = ['title','start_date'];

foreach($required as $col){
if(!in_array($col,$headers)){
echo 'Sheet connected but missing required column: '.$col;
wp_die();
}
}

echo 'Connection successful. Sheet and headers look good.';
}

wp_die();

}


add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'sc_calendar_settings_link');

function sc_calendar_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=sheet-calendar">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
