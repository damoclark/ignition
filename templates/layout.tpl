{* Smarty *}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

	<head>
	
		{block name=headtag}
	
			{block name=meta}
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
				<meta name="apple-mobile-web-app-capable" content="yes" />    
				<meta name="robots" content="noindex,nofollow,noarchive"/>
				{if $userAgent=="phone"}
					<meta name="viewport" content="width=480;" />
				{/if}
						
			{/block}
			
			{block name=icons}
				<link rel="shortcut icon" type="image/x-icon" href="{$themelib}/favicon.ico" />
				<link rel="icon" type="image/x-icon" href="{$themelib}/favicon.ico" />
				<link rel="apple-touch-icon-precomposed" href="{$themelib}/siteIcon-regular.png" />
				<link rel="apple-touch-icon-precomposed" sizes="72x72" href="{$themelib}/siteIcon-ipad.png" />
				<link rel="apple-touch-icon-precomposed" sizes="114x114" href="{$themelib}/siteIcon-iphone4.png" />
			{/block}
			
			<title>
				{block name=siteTitle}{$siteTitle|default:'An EDT Site'}{/block}
			</title>
	
			{block name=headerCSS}
				<link type="text/css" href="{$themelib}/jquery-ui-1.8.11.custom.css" rel="stylesheet" />	
				<link type="text/css" href="{$themelib}/layout.css" rel="stylesheet" />	
				<link type="text/css" href="{$themelib}/formatting.css" rel="stylesheet" />	
				{assign var='headerCSS' value=$headerCSS|default:''} {* Set default headerCSS to nothing *}
				{foreach $headerCSS as $css}
					{$css}
				{/foreach}
			{/block}
			
			{block name=headerJS}
				<script type="text/javascript" src="{$themelib}/jquery-latest.js"></script>
				<script type="text/javascript" src="{$themelib}/jquery-ui-latest.js"></script>    
				<script type="text/javascript" src="{$themelib}/ajax-exception-handler.js"></script>
				{assign var='headerJS' value=$headerJS|default:''} {* Set default headerJS to nothing *}
				{foreach $headerJS as $js}
					{$js}
				{/foreach}
			{/block} 
	
		{/block}
			
	</head>
	
	<body id="standard">
	
		<div id="headerArea">
			<div id="header" style="display:none;">
				<h1>
					{block name=pageTitle}{$title}{/block}
				</h1>
				<div id="loginDetails">
					{block name=loginDetails}You are logged in{/block}
				</div>
			</div>
		</div>
	
	
		{block name=breadcrumbbar}
			<div id="breadcrumbs">{block name="breadcrumbs"}Home{/block}</div>
		{/block}
	
		<div id="contentarea">
	
			<div id="content" >
			{block name=bodyContent}{/block}
			</div>
	
		</div>
	
		<div id="footer">
			<div id="footertext">
				{block name=footerContent}
				<div id="logo"></div>
				{/block}
			</div>
		</div>
			
	{* google analytics tracking code *}
	{block name=googleAnalytics}
	
	{/block}
	
	</body>

</html>
