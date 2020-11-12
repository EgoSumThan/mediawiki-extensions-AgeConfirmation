<?php

namespace AgeConfirmation;

use Config;
use ConfigException;
use IContextSource;
use MWException;
use WANObjectCache;

class Decisions {
	private $config;
	private $cache;

	private const CACHE_KEY = 'AgeConfirmationIpLookupCache:';

	/**
	 * @param Config $config
	 * @param WANObjectCache $cache
	 */
	public function __construct( Config $config, WANObjectCache $cache ) {
		$this->config = $config;
		$this->cache = $cache;
	}

	/**
	 * Checks, if the AgeConfirmation information bar should be visible to this user on
	 * this page.
	 *
	 * @param IContextSource $context
	 * @return bool Returns true, if the age confirmation warning should be visible, false otherwise.
	 * @throws ConfigException
	 * @throws MWException
	 */
	public function shouldShowAgeConfirmation( IContextSource $context ) {
		$user = $context->getUser();

		return $this->config->get( 'AgeConfirmationEnabled' ) &&
			!$user->getBoolOption( 'ageconfirmation_dismissed', false ) &&
			!$context->getRequest()->getCookie( 'ageconfirmation_dismissed' );
	}
}