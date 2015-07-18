{* Smarty *}

{********************************************************

This template is used by ignition to display a standard error message when
someone tries to access something they don't have permissions to.  It will also
take a parameter 'errorMessage' which can contain specific information or
instructions that replaces the default message.

*********************************************************}

{extends file='layout.tpl'}

{block name=siteTitle}Unexpected Error{/block}
{block name=pageTitle}
	Sorry, there seems to be an error
{/block}


{block name=bodyContent}
	<div id="errorMessage">
{* The message body for any error message handler templates must have a div with
an id of "errorMessage", so that calls via ajax can look for this div in the
returned page and just display it in a dialog
*}
		<h2>Authorisation Error</h2>
		{if $errorMessage != ''}
			<p>{$errorMessage}</p>
		{else}
			<p>Sorry {$firstname} but you do not currently have access to this page. If you believe this to be incorrect, please contact {$contact.name} by emailing <a href='mailto:{$contact.email}'>{$contact.email}</a> for assistance.</p>
		{/if}
	</div>
{/block}
