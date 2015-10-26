function submit_login()
{
	$('FormElement').sendPhpr('login_onsubmit', {
		loadIndicator: {element: 'formContent', hideOnSuccess: false, show: true, injectInElement: true}}
	);

	return false;
}

window.addEvent('domready', function(){ 
	$('login').focus();
	$('FormElement').bindKeys({enter: submit_login});
});
