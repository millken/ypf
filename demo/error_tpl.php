<!DOCTYPE html>
<html>
<head>
<title>system error occurred</title>
<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
<style>
body{
	font-family: 'Microsoft Yahei', Verdana, arial, sans-serif;
	font-size:14px;
}
a{text-decoration:none;color:#174B73;}
a:hover{ text-decoration:none;color:#FF6600;}
.title{
	margin:4px 0;
	color:#F60;
	font-weight:bold;
}
.message,#trace{
	padding:1em;
	border:solid 1px #000;
	margin:10px 0;
	background:#FFD;
	line-height:150%;
}
.message{
	background:#FFD;
	color:#2E2E2E;
		border:1px solid #E0E0E0;
}
#trace{
	background:#E7F7FF;
	border:1px solid #E0E0E0;
	color:#535353;
}
.notice{
    padding:10px;
	margin:5px;
	color:#666;
	background:#FCFCFC;
	border:1px solid #E0E0E0;
}
.red{
	color:red;
	font-weight:bold;
}
pre{margin:1em 0;font-size:12px;background-color:#eee;border:1px solid #ddd;padding:5px;line-height:1.5em;color:#444;overflow:auto;-webkit-box-shadow:rgba(0,0,0,0.07) 0 1px 2px inset;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px;}

code{font-size:14px!important;padding:0 .2em!important;border-bottom:1px solid #DEDEDE !important}

</style>
</head>
<body>
<div class="notice">
<?php if(isset($error['file'])) {?>
<p><strong>[ Location ]</strong>　FILE: <span class="red"><?php echo $error['file'] ;?></span>　LINE: <span class="red"><?php echo $error['line'];?></span></p>
<?php }?>
<p class="title">[ Info ]</p>
<p class="message">[<?php echo strip_tags($error['type']);?>]<?php echo strip_tags($error['message']);?><br />
<?php echo $error['detail'];?></p>
<?php if(isset($error['trace'])) {?>
<p class="title">[ Trace ]</p>
<p id="trace">
<?php echo nl2br($error['trace']);?>
</p>
<?php }?>

</div>
</div>
</body>
</html>
