<?php

namespace AgeConfirmation\Tests;

use CommentStoreComment;
use AgeConfirmation\Hooks;
use DerivativeContext;
use FauxRequest;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use MediaWikiLangTestCase;
use QuickTemplate;
use RequestContext;
use SkinTemplate;
use Title;
use WikiPage;
use WikitextContent;

/**
 * @covers Hooks
 * @group Database
 */
class HooksTest extends MediaWikiLangTestCase {

	/**
	 * @dataProvider providerOnSkinTemplateOutputPageBeforeExec
	 * @throws \MWException
	 * @throws \ConfigException
	 */
	public function testOnSkinTemplateOutputPageBeforeExec( $enabled, $morelinkConfig,
		$morelinkAgeConfirmationMsg, $morelinkCookiePolicyMsg, $expectedLink
	) {
		$this->setMwGlobals( [
			'wgAgeConfirmationEnabled' => $enabled,
			'wgAgeConfirmationMoreUrl' => $morelinkConfig,
			'wgUseMediaWikiUIEverywhere' => true,
		] );
		MediaWikiServices::getInstance()->getMessageCache()->enable();
		if ( $morelinkAgeConfirmationMsg ) {
			$title = Title::newFromText( 'AgeConfirmation-more-link', NS_MEDIAWIKI );
			$wikiPage = WikiPage::factory( $title );
			$pageUpdater = $wikiPage->newPageUpdater( \User::newFromName( 'UTSysop' ) );
			$pageUpdater->setContent( SlotRecord::MAIN, new WikitextContent( $morelinkAgeConfirmationMsg ) );
			$pageUpdater->saveRevision( CommentStoreComment::newUnsavedComment( 'AgeConfirmation test' ) );
		}
		if ( $morelinkCookiePolicyMsg ) {
			$title = Title::newFromText( 'cookie-policy-link', NS_MEDIAWIKI );
			$wikiPage = WikiPage::factory( $title );
			$pageUpdater = $wikiPage->newPageUpdater( \User::newFromName( 'UTSysop' ) );
			$pageUpdater->setContent( SlotRecord::MAIN, new WikitextContent( $morelinkCookiePolicyMsg ) );
			$pageUpdater->saveRevision( CommentStoreComment::newUnsavedComment( 'AgeConfirmation test' ) );
		}
		$sk = new SkinTemplate();
		$tpl = new class extends QuickTemplate {
			public function execute() {
			}
		};
		Hooks::onSkinTemplateOutputPageBeforeExec( $sk, $tpl );
		$headElement = '';
		if ( isset( $tpl->data['headelement'] ) ) {
			$headElement = $tpl->data['headelement'];
		}
		if ( $expectedLink === false ) {
			$expected = '';
		} else {
			// @codingStandardsIgnoreStart Generic.Files.LineLength
			$expected =
				str_replace( '$1', $expectedLink,
					'<div class="mw-ageConfirmation-container banner-container"><div class="mw-ageConfirmation-text"><span>Cookies help us deliver our services. By using our services, you agree to our use of cookies.</span>$1<form method="POST"><input name="disableageConfirmation" class="mw-ageConfirmation-dismiss mw-ui-button" type="submit" value="OK"/></form></div></div>' );
			// @codingStandardsIgnoreEnd
		}
		$this->assertEquals( $expected, $headElement );
	}

	public function providerOnSkinTemplateOutputPageBeforeExec() {
		return [
			[
				// $wgAgeConfirmationEnabled
				true,
				// $wgAgeConfirmationMoreUrl
				'',
				// MediaWiki:AgeConfirmation-more-link
				false,
				// MediaWiki:Cookie-policy-link
				false,
				// expected cookie warning link (when string), nothing if false
				'',
			],
			[
				false,
				'',
				false,
				false,
				false,
			],
			[
				true,
				'http://google.de',
				false,
				false,
				"\u{00A0}<a href=\"http://google.de\">More information</a>",
			],
			[
				true,
				'',
				'http://google.de',
				false,
				"\u{00A0}<a href=\"http://google.de\">More information</a>",
			],
			[
				true,
				'',
				false,
				'http://google.de',
				"\u{00A0}<a href=\"http://google.de\">More information</a>",
			],
			// the config should be the used, if set (no matter if the messages are used or not)
			[
				true,
				'http://google.de',
				false,
				'http://google123.de',
				"\u{00A0}<a href=\"http://google.de\">More information</a>",
			],
			[
				true,
				'http://google.de',
				'http://google1234.de',
				'http://google123.de',
				"\u{00A0}<a href=\"http://google.de\">More information</a>",
			],
			[
				true,
				'',
				'http://google.de',
				'http://google123.de',
				"\u{00A0}<a href=\"http://google.de\">More information</a>",
			],
		];
	}
}