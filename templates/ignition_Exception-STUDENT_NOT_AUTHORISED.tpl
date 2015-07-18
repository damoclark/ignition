{* Smarty *}

{********************************************************

This template is used by ignition to display a standard error message to students
who have attempted to access pages that are for staff only. 

*********************************************************}

{extends file='layout.tpl'}

{block name=siteTitle}Error Status{/block}
{block name=pageTitle}
    Sorry, there seems to be an error
{/block}

{block name=bodyContent}
	<div id="errorMessage">
		{* The message body for any error message handler templates must have a div with
		an id of "errorMessage", so that calls via ajax can look for this div in the
		returned page and just display it in a dialog
		*}
		<h2>Staff Access Only</h2>
		{if $errorMessage != ''}
			{$errorMessage}
		{else}
			<p>Sorry {$firstname} but this system is for Staff use only.  Your account has been detected as a student account.  Perhaps you should <a href="logout.php">logout</a> and then log back in using your staff username instead of your student account?  Otherwise, please contact {$contact.name} by emailing <a href='mailto:{$contact.email}'>{$contact.email}</a> for assistance.</p>
		{/if}
	</div>
{/block}
