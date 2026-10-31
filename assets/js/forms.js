// Sends [data-vs-form] forms in the background. Without JS they post normally. Loaded only where a form renders.
document.addEventListener( 'submit', async ( event ) => {
	const form = event.target.closest?.( '[data-vs-form]' );
	if ( ! form ) {
		return;
	}
	event.preventDefault();
	const button = form.querySelector( '[type=submit]' );
	const error = form.querySelector( '[data-error]' );
	const sent = form.querySelector( '[data-sent]' );
	const label = button?.textContent;
	if ( button ) {
		button.disabled = true;
		button.textContent = button.dataset.busy || label;
	}
	if ( error ) {
		error.textContent = '';
	}
	if ( sent ) {
		sent.textContent = '';
	}
	let reply;
	try {
		const response = await fetch( form.action, { method: 'POST', body: new FormData( form ), headers: { Accept: 'application/json' } } );
		reply = await response.json();
	} catch {
		// Network or server failure: post normally so the server shows its own page.
		form.submit();
		return;
	}
	if ( button ) {
		button.disabled = false;
		button.textContent = label;
	}
	if ( ! reply.ok ) {
		if ( error ) {
			error.textContent = reply.message;
		}
		return;
	}
	if ( sent ) {
		sent.textContent = sent.dataset.sent || reply.message;
	}
	form.reset();
	form.dispatchEvent( new CustomEvent( 'vs:sent', { detail: reply } ) );
} );
