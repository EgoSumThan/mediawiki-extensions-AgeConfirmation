<?php

namespace AgeConfirmation;

use Config;
use ConfigException;
use ExtensionRegistry;
use Html;
use MediaWiki;
use MediaWiki\MediaWikiServices;
use MobileContext;
use MWException;
use OutputPage;
use QuickTemplate;
use SkinTemplate;
use Title;
use User;
use WebRequest;

class Hooks {
	/**
	 * BeforeInitialize hook handler.
	 *
	 * If the disableageconfirmation POST data is send, disables the ageconfirmation bar with a
	 * cookie or a user preference, if the user is logged in.
	 *
	 * @param Title &$title
	 * @param null &$unused
	 * @param OutputPage &$output
	 * @param User &$user
	 * @param WebRequest $request
	 * @param MediaWiki $mediawiki
1	 * @throws MWException
	 */
	public static function onBeforeInitialize( Title &$title, &$unused, OutputPage &$output,
		User &$user, WebRequest $request, MediaWiki $mediawiki
	) {
		if ( !$request->wasPosted() || !$request->getVal( 'disableageconfirmation' ) ) {
			return;
		}

		if ( $user->isLoggedIn() ) {
			$user->setOption( 'ageconfirmation_dismissed', 1 );
			$user->saveSettings();
		} else {
			$request->response()->setCookie( 'ageconfirmation_dismissed', true );
		}
		$output->redirect( $request->getRequestURL() );
	}

	/**
	 * SkinTemplateOutputPageBeforeExec hook handler.
	 *
	 * Adds AgeConfirmation to the output html.
	 *
	 * @param SkinTemplate &$sk
	 * @param QuickTemplate &$tpl
	 * @throws ConfigException
	 * @throws MWException
	 */
	public static function onSkinTemplateOutputPageBeforeExec(
		SkinTemplate &$sk, QuickTemplate &$tpl
	) {
		/** @var Decisions $AgeConfirmationDecisions */
		$AgeConfirmationDecisions = MediaWikiServices::getInstance()
			->getService( 'AgeConfirmation.Decisions' );

		if ( !$AgeConfirmationDecisions->shouldShowAgeConfirmation( $sk->getContext() ) ) {
			return;
		}
		$moreLink = self::getMoreLink();

		if ( $moreLink ) {
			$moreLink = "\u{00A0}" . Html::element(
				'a',
				[ 'href' => $moreLink ],
				$sk->msg( 'ageconfirmation-moreinfo-label' )->text()
			);
		}

		if ( !isset( $tpl->data['headelement'] ) ) {
			$tpl->data['headelement'] = '';
		}
		$form = Html::openElement( 'form', [ 'method' => 'POST' ] ) .
			Html::submitButton(
				$sk->msg( 'ageconfirmation-ok-label' )->text(),
				[
					'name' => 'disableageconfirmation',
					'class' => 'mw-ageconfirmation-dismiss'
				]
			) .
			Html::closeElement( 'form' );

		$cookieImage = Html::element(
			'div',
			[ 'class' => 'mw-ageconfirmation-cimage' ],
			"\u{1F36A}"
		);

		$isMobile = ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) &&
			MobileContext::singleton()->shouldDisplayMobileView();
		$tpl->data['headelement'] .= Html::openElement(
				'div',
				// banner-container marks this as a banner for Minerva
				// Note to avoid this class, in future we may want to make use of SiteNotice
				// or banner display
				[ 'class' => 'mw-ageconfirmation-container banner-container' ]
			) .
			( $isMobile ? $form : '' ) .
			Html::openElement(
				'div',
				[ 'class' => 'mw-ageconfirmation-text' ]
			) .
			Html::element(
				'span',
				[],
				$sk->msg( 'ageconfirmation-info' )->text()
			) .
			$moreLink .
			( !$isMobile ? $form : '' ) .
			Html::closeElement( 'div' ) .
			Html::closeElement( 'div' );
	}

	/**
	 * Returns the target for the "More information" link of the cookie warning bar, if one is set.
	 * The link can be set by either (checked in this order):
	 *  - the configuration variable $wgAgeConfirmationMoreUrl
	 *  - the interface message MediaWiki:AgeConfirmation-more-link
	 *  - the interface message MediaWiki:Cookie-policy-link (bc T145781)
	 *
	 * @return string|null The url or null if none set
	 * @throws ConfigException
	 */
	private static function getMoreLink() {
		$conf = self::getConfig();
		if ( $conf->get( 'AgeConfirmationMoreUrl' ) ) {
			return $conf->get( 'AgeConfirmationMoreUrl' );
		}

		$AgeConfirmationMessage = wfMessage( 'ageconfirmation-more-link' );
		if ( $AgeConfirmationMessage->exists() && !$AgeConfirmationMessage->isDisabled() ) {
			return $AgeConfirmationMessage->text();
		}

		$cookiePolicyMessage = wfMessage( 'cookie-policy-link' );
		if ( $cookiePolicyMessage->exists() && !$cookiePolicyMessage->isDisabled() ) {
			return $cookiePolicyMessage->text();
		}

		return null;
	}

	/**
	 * BeforePageDisplay hook handler.
	 *
	 * Adds the required style and JS module, if ageconfirmation is enabled.
	 *
	 * @param OutputPage $out
	 * @throws ConfigException
	 * @throws MWException
	 */
	public static function onBeforePageDisplay( OutputPage $out ) {
		/** @var Decisions $AgeConfirmationDecisions */
		$AgeConfirmationDecisions = MediaWikiServices::getInstance()
			->getService( 'AgeConfirmation.Decisions' );

		if ( !$AgeConfirmationDecisions->shouldShowAgeConfirmation( $out->getContext() ) ) {
			return;
		}

		if (
			ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) &&
			MobileContext::singleton()->shouldDisplayMobileView()
		) {
			$moduleStyles = [ 'ext.AgeConfirmation.mobile.styles' ];
		} else {
			$moduleStyles = [ 'ext.AgeConfirmation.styles' ];
		}
		$modules = [ 'ext.AgeConfirmation' ];

		$out->addModules( $modules );
		$out->addModuleStyles( $moduleStyles );
	}

	/**
	 * ResourceLoaderGetConfigVars hook handler.
	 *
	 * @param array &$vars
	 * @throws ConfigException
	 */
	public static function onResourceLoaderGetConfigVars( array &$vars ) {
		/** @var Decisions $AgeConfirmationDecisions */
		$AgeConfirmationDecisions = MediaWikiServices::getInstance()
			->getService( 'AgeConfirmation.Decisions' );
		$conf = self::getConfig();
	}

	/**
	 * Returns the Config object for the AgeConfirmation extension.
	 *
	 * @return Config
	 */
	private static function getConfig() {
		return MediaWikiServices::getInstance()->getService( 'AgeConfirmation.Config' );
	}

	/**
	 * GetPreferences hook handler
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 *
	 * @param User $user
	 * @param array &$defaultPreferences
	 * @return bool
	 */
	public static function onGetPreferences( User $user, &$defaultPreferences ) {
		$defaultPreferences['ageconfirmation_dismissed'] = [
			'type' => 'api',
			'default' => '0',
		];
		return true;
	}
}

