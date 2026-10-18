// Swaps the facade link for a youtube-nocookie iframe on click. Loaded only where the block renders.
document.addEventListener( 'click', ( event ) => {
	const link = event.target.closest?.( '[data-vs-video]' );
	if ( ! link || event.metaKey || event.ctrlKey || event.shiftKey ) {
		return;
	}
	event.preventDefault();
	const iframe = document.createElement( 'iframe' );
	iframe.src = `https://www.youtube-nocookie.com/embed/${ encodeURIComponent( link.dataset.vsVideo ) }?autoplay=1`;
	iframe.title = link.dataset.vsTitle;
	iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
	iframe.allowFullscreen = true;
	link.replaceWith( iframe );
	iframe.focus();
} );
