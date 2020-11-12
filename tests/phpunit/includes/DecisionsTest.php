<?php

namespace AgeConfirmation\Tests;

use ConfigException;
use AgeConfirmation\Decisions;
use HashBagOStuff;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;
use MWException;
use RequestContext;
use WANObjectCache;

class DecisionsTest extends MediaWikiTestCase {
	/**
	 * @covers \AgeConfirmation\Decisions::shouldShowAgeConfirmation()
	 * @throws ConfigException
	 * @throws MWException
	 */
	public function testShouldNotCallGeoLocationMultiple() {
		$this->setMwGlobals( [
			'wgAgeConfirmationEnabled' => true,
		] );

		$AgeConfirmationDecisions = new Decisions(
			MediaWikiServices::getInstance()->getService( 'AgeConfirmation.Config' ),
			new WANObjectCache( [ 'cache' => new HashBagOStuff() ] )
		);

		$AgeConfirmationDecisions->shouldShowAgeConfirmation( RequestContext::getMain() );
		$AgeConfirmationDecisions->shouldShowAgeConfirmation( RequestContext::getMain() );
	}
}
