( function ( mw, $ ) {
	/**
	 * Sets the cookie, that the ageconfirmation is dismissed. Called,
	 * when the api query to save this information in the user preferences,
	 * failed for any reason, or the user is not logged-in.
	 */
	function setCookie() {
		mw.cookie.set( 'ageconfirmation_dismissed', true );
	}

	$( function () {
		if ( mw.cookie.get( 'ageconfirmation_dismissed' ) ) {
			$( '.mw-ageconfirmation-container' ).detach();
		} else {
			// Click handler for the "Ok" element in the ageconfirmation information bar
			$( '.mw-ageconfirmation-dismiss' ).on( 'click', function ( ev ) {
				// an anonymous user doesn't have preferences, so don't try to save this in
				// the user preferences.
				if ( !mw.user.isAnon() ) {
					// try to save, that the ageconfirmation was disabled, in the user preferences
					new mw.Api().saveOption( 'ageconfirmation_dismissed', '1' )
						.fail( function ( code, result ) {
							// if it fails, fall back to the cookie
							mw.log.warn( 'Failed to save dismissed AgeConfirmation: ' + code + '\n' + result.error + '. Using cookie now.' );
							setCookie();
						} );
				} else {
					// use cookies for anonymous users
					setCookie();
				}
				// always remove the ageconfirmation element
				$( '.mw-ageconfirmation-container' ).detach();

				ev.preventDefault();
			} );
		}
	} );
}( mediaWiki, jQuery ) );
