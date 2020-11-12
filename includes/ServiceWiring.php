<?php

use AgeConfirmation\Decisions;
use MediaWiki\MediaWikiServices;

return [
	'AgeConfirmation.Config' => function ( MediaWikiServices $services ) {
		return $services->getService( 'ConfigFactory' )
			->makeConfig( 'AgeConfirmation' );
	},
	'AgeConfirmation.Decisions' => function ( MediaWikiServices $services ) {
		return new Decisions( $services->getService( 'AgeConfirmation.Config' ),
			$services->getMainWANObjectCache() );
	},
];
