<?php
/* 
 * Plugin Name:   MaxBlogPress Unblockable Popup
 * Version:       1.7.4
 * Plugin URI:    http://www.maxblogpress.com/plugins/mup/
 * Description:   MaxBlogPress Unblockable Popup generates custom popup to power up your wordpress blog.
 * Author:        MaxBlogPress
 * Author URI:    http://www.maxblogpress.com
 */
 
define('UPOP_VERSION', '1.7.4'); // Current version of the Plugin
define('UPOP_NAME', 'MaxBlogPress Unblockable Popup'); // Name of the Plugin

/**
 * UPop - MaxBlogPress Unblockable Popup Class
 * Holds all the necessary functions and variables
 */
class UPop 
{
    /////////////////////////////////////////////////
    // PUBLIC VARIABLES
    /////////////////////////////////////////////////

    /**
     * MaxBlogPress Unblockable Popup plugin path
	 * Consists of plugin directory and plugin file
     * @var string
     */
	var $upop_path = "";
	
    /**
     * MaxBlogPress Unblockable Popup plugin full path
     * @var string
     */
	var $upop_fullpath = "";
	
    /**
     * MaxBlogPress Unblockable Popup setting values set by admin
     * @var array
     */
	var $upop_values = array();
	
    /**
     * Holds Post/Get data
     * @var array
     */
	var $upop_request = array();
	
    /**
     * Below are the list of variables that holds the default popup window properties
     */
	var $upop_show_after_visits = 5;
	var $upop_show_days   	    = 3;
	var $upop_show_visits 	    = 2;
	var $upop_width 			= 350;
	var $upop_height 		    = 200;
	var $upop_bgcolor 		    = '#D6E1F5';
	var $upop_titlebarbgcolor   = '#4E69AE';
	var $upop_titlebartextcolor = '#FFFFFF';
	var $upop_show     			= 'checked';
	var $upop_effect 			= 'checked';
	var $upop_disable     		= '';

	/**
	 * Constructor. Adds MaxBlogPress Unblockable Popup plugin actions/filters and gets the user defined options.
	 * @access public
	 */
	function UPop() {
		$this->upop_path = preg_replace('/^.*wp-content[\\\\\/]plugins[\\\\\/]/', '', __FILE__);
		$this->upop_path = str_replace('\\','/',$this->upop_path);
		$this->upop_fullpath = get_option('siteurl').'/wp-content/plugins/'.substr($this->upop_path,0,strrpos($this->upop_path,'/')).'/';
		add_action('admin_menu', array(&$this, 'upopAddMenu'));
		
		$this->mup_activate = get_option('mup_activate');
		if( !$this->upop_values = get_option('upop_settings') ) {
			$this->upop_values = array();
		}
		if ( $this->mup_activate == 2 ) {
			if ( $this->upop_values['disable'] != 1 ) {
				add_action('init', array(&$this, 'upopSetCookie'));
				add_filter('get_header', array(&$this, 'upopStartPopup'), 98);
				add_action('wp_head', array(&$this, 'upopStartPopup'), 98);
				add_filter('get_footer', array(&$this, 'upopEndPopup'), 98);
				add_action('wp_footer', array(&$this, 'upopEndPopup'), 98);
			}
		}
	}
	
	/**
	 * Sets Cookies according to the preferences set in admin
	 * @access public
	 */
	function upopSetCookie() {
		if (!is_admin()) {
			session_start();
			$url         = parse_url(get_option('home'));
			$noof_visits = $this->upop_values['show_visits'];
			$noof_days   = $this->upop_values['show_days'];
			$days_cookie_expire = time() - (3600 * 24) * 365 * 1; //1 year
			$days_cookie_life   = time() + (3600 * 24) * $noof_days;
			$visits_cookie_life = time() + (3600 * 24) * 365 * 1;

			if ( $this->upop_values['show_after'] == 1 && !isset($_COOKIE['upopShowAfter']) ) {
				setcookie("upopShowAfter", 1, $visits_cookie_life, $url['path'] . '/');
				$halt_show = 1;
			} else if ( $this->upop_values['show_after'] != 1 ) {
				setcookie("upopShowAfter", '', $visits_cookie_life, $url['path'] . '/');
			}			
			if ( $this->upop_values['show_after'] == 1 && isset($_COOKIE['upopShowAfter']) && $_COOKIE['upopShowAfter'] < $this->upop_values['show_after_visits'] ) { 
				$total_visits = intval($_COOKIE['upopShowAfter']) + 1;
				setcookie("upopShowAfter", $total_visits, $visits_cookie_life, $url['path'] . '/');
			} else {
				$start_show = 1;
			}	
			if ( $this->upop_values['show'] == 1 ) {
				setcookie("upopDays", '', $days_cookie_expire, $url['path'] . '/');
				setcookie("upopVisits", '', $days_cookie_expire, $url['path'] . '/');
			}
			else if ( $this->upop_values['show'] == 2 && !isset($_SESSION['upopShown']) && isset($_SESSION['upopFirstVisit']) ) { 
				setcookie("upopDays", '', $days_cookie_expire, $url['path'] . '/');
				setcookie("upopVisits", '', $days_cookie_expire, $url['path'] . '/');
				$_SESSION['upopShown'] = 1;
			}
			else if ( $this->upop_values['show'] == 3 && !isset($_COOKIE['upopDays']) && $start_show == 1 && $halt_show != 1 ) {
				unset($_SESSION['upopShown']);
				setcookie("upopDays", $noof_days, $days_cookie_life, $url['path'] . '/');
			}
			else if ( $this->upop_values['show'] == 4 && !isset($_COOKIE['upopVisits']) && $start_show == 1 && $halt_show != 1 ) {
				unset($_SESSION['upopShown']);
				setcookie("upopVisits", 1, $visits_cookie_life, $url['path'] . '/');
			}
			else if ( $this->upop_values['show'] == 4 && isset($_COOKIE['upopVisits']) && $_COOKIE['upopVisits']<$noof_visits && $start_show == 1 ) { 
				$upto_visits = $_COOKIE['upopVisits'] + 1;
				setcookie("upopVisits", $upto_visits, $visits_cookie_life, $url['path'] . '/');
			}
			if( !isset($_SESSION['upopFirstVisit']) && $start_show == 1 && $halt_show != 1  ) {
				$_SESSION['upopFirstVisit'] = 1;
			}
		}
	}

