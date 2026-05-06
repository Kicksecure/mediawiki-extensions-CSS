<?php

namespace MediaWiki\Extension\CSS\Tests\Integration;

use MediaWiki\Extension\CSS\Hooks;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\CSS\Hooks
 * @group Database
 */
class HooksTest extends MediaWikiIntegrationTestCase {
	private function newInstance(): Hooks {
		$services = $this->getServiceContainer();
		return new Hooks(
			$services->getMainConfig(),
			$services->getHookContainer(),
			$services->getTitleFactory(),
			$services->getUrlUtils()
		);
	}

	/**
	 * @dataProvider provideCssRender
	 */
	public function testCssRender( string $expected, string $css ) {
		$hooks = $this->newInstance();

		$parserOutput = $this->createMock( ParserOutput::class );
		$parserOutput->method( 'addHeadItem' )->with( $expected );

		$parser = $this->createMock( Parser::class );
		$parser->method( 'getOutput' )
			->willReturn( $parserOutput );

		$result = $hooks->cssRender( $parser, $css );

		// The result is always empty.
		$this->assertSame( '', $result );
	}

	public static function provideCssRender() {
		return [
			[ '', '' ],
			[
				'<!-- Begin Extension:CSS --><link rel="stylesheet" ' .
				'href="/skins/skins/MyStyles.css?css-extension=1">' .
				'<!-- End Extension:CSS -->',
				'/skins/MyStyles.css',
			],
			[
				'<!-- Begin Extension:CSS --><!-- Invalid/malicious path  --><!-- End Extension:CSS -->',
				'/../../BadStyles.css',
			],
			// Regression test for T369486:
			[
				'<!-- Begin Extension:CSS --><!-- Invalid/malicious path  --><!-- End Extension:CSS -->',
				'/..\index.php?title=CSS/Path traversal/styles.css&action=raw&ctype=text/css',
			],
			[
				'<!-- Begin Extension:CSS --><style type="text/css">' .
				'/* css-sanitizer failed to parse CSS */</style>' .
				'<!-- End Extension:CSS -->',
				'{',
			],
			[
				'<!-- Begin Extension:CSS --><style type="text/css">' .
				'/* css-sanitizer failed to sanitize CSS */</style>' .
				'<!-- End Extension:CSS -->',
				<<<EOT
				  body {{
				    background: yellow;
				    font-size: 20pt;
				    color: red;
				  }}
				EOT,
			],
			[
				'<!-- Begin Extension:CSS --><style type="text/css">' .
				'body{background:yellow;font-size:20pt;color:red}</style>' .
				'<!-- End Extension:CSS -->',
				<<<EOT
				  body {
				    background: yellow;
				    font-size: 20pt;
				    color: red;
				  }
				EOT,
			],
		];
	}
}
