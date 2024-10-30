<?php
$effect = $_GET['effect'];
$path   = $_GET['path'];
$poweredby_link = $_GET['poweredby_link'];
$poweredby_txt  = '<a href="'.$poweredby_link.'" target="_blank">Powered by MaxBlogPress</a>';
?>
<style>
body {
	font-family: Arial;
	font-size: 11px;
	margin-top: 10px;
	margin-left: 10px;
	margin-right: 10px;
	margin-bottom: 10px;
	color: #121212;
	background-image: url(images/bg.gif);
	background-repeat: repeat-y;
	background-position: center;
	background-color: #ebebeb;
}
</style>
<script type="text/javascript">
var path 			= '<?php echo $path;?>';
var upop_width 		= 350;
var upop_height		= 200;
var upop_poweredby 	= '<?php echo $poweredby_txt;?>';
var upop_title      = 'MaxBlogPress Unblockable Popup';
var upop_content    = 'This is <b>MaxBlogPress Unblockable Popup</b> preview'; 
var upop_bgcolor 	= '#D6E1F5';
var upop_titlebarbgcolor   = '#4E69AE';
var upop_titlebartextcolor = '#FFFFFF';
</script>

<?php if ( $effect == 'fade' ) { ?>
	<style type="text/css">
	.popup_main {
	-moz-opacity: 0;
	filter: alpha(opacity=0);
	}</style>
	<script src='<?php echo $path;?>upop_fade.js' type='text/javascript'></script>
<?php 
} else if ( $effect == 'lightbox' ) { ?>
	<style>
	#overlay{ background-image: url(<?php echo $path;?>images/overlay.png); }
	* html #overlay{
		background-color: #333;
		background-color: transparent;
		background-image: url(blank.gif);
		filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src="<?php echo $path;?>images/overlay.png", sizingMethod="scale");
	}
	</style>
	<script src='<?php echo $path;?>lightbox.js' type='text/javascript'></script>
	<script src='<?php echo $path;?>upop_lightbox.js' type='text/javascript'></script>
<?php 
} else { ?>
	<script src='<?php echo $path;?>upop_simple.js' type='text/javascript'></script>
<?php
}
?>
<script type="text/javascript">
popupWindow.openPopup(upop_title, upop_content, "width="+upop_width+"px,height="+upop_height+"px,left=50,top=120,resize=1,scrolling=0");
</script>