	/**
	 * Start Output Buffer
	 * @access public
	 */
	function upopStartPopup(){
		if ( $this->upop_header_executed != 1 ) {
			$this->upop_header_executed = 1;
			ob_start();
		}				
	}
	
	/**
	 * Displays popup. Gets content from output buffer and displays
	 * @access public
	 */
	function upopEndPopup(){
		if ( $this->upop_footer_executed == 1 ) {
			return;
		}
		$this->upop_footer_executed = 1;
		
		$upop_output = ob_get_contents();
		ob_end_clean();
		
		ob_start();
		$this->upopStyleAndJS();
		$upop_style = ob_get_contents();
		ob_end_clean();
		
		ob_start();
		$this->upopShowPopup();
		$upop_popup = ob_get_contents();
		ob_end_clean();
		
		$upop_output = str_replace("</head>", "\n $upop_style \n </head>", $upop_output);
		$upop_output = "$upop_output \n $upop_popup \n";
		echo $upop_output;
	}
	
	/**
	 * MaxBlogPress Unblockable Popup Style and JS
	 * @access public
	 */
	function upopStyleAndJS() {
		$upop_title = stripslashes(trim($this->upop_values['title']));
		$upop_txt   = trim($this->upop_values['body']);
		$upop_txt   = str_replace("\n","",$upop_txt);
		$upop_txt   = str_replace("\r","",$upop_txt);
		$upop_txt   = stripslashes($upop_txt);
		if ( trim($upop_title) == '' ) {
			$upop_title = UPOP_NAME;
		}
		$pwd_style = 'font-family:Verdana,Arial,Helvetica,sans-serif;font-size:9px;color:#006179;text-decoration:underline';
		if ( trim($this->upop_values['cb_id']) != '' ) {
			$clickbank_id = $this->upop_values['cb_id'];
		} else {
			$clickbank_id = base64_decode("bmljZWFydA==");
		}
		${base64_decode("dXBvcF9wd2Q=")} = base64_decode("PGEgaHJlZj0iaHR0cDovL3d3dy5tYXhibG9ncHJlc3MuY29tL2dvLnBocD9vZmZlcj0=").$clickbank_id.'&pid=5" style="'.$pwd_style.'" '.base64_decode("dGFyZ2V0PSJfYmxhbmsiIG9ubW91c2VvdmVyPSJzZWxmLnN0YXR1cz1cJ01heEJsb2dQcmVzcy5jb21cJztyZXR1cm4gdHJ1ZTsiIG9ubW91c2VvdXQ9InNlbGYuc3RhdHVzPVwnXCciIHRpdGxlPSJNYXhCbG9nUHJlc3MuY29tIj5Qb3dlcmVkIGJ5IE1heEJsb2dQcmVzczwvYT4=");
		if ( $this->upop_values['position'] == 'custom' ) {
			$upop_position = 'top='.$this->upop_values['top'].',left='.$this->upop_values['left'];
		} else {
			$upop_position = 'center=1';
		}
		?>
		<script>
		var path 			= '<?php echo $this->upop_fullpath;?>';
		var upop_title 		= '<?php echo $upop_title;?>';
		var upop_text 		= '<?php echo $upop_txt;?>';
		var upop_show 		= '<?php echo $this->upop_values['show'];?>';
		var upop_show_days  = '<?php echo $this->upop_values['show_days'];?>';
		var upop_position   = '<?php echo $upop_position;?>';
		var upop_width 		= '<?php echo $this->upop_values['width'];?>';
		var upop_height 	= '<?php echo $this->upop_values['height'];?>';
		var upop_bgcolor 	= '<?php echo $this->upop_values['bgcolor'];?>';
		var upop_effect 	= '<?php echo $this->upop_values['effect'];?>';
		var upop_pwd 	    = '<?php echo $upop_pwd;?>';
		var upop_titlebarbgcolor   = '<?php echo $this->upop_values['titlebarbgcolor'];?>';
		var upop_titlebartextcolor = '<?php echo $this->upop_values['titlebartextcolor'];?>';
		var clb_arr   = new Array();
		var clb_clear = new Array();
		function clbFloat(clb) {
			clb_arr[clb_arr.length] = this;
			var clbpointer = eval(clb_arr.length-1);
			this.pagetop       = 0;
			this.cmode         = (document.compatMode && document.compatMode!="BackCompat") ? document.documentElement : document.body;
			this.clbsrc        = document.all? document.all[clb] : document.getElementById(clb);
			this.clbsrc.height = this.clbsrc.offsetHeight;
			this.clbheight     = this.cmode.clientHeight;
			this.clboffset     = clbGetOffsetY(clb_arr[clbpointer]);
			var clbbar         = 'clb_clear['+clbpointer+'] = setInterval("clbFloatInit(clb_arr['+clbpointer+'])",1);';
			clbbar             = clbbar;
			eval(clbbar);
		}
		function clbGetOffsetY(clb) {
			var clbTotOffset = parseInt(clb.clbsrc.offsetTop);
			var parentOffset = clb.clbsrc.offsetParent;
			while ( parentOffset != null ) {
				clbTotOffset += parentOffset.offsetTop;
				parentOffset  = parentOffset.offsetParent;
			}
			return clbTotOffset;
		}
		function clbFloatInit(clb) {
			clb.pagetop = clb.cmode.scrollTop;
			clb.clbsrc.style.top = clb.pagetop - clb.clboffset + "px";
		}
		</script>
		<?php
	}

