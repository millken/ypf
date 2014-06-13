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
</style>
</head>
<body>
<div class="notice">
<?php if(isset($e['file'])) {?>
<p><strong>[ Location ]</strong>　FILE: <span class="red"><?php echo $e['file'] ;?></span>　LINE: <span class="red"><?php echo $e['line'];?></span></p>
<?php }?>
<p class="title">[ Info ]</p>
<p class="message"><?php echo strip_tags($e['message']);?></p>
<?php if(isset($e['trace'])) {?>
<p class="title">[ Trace ]</p>
<p id="trace">
<?php echo nl2br($e['trace']);?>
</p>
<?php }?>
</div>
</div>
</body>
</html>
