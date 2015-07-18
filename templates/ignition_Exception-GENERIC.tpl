{* Smarty *}

{********************************************************

This template is used by ignition to display a standard error message to users
when things go fatally wrong.  It is sent back to the browser when used in
a web environment. It is a generic error message page that has the details
filled in by the 'message' part of the ignition_Exception object that generates it.
The 'errorMessage' template variable will contain this 'message'.

*********************************************************}

{extends file='layout.tpl'}

{block name=siteTitle}Error Status{/block}
{block name=pageTitle}
    Sorry, there seems to be an error
{/block}


{block name=bodyContent}

{* The message body for any error message handler templates must have a div with
an id of "errorMessage", so that calls via ajax can look for this div in the
returned page and just display it in a dialog
*}
<div id="errorMessage">
	<h2>Error</h2>
	{$errorMessage}
</div>

{/block}