	/**
	 * Displays popup window
	 * @return bool
	 * @access public 
	 */
	function upopShowPopup() {
		if ( $this->upop_values['delay'] == 1 ) {
			$delay_millisecs = intval($this->upop_values['delay_time'])*1000;
		} else {
			$delay_millisecs = 0;
		}
		?>
		<?php if ( $this->upop_values['float'] == 1 ) { ?>
			<style type="text/css">
			#popupwrapper { 
				position: relative;
			}
			* html #popupwrapper { 
				width: expression(document.compatMode=="CSS1Compat"? document.documentElement.clientWidth+"px" : body.clientWidth+"px");
			}
			</style>
		<?php } ?>
		<?php 
		if ( 
		($this->upop_values['show_after'] != 1 || $_COOKIE['upopShowAfter'] >= $this->upop_values['show_after_visits']) && 
		!isset($_COOKIE['upopDays']) && 
		!isset($_SESSION['upopShown']) && 
		($_COOKIE['upopVisits'] < $this->upop_values['show_visits']) 
		) {
			if ( $this->upop_values['effect'] == 'fade' ) { ?>
				<style type="text/css">
				.popup_main {
				-moz-opacity: 0;
				filter: alpha(opacity=0);
				}</style>
				<script src='<?php echo $this->upop_fullpath;?>upop_fade.js' type='text/javascript'></script>
			<?php
			} else if ( $this->upop_values['effect'] == 'lightbox' ) { ?>
				<style>
				#overlay{ background-image: url(<?php echo $this->upop_fullpath;?>images/overlay.png); }
				* html #overlay {
				background-color: #333;
				background-color: transparent;
				background-image: url(<?php echo $this->upop_fullpath;?>images/blank.gif);
				filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src="<?php echo $this->upop_fullpath;?>images/overlay.png", sizingMethod="scale");
				}
				</style>
				<script src='<?php echo $this->upop_fullpath;?>lightbox.js' type='text/javascript'></script>
				<script src='<?php echo $this->upop_fullpath;?>upop_lightbox.js' type='text/javascript'></script>
			<?php
			} else { ?>
				<script src='<?php echo $this->upop_fullpath;?>upop_simple.js' type='text/javascript'></script>
			<?php
			}
			?>
			<script type="text/javascript">
			<?php if ( $this->upop_values['effect'] == 'lightbox' ) { ?>
				setTimeout(upopStartLightbox, <?php echo $delay_millisecs?>);
			<?php } else {?>
				setTimeout(upopStartPopup, <?php echo $delay_millisecs?>);
			<?php } ?>
			function upopStartLightbox() {
				initLightbox();
				setTimeout(upopStartPopup, 1000);
			}
			function upopStartPopup() {
				popupWindow.openPopup(upop_title, upop_text, "width="+upop_width+"px,height="+upop_height+"px,"+upop_position+",resize=1,scrolling=0");
				new clbFloat("popupwrapper");
			}
			</script>
		<?php
		}
	}
	
	/**
	 * Adds "UnblockablePopup" link to admin Options menu
	 * @access public 
	 */
	function upopAddMenu() {
		add_options_page('MaxBlogPress Unblockable Popup', 'MBP Unblockable Popup', 'manage_options', $this->upop_path, array(&$this, 'upopOptionsPg'));
	}
	
	/**
	 * Displays the page content for "UnblockablePopup" Options submenu
	 * Carries out all the operations in Options page
	 * @access public 
	 */
	function upopOptionsPg() {
		load_plugin_textdomain('MaxWordpressUnblockablePopup');
		$this->upop_request = $_REQUEST['upop'];

		$form_1 = 'mup_reg_form_1';
		$form_2 = 'mup_reg_form_2';
		// Activate the plugin if email already on list
		if ( trim($_GET['mbp_onlist']) == 1 ) { 
			$this->mup_activate = 2;
			update_option('mup_activate', $this->mup_activate);
			$msg = 'Thank you for registering the plugin. It has been activated'; 
		} 
		// If registration form is successfully submitted
		if ( ((trim($_GET['submit']) != '' && trim($_GET['from']) != '') || trim($_GET['submit_again']) != '') && $this->mup_activate != 2 ) { 
			update_option('mup_name', $_GET['name']);
			update_option('mup_email', $_GET['from']);
			$this->mup_activate = 1;
			update_option('mup_activate', $this->mup_activate);
		}
		if ( intval($this->mup_activate) == 0 ) { // First step of plugin registration
			$this->mupRegister_1($form_1);
		} else if ( intval($this->mup_activate) == 1 ) { // Second step of plugin registration
			$name  = get_option('mup_name');
			$email = get_option('mup_email');
			$this->mupRegister_2($form_2,$name,$email);
		} else if ( intval($this->mup_activate) == 2 ) { // Options page
			if ( $_REQUEST['save'] || $_REQUEST['save_all'] ) {
				$this->upopSaveOptions();
				$this->upopShowOptionsPage($msg=1);
			} else {
				$this->upopShowOptionsPage($msg);
			}
		}
	}

	/**
	 * Saves the popup settings
	 * @access public 
	 */
	function upopSaveOptions() {
		if ( count($this->upop_request) ) {
			foreach ( $this->upop_request as $key=>$value ) {
				if ( $key == 'body' || $key == 'title' ) {
					$value = addslashes($value);
				} else if ( $key == 'show_after_visits' || $key == 'show_days' || $key == 'show_visits' || $key == 'width' || $key == 'height' || $key == 'top' || $key == 'left' || $key == 'delay_time' ) {
					$value = intval(trim($value));
				} 
				$upop_settings[$key] = $value;
			}
		}
		$this->upop_values = $upop_settings;
		update_option('upop_settings', $this->upop_values);
	}
	
	/**
	 * Display the options page
	 * @param string $msg Update/Delete message to be shown
	 * @access public 
	 */
	function upopShowOptionsPage($msg=0) {
		if ( $msg==1 ) {
			$msg = "'MaxBlogPress Unblockable Popup' settings saved.";
		}
		if ( $msg ) {
			echo '<div id="message" class="updated fade"><p><strong>'. __($msg, 'upop') .'</strong></p></div>';
		}
		
		$_upop_body = '';
		if ( count($this->upop_values) > 0 ) {
			$_upop_title = stripslashes($this->upop_values['title']);
			$_upop_body  = stripslashes($this->upop_values['body']);

			if ( $this->upop_values['show_after'] == 1 ) $_upop_show_after_chk = 'checked';
			if ( $this->upop_values['float'] == 1 )      $_upop_float_chk = 'checked';
			if ( $this->upop_values['show']==4 ) 	  $_upop_show_4  = 'checked';
			else if ( $this->upop_values['show']==3 ) $_upop_show_3  = 'checked';
			else if ( $this->upop_values['show']==2 ) $_upop_show_2  = 'checked';
			else $_upop_show_1 = 'checked';
			if ( $this->upop_values['effect']=='lightbox' )   $_upop_effect_3 = 'checked';
			else if ( $this->upop_values['effect']=='fade' )  $_upop_effect_2 = 'checked';
			else $_upop_effect_1 = 'checked';
			if ( $this->upop_values['disable'] == 1 ) $_upop_disable = 'checked';
			else $_upop_disable = '';
			if ( $this->upop_values['delay'] == 1 )   $_upop_delay_chk_2 = 'checked';
			else $_upop_delay_chk_1 = 'checked';
			if ( $this->upop_values['position'] == 'custom' ) {
				$_upop_pos_custom = 'block';
				$_upop_pos_custom_chk = 'checked';
			} else {
				$_upop_pos_custom = 'none';
				$_upop_pos_center_chk = 'checked';
			}
			
			$_upop_show_after_visits = $this->upop_values['show_after_visits'];
			$_upop_show_days   = $this->upop_values['show_days'];
			$_upop_show_visits = $this->upop_values['show_visits'];
			$_upop_bgcolor     = $this->upop_values['bgcolor'];
			$_upop_width       = $this->upop_values['width'];
			$_upop_height      = $this->upop_values['height'];
			$_upop_titlebarbgcolor   = $this->upop_values['titlebarbgcolor'];
			$_upop_titlebartextcolor = $this->upop_values['titlebartextcolor'];
			$_upop_top         = $this->upop_values['top'];
			$_upop_left        = $this->upop_values['left'];
			$_upop_cb_id       = $this->upop_values['cb_id'];
		}
		else {
			$_upop_show_1            = $this->upop_show;
			$_upop_effect_1          = $this->upop_effect;
			$_upop_disable           = $this->upop_disable;
			$_upop_show_after_visits = $this->upop_show_after_visits;
			$_upop_show_days   	     = $this->upop_show_days;
			$_upop_show_visits 	     = $this->upop_show_visits;
			$_upop_width 			 = $this->upop_width;
			$_upop_height 		     = $this->upop_height;
			$_upop_bgcolor 		     = $this->upop_bgcolor;
			$_upop_titlebarbgcolor   = $this->upop_titlebarbgcolor;
			$_upop_titlebartextcolor = $this->upop_titlebartextcolor;
			$_upop_delay_chk_1       = 'checked';
			$_upop_pos_custom        = 'none';
			$_upop_pos_center_chk    = 'checked';
		}
		?>
		<script>
		<!--
		var full_path = '<?php echo $this->upop_fullpath;?>';
		function isNumeric(num){
			var the_val = num.value;
			var ret = (/^[0-9]*$/.test(the_val));
			if ( ret == false ) {
				alert('Should be a numeric value');
				num.value = the_val.substr(the_val,the_val.length-1);
				return false;
			}
			return true;
		}
		function upopShowHide(Div, Img) {
			var divCtrl = document.getElementById(Div);
			var Img     = document.getElementById(Img);
			if ( divCtrl.style == "" || divCtrl.style.display == "none" ) {
				divCtrl.style.display = "block";
				Img.src = '<?php echo $this->upop_fullpath?>images/minus.gif';
			} else if ( divCtrl.style != "" || divCtrl.style.display == "block" ) {
				divCtrl.style.display = "none";
				Img.src = '<?php echo $this->upop_fullpath?>images/plus.gif';
			}
		}
		function upopShowHide2(curr) {
			var divPos = document.getElementById('divPos');
			if ( curr.value == 'center' ) {
				divPos.style.display = "none";
			} else {
				divPos.style.display = "block";
			}
		}
		//-->
		</script>
		<script language="JavaScript" type="text/javascript" src="<?php echo $this->upop_fullpath;?>editor/wysiwyg.js"></script>
		<form name="upopform" method="post" onsubmit="">
		<div class="wrap"><h2> <?php echo UPOP_NAME.' '.UPOP_VERSION; ?></h2><br />
		 <span style="padding:4px 4px 4px 4px;background-color:#FFFEEB"><?php _e('There is a new version of MaxBlogPress Unblockable Popup avaialable.');?> <a href="http://www.maxblogpress.com/plugins/mup/" target="_blank"><?php _e('Download it from here', 'upop'); ?></a>.</span><br /><br />
         <strong><a href="http://www.maxblogpress.com/plugins/mup/mup-use/" target="_blank"><?php _e('How to use it?', 'upop'); ?></a></strong> | 
		 <strong><a href="http://www.maxblogpress.com/plugins/mup/mup-comments/" target="_blank"><?php _e('Comments and Suggestions', 'upop'); ?></a></strong> 
         <br /><br />
		 <table cellspacing="1" cellpadding="3" width="100%">
		  <tr><td><strong><?php _e('Enter your popup title here:', 'upop'); ?></strong></td></tr>
		  <tr>
		    <td><input type="text" name="upop[title]" value="<?php echo stripslashes($_upop_title);?>" style="width:340px;" maxlength="200" /></td>
		  </tr> 
		  <tr><td>&nbsp;</td></tr>  
		  <tr><td><strong><?php _e('Enter your popup text here:', 'upop'); ?></strong><br /><font size="1">(Click on "HTML" and then paste your code. Click on "TEXT" before saving it)</font></td></tr>
		  <tr><td>
		  <textarea id="upop[body]" name="upop[body]" style="height: 170px; width: 500px;"><?php echo stripslashes($_upop_body);?></textarea>
		  <script type="text/javascript">generate_wysiwyg('upop[body]');</script>
		  </td></tr> 
		  <tr><td>&nbsp;</td></tr> 
		  <tr><td><strong><?php _e('Show popup:', 'upop'); ?></strong></td></tr>
		  <tr>
		   <td>
		   <input type="checkbox" name="upop[show_after]" value="1" <?php echo $_upop_show_after_chk;?> /> After <input type="text" name="upop[show_after_visits]" value="<?php echo stripslashes($_upop_show_after_visits);?>" size="3" maxlength="5" /> visits<br />
		   <input type="radio" name="upop[show]" value="1" <?php echo $_upop_show_1;?> /> Every time page is loaded <br />
		   <input type="radio" name="upop[show]" value="2" <?php echo $_upop_show_2;?> /> Once until browser is closed <br />
		   <input type="radio" name="upop[show]" value="3" <?php echo $_upop_show_3;?> /> Every <input type="text" name="upop[show_days]" value="<?php echo stripslashes($_upop_show_days);?>" size="2" maxlength="5" onkeyup="isNumeric(this);" /> days <br />
		   <input type="radio" name="upop[show]" value="4" <?php echo $_upop_show_4;?> /> For first &nbsp;<input type="text" name="upop[show_visits]" value="<?php echo stripslashes($_upop_show_visits);?>" size="2" maxlength="5" onkeyup="isNumeric(this);" /> visits <br /> </td>
		  </tr> 
		  <tr><td>&nbsp;</td></tr> 
		  <tr><td><strong><?php _e('Color and size settings:', 'upop'); ?></strong></td></tr>
		  <tr>
		   <td>
		    <table cellspacing="0" cellpadding="0" width="100%">
			  <tr>
			   <td width="14%">Popup Width </td>
			   <td width="23%"><input type="text" name="upop[width]" value="<?php echo $_upop_width;?>" size="10" maxlength="4" onkeyup="isNumeric(this);" /></td>
			   <td width="13%">Popup Height </td>
			   <td width="50%"><input type="text" name="upop[height]" value="<?php echo $_upop_height;?>" size="10" maxlength="4" onkeyup="isNumeric(this);" /></td>
			  </tr>
			  <tr>
			   <td>Title Bar Color </td><td><input type="text" name="upop[titlebarbgcolor]" id="upop[titlebarbgcolor]" value="<?php echo $_upop_titlebarbgcolor;?>" size="10" maxlength="7" readonly /> 
			   <input type="button" name="titlebarbgcolor_btn" id="titlebarbgcolor_btn" title="Select Color" style="line-height:8px;width:20px;cursor:pointer;cursor:hand;background-color:<?php echo $_upop_titlebarbgcolor;?>" onclick='window.open("<?php echo $this->upop_fullpath;?>pickcolor.html?pid=titlebarbgcolor","colorpicker","left=300,top=200,width=240,height=170,resizable=0");' /></td>
			   <td>Title Text Color </td><td><input type="text" name="upop[titlebartextcolor]" id="upop[titlebartextcolor]" value="<?php echo $_upop_titlebartextcolor;?>" size="10" maxlength="7" readonly />
			   <input type="button" name="titlebartextcolor_btn" id="titlebartextcolor_btn" title="Select Color" style="line-height:8px;width:20px;cursor:pointer;cursor:hand;background-color:<?php echo $_upop_titlebartextcolor;?>" onclick='window.open("<?php echo $this->upop_fullpath;?>pickcolor.html?pid=titlebartextcolor","colorpicker","left=300,top=200,width=240,height=170,resizable=0");' /></td>
			  </tr>
			  <tr>
			   <td>Background Color </td><td colspan="3"><input type="text" name="upop[bgcolor]" id="upop[bgcolor]" value="<?php echo $_upop_bgcolor;?>" size="10" maxlength="7" readonly />
			   <input type="button" name="bgcolor_btn" id="bgcolor_btn" title="Select Color" style="line-height:8px;width:20px;cursor:pointer;cursor:hand;background-color:<?php echo $_upop_bgcolor;?>" onclick='window.open("<?php echo $this->upop_fullpath;?>pickcolor.html?pid=bgcolor","colorpicker","left=300,top=200,width=240,height=170,resizable=0");' /></td>
			  </tr>
			</table></td>
		  </tr> 
		  <tr><td>&nbsp;</td></tr> 
		  <tr><td><strong><?php _e('Effects:', 'upop'); ?></strong></td></tr>
		  <tr>
		   <td>
		   <input type="radio" name="upop[effect]" value="simple" <?php echo $_upop_effect_1;?> /> Simple &nbsp;&nbsp;&nbsp;
		   <a href="#" onclick="window.open('<?php echo $this->upop_fullpath;?>preview.php?effect=simple&path=<?php echo $this->upop_fullpath;?>&poweredby_link=http://www.maxblogpress.com/plugins/mup/','preview','left=40,top=120,width=450,height=450,resizable=0');">(preview)</a> <br />
		   <input type="radio" name="upop[effect]" value="fade" <?php echo $_upop_effect_2;?> /> Fading &nbsp;&nbsp;&nbsp;
		   <a href="#" onclick="window.open('<?php echo $this->upop_fullpath;?>preview.php?effect=fade&path=<?php echo $this->upop_fullpath;?>&poweredby_link=http://www.maxblogpress.com/plugins/mup/','preview','left=40,top=120,width=450,height=450,resizable=0');">(preview)</a> <br />
		   <input type="radio" name="upop[effect]" value="lightbox" <?php echo $_upop_effect_3;?> /> Lightbox
		   <a href="#" onclick="window.open('<?php echo $this->upop_fullpath;?>preview.php?effect=lightbox&path=<?php echo $this->upop_fullpath;?>&poweredby_link=http://www.maxblogpress.com/plugins/mup/','preview','left=40,top=120,width=450,height=450,resizable=0');">(preview)</a> <br />
		  </tr> 
		  <tr><td>&nbsp;</td></tr> 
		  <tr><td><input type="checkbox" name="upop[disable]" value="1" <?php echo $_upop_disable;?> /> <strong><?php _e('Disable Popup', 'upop'); ?></strong></td></tr>
		  <tr><td>&nbsp;</td></tr> 
		  <tr><td><input type="submit" class="button" name="save" value="<?php _e('Save', 'upop'); ?>" /></td></tr>
		</table><br />
		
		<h3><a name="upopadv" href="#upopadv" onclick="upopShowHide('adv_option','adv_img');"><img src="<?php echo $this->upop_fullpath?>images/plus.gif" id="adv_img" border="0" /><strong><?php _e('More Options (optional)', 'upop'); ?></strong></a></h3>
		<div id="adv_option" style="display:none">
		<table border="0" width="600" cellspacing="2" cellpadding="3" style="border:1px solid #f1f1f1">
		 <tr class="alternate">
		  <td colspan="2">
		  <strong><?php _e('Popup Position', 'upop'); ?>:</strong><br />
		  <input type="radio" name="upop[position]" value="center" <?php echo $_upop_pos_center_chk;?> onclick="upopShowHide2(this);" /> <?php _e('Center', 'upop'); ?> <br />	
		  <input type="radio" name="upop[position]" value="custom" <?php echo $_upop_pos_custom_chk;?> onclick="upopShowHide2(this);" /> <?php _e('Custom', 'upop'); ?>
		  <div id="divPos" style="display:<?php echo $_upop_pos_custom;?>">
		  &nbsp;&nbsp;&nbsp;&nbsp; Distance From: &nbsp;&nbsp; Top <input type="text" name="upop[top]" value="<?php echo $_upop_top;?>" style="width:60px;" maxlength="4" />
		  &nbsp;&nbsp;&nbsp; Left <input type="text" name="upop[left]" value="<?php echo $_upop_left;?>" style="width:60px;" maxlength="4" />
		  </div>  
		  </td>
		 </tr>
		 <tr>
		  <td colspan="2">
		  <strong><?php _e('Show Popup', 'upop'); ?>:</strong><br />
		  <input type="radio" name="upop[delay]" value="0" <?php echo $_upop_delay_chk_1;?> /> <?php _e('Immediately after page is loaded', 'upop'); ?> <br />	
		  <input type="radio" name="upop[delay]" value="1" <?php echo $_upop_delay_chk_2;?> /> 
		  After <input type="text" name="upop[delay_time]" value="<?php echo $this->upop_values['delay_time'];?>" style="width:30px" maxlength="8" /> seconds the page is loaded
		  </td>
		 </tr>
		 <tr class="alternate">
		  <td colspan="2"><input type="checkbox" name="upop[float]" value="1" <?php echo $_upop_float_chk;?> /> <?php _e('Scroll the popup when the page is scrolled', 'upop'); ?></td>
		 </tr>
		 <tr>
		  <td width="18%"><?php _e('Clickbank ID', 'upop'); ?>:</td>
		  <td><input type="text" name="upop[cb_id]" value="<?php echo $_upop_cb_id;?>" size="15" maxlength="30" /></td>
		 </tr>
		 <tr class="alternate">
		  <td colspan="2"><input type="submit" class="button" name="save_all" value="<?php _e('Save All', 'upop'); ?>" /></td>
		 </tr>
		</table>
		</div>

		<p style="text-align:center;margin-top:3em;"><strong><?php echo UPOP_NAME.' '.UPOP_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	    </div>
	    </form>
		<?php
	}
	
	/**
	 * Plugin registration form
	 * @access public 
	 */
	function mupRegistrationForm($form_name, $submit_btn_txt='Register', $name, $email, $hide=0, $submit_again='') {
		$wp_url = get_bloginfo('wpurl');
		$wp_url = (strpos($wp_url,'http://') === false) ? get_bloginfo('siteurl') : $wp_url;
		$thankyou_url = $wp_url.'/wp-admin/options-general.php?page='.$_GET['page'];
		$onlist_url   = $wp_url.'/wp-admin/options-general.php?page='.$_GET['page'].'&amp;mbp_onlist=1';
		if ( $hide == 1 ) $align_tbl = 'left';
		else $align_tbl = 'center';
		?>
		
		<?php if ( $submit_again != 1 ) { ?>
		<script><!--
		function trim(str){
			var n = str;
			while ( n.length>0 && n.charAt(0)==' ' ) 
				n = n.substring(1,n.length);
			while( n.length>0 && n.charAt(n.length-1)==' ' )	
				n = n.substring(0,n.length-1);
			return n;
		}
		function mupValidateForm_0() {
			var name = document.<?php echo $form_name;?>.name;
			var email = document.<?php echo $form_name;?>.from;
			var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
			var err = ''
			if ( trim(name.value) == '' )
				err += '- Name Required\n';
			if ( reg.test(email.value) == false )
				err += '- Valid Email Required\n';
			if ( err != '' ) {
				alert(err);
				return false;
			}
			return true;
		}
		//-->
		</script>
		<?php } ?>
		<table align="<?php echo $align_tbl;?>">
		<form name="<?php echo $form_name;?>" method="post" action="http://www.aweber.com/scripts/addlead.pl" <?php if($submit_again!=1){;?>onsubmit="return mupValidateForm_0()"<?php }?>>
		 <input type="hidden" name="unit" value="maxbp-activate">
		 <input type="hidden" name="redirect" value="<?php echo $thankyou_url;?>">
		 <input type="hidden" name="meta_redirect_onlist" value="<?php echo $onlist_url;?>">
		 <input type="hidden" name="meta_adtracking" value="mup-w-activate">
		 <input type="hidden" name="meta_message" value="1">
		 <input type="hidden" name="meta_required" value="from,name">
	 	 <input type="hidden" name="meta_forward_vars" value="1">	
		 <?php if ( $submit_again == 1 ) { ?> 	
		 <input type="hidden" name="submit_again" value="1">
		 <?php } ?>		 
		 <?php if ( $hide == 1 ) { ?> 
		 <input type="hidden" name="name" value="<?php echo $name;?>">
		 <input type="hidden" name="from" value="<?php echo $email;?>">
		 <?php } else { ?>
		 <tr>
		  <td>Name: </td><td><input type="text" name="name" value="<?php echo $name;?>" size="25" maxlength="150" /></td>
		 </tr>
		 <tr>
		  <td>Email: </td><td><input type="text" name="from" value="<?php echo $email;?>" size="25" maxlength="150" /></td>
		 </tr>
		 <?php } ?>
		 <tr>
		  <td>&nbsp;</td><td><input type="submit" name="submit" value="<?php echo $submit_btn_txt;?>" class="button" /></td>
		 </tr>
		 </form>
		</table>
		<?php
	}
	
	/**
	 * Register Plugin - Step 2
	 * @access public 
	 */
	function mupRegister_2($form_name='frm2',$name,$email) {
		$msg = 'You have not clicked on the confirmation link yet. A confirmation email has been sent to you again. Please check your email and click on the confirmation link to activate the plugin.';
		if ( trim($_GET['submit_again']) != '' && $msg != '' ) {
			echo '<div id="message" class="updated fade"><p><strong>'.$msg.'</strong></p></div>';
		}
		?>
		<div class="wrap"><h2> <?php echo UPOP_NAME.' '.UPOP_VERSION; ?></h2>
		 <center>
		 <table width="640" cellpadding="5" cellspacing="1" bgcolor="#ffffff" style="border:1px solid #e9e9e9">
		  <tr>
		   <td align="center"><h3>Almost Done....</h3></td>
		  </tr>
		  <tr>
		   <td><h3>Step 1:</h3></td>
		  </tr>
		  <tr>
		   <td>A confirmation email has been sent to your email "<?php echo $email;?>". You must click on the link inside the email to activate the plugin.</td>
		  </tr>
		  <tr>
		   <td><strong>The confirmation email will look like:</strong><br /><img src="http://www.maxblogpress.com/images/activate-plugin-email.jpg" vspace="4" border="0" /></td>
		  </tr>
		  <tr><td>&nbsp;</td></tr>
		  <tr>
		   <td><h3>Step 2:</h3></td>
		  </tr>
		  <tr>
		   <td>Click on the button below to Verify and Activate the plugin.</td>
		  </tr>
		  <tr>
		   <td><?php $this->mupRegistrationForm($form_name.'_0','Verify and Activate',$name,$email,$hide=1,$submit_again=1);?></td>
		  </tr>
		 </table>
		 <p>&nbsp;</p>
		 <table width="640" cellpadding="5" cellspacing="1" bgcolor="#ffffff" style="border:1px solid #e9e9e9">
           <tr>
             <td><h3>Troubleshooting</h3></td>
           </tr>
           <tr>
             <td><strong>The confirmation email is not there in my inbox!</strong></td>
           </tr>
           <tr>
             <td>Dont panic! CHECK THE JUNK, spam or bulk folder of your email.</td>
           </tr>
           <tr>
             <td>&nbsp;</td>
           </tr>
           <tr>
             <td><strong>It's not there in the junk folder either.</strong></td>
           </tr>
           <tr>
             <td>Sometimes the confirmation email takes time to arrive. Please be patient. WAIT FOR 6 HOURS AT MOST. The confirmation email should be there by then.</td>
           </tr>
           <tr>
             <td>&nbsp;</td>
           </tr>
           <tr>
             <td><strong>6 hours and yet no sign of a confirmation email!</strong></td>
           </tr>
           <tr>
             <td>Please register again from below:</td>
           </tr>
           <tr>
             <td><?php $this->mupRegistrationForm($form_name,'Register Again',$name,$email,$hide=0,$submit_again=2);?></td>
           </tr>
           <tr>
             <td><strong>Help! Still no confirmation email and I have already registered twice</strong></td>
           </tr>
           <tr>
             <td>Okay, please register again from the form above using a DIFFERENT EMAIL ADDRESS this time.</td>
           </tr>
           <tr>
             <td>&nbsp;</td>
           </tr>
           <tr>
             <td><strong>Why am I receiving an error similar to the one shown below?</strong><br />
                 <img src="http://www.maxblogpress.com/images/no-verification-error.jpg" border="0" vspace="8" /><br />
               You get that kind of error when you click on &quot;Verify and Activate&quot; button or try to register again.<br />
               <br />
               This error means that you have already subscribed but have not yet clicked on the link inside confirmation email. In order to  avoid any spam complain we don't send repeated confirmation emails. If you have not recieved the confirmation email then you need to wait for 12 hours at least before requesting another confirmation email. </td>
           </tr>
           <tr>
             <td>&nbsp;</td>
           </tr>
           <tr>
             <td><strong>But I've still got problems.</strong></td>
           </tr>
           <tr>
             <td>Stay calm. <strong><a href="http://www.maxblogpress.com/contact-us/" target="_blank">Contact us</a></strong> about it and we will get to you ASAP.</td>
           </tr>
         </table>
		 </center>		
		<p style="text-align:center;margin-top:3em;"><strong><?php echo UPOP_NAME.' '.UPOP_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	    </div>
		<?php
	}

	/**
	 * Register Plugin - Step 1
	 * @access public 
	 */
	function mupRegister_1($form_name='frm1') {
		global $userdata;
		$name  = trim($userdata->first_name.' '.$userdata->last_name);
		$email = trim($userdata->user_email);
		?>
		<div class="wrap"><h2> <?php echo UPOP_NAME.' '.UPOP_VERSION; ?></h2>
		 <center>
		 <table width="620" cellpadding="3" cellspacing="1" bgcolor="#ffffff" style="border:1px solid #e9e9e9">
		  <tr>
		   <td align="center"><h3>Please register the plugin to activate it. (Registration is free)</h3></td>
		  </tr>
		  <tr>
		   <td align="left">In addition you'll receive complimentary subscription to MaxBlogPress Newsletter which will give you many tips and tricks to attract lots of visitors to your blog.</td> 
		  </tr>
		  <tr>
		   <td align="center"><strong>Fill the form below to register the plugin:</strong></td>
		  </tr>
		  <tr>
		   <td><?php $this->mupRegistrationForm($form_name,'Register',$name,$email);?></td>
		  </tr>
		  <tr>
		   <td align="center"><font size="1">[ Your contact information will be handled with the strictest confidence <br />and will never be sold or shared with third parties ]</font></td></td>
		  </tr>
		 </table>
		 </center>
		<p style="text-align:center;margin-top:3em;"><strong><?php echo UPOP_NAME.' '.UPOP_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	    </div>
		<?php
	}
	
} // Eof Class

$UPop = new UPop();
?>