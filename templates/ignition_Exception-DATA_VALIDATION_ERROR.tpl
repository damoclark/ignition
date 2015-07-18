{* Smarty *}

{********************************************************

This template is used by ignition to display a standard error message to users
when things go fatally wrong.  It is sent back to the browser when used in
a web environment.

*********************************************************}

{extends file='layout.tpl'}


{block name=siteTitle}Unexpected Error{/block}

{block name=bodyContent}

	{* The message body for any error message handler templates must have a div with
	an id of "errorMessage", so that calls via ajax can look for this div in the
	returned page and just display it in a dialog
	*}
	<div id="errorMessage">
		<h2>An unexpected error has occurred</h2>
		{if $authenticated}  {* We know who the user is so make more personal response *}
	
		<p>
		Really sorry {$firstname} but there has been a bit of a problem with the server and we are unable to complete your request.
		</p>
		<p>
	{*	We do work hard to make sure things work properly, but sometimes things go wrong. To err is human.  While we can&apos;t guarantee problems won&apos;t happen, we can do our best to rectify them when they do. *} At this stage you cannot continue with your current task. Please report the problem to {$contact.name} by email (<a href='mailto:{$contact.email}'>{$contact.email}</a>). 
		</p>
		<p>
			Again apologies - please contact us and we hope to have this sorted out as soon as possible.  
		</p>
		<p>
			Regards,<br/>
			{$contact.name}
		</p>
	
		{else} {* $authenticated *}
		{* We don't really know who this is so more generic response *}	
		<p>
		Really sorry about this but something has gone wrong and we are unable to complete the requested action. 	We do work hard to make sure things work properly, but sometimes things go wrong. To err is human.  While we can&apos;t guarantee problems won&apos;t happen, we can do our best to rectify them when they do.  At this stage you cannot continue with your current task. Please report the problem to {$contact.name} by email (<a href='mailto:{$contact.email}'>{$contact.email}</a>). 
		</p>
		<p>
			Again apologies - please contact us and we hope to have this sorted out as soon as possible.  
		</p>
		{/if} {* $authenticated *}
	</div>

{/block}
