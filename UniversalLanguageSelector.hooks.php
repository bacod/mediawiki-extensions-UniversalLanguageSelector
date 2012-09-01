<?php
/**
 * Hooks for UniversalLanguageSelector extension.
 *
 * Copyright (C) 2012 Alolita Sharma, Amir Aharoni, Arun Ganesh, Brandon Harris,
 * Niklas Laxström, Pau Giner, Santhosh Thottingal, Siebrand Mazeland and other
 * contributors. See CREDITS for a list.
 *
 * UniversalLanguageSelector is dual licensed GPLv2 or later and MIT. You don't
 * have to do anything special to choose one license or the other and you don't
 * have to notify anyone which license you are using. You are free to use
 * UniversalLanguageSelector in commercial projects as long as the copyright
 * header is left intact. See files GPL-LICENSE and MIT-LICENSE for details.
 *
 * @file
 * @ingroup Extensions
 * @licence GNU General Public Licence 2.0 or later
 * @licence MIT License
 */

class UniversalLanguageSelectorHooks {

	/**
	 * BeforePageDisplay hook handler.
	 * @param $out OutputPage
	 * @param $skin Skin
	 * @return bool
	 */
	public static function addModules( $out, $skin ) {
		global $wgULSGeoService;
		$out->addModules( 'ext.uls.init' );
		if ( $wgULSGeoService ) {
			$out->addModules( 'ext.uls.geoclient' );
		}
		return true;
	}

	/**
	 * ResourceLoaderTestModules hook handler.
	 * @param $testModules array of javascript testing modules. 'qunit' is fed using tests/qunit/QUnitTestResources.php.
	 * @param $resourceLoader ResourceLoader
	 * @return bool
	 */
	public static function addTestModules( array &$testModules, ResourceLoader &$resourceLoader ) {
		$testModules['qunit']['ext.uls.tests'] = array(
			'scripts' => array( 'tests/qunit/ext.uls.tests.js' ),
			'dependencies' => array( 'ext.uls.init' ),
			'localBasePath' => __DIR__,
			'remoteExtPath' => 'UniversalLanguageSelector',
		);
		return true;
	}

	/**
	 * Add some tabs for navigation for users who do not use Ajax interface.
	 * Hooks: SkinTemplateNavigation, SkinTemplateTabs
	 */
	static function addTrigger( array &$personal_urls, &$title ) {
		global $wgLang;
		$personal_urls = array( 'uls' => array(
					'text' =>  $wgLang->getLanguageName( $wgLang->getCode() ),
					'href' => '#',
					'class' => 'uls-trigger',
					'active' => true
				) ) + $personal_urls;
		return true;
	}

	protected static function isSupportedLanguage( $language ) {
		$supported = Language::fetchLanguageNames( null, 'mwfile' );
		return isset( $supported[$language] );
	}

	/**
	 * @return string
	 */
	protected static function getDefaultLanguage( array $preferred ) {
		$supported = Language::fetchLanguageNames( null, 'mwfile' );
		// look for a language that is acceptable to the client
		// and known to the wiki.
		foreach ( $preferred as $code => $weight ) {
			if ( isset( $supported[$code] ) ) {
				return $code;
			}
		}

		// Some browsers might only send codes like de-de.
		// Try with bare code.
		foreach ( $preferred as $code => $weight ) {
			$parts = explode( '-', $code, 2 );
			$code = $parts[0];
			if ( isset( $supported[$code] ) ) {
				return $code;
			}
		}
		return "";
	}

	/**
	 * Hook to UserGetLanguageObject
	 * @param  $user User
	 * @param  $code String
	 * @return bool
	 */
	public static function getLanguage( $user, &$code ) {
		global $wgRequest, $wgULSLanguageDetection;
		if ( $wgRequest->getVal( 'uselang' ) ) {
			// uselang can be used for temporary override of language preference
			return true;
		}

		$languageToUse = null;
		$languageToSave = $wgRequest->getVal( 'setlang' );
		if ( self::isSupportedLanguage( $languageToSave ) ) {
			if ( $user->isAnon() ) {
				$wgRequest->response()->setcookie( 'language', $languageToSave );
			} else {
				$user->setOption( 'language', $languageToSave );
				$user->saveSettings();
			}
			$languageToUse = $languageToSave;
		}

		// Load from cookie unless overriden
		if ( $languageToUse === null && $user->isAnon() ) {
			$languageToUse = $wgRequest->getCookie( 'language' );
		}

		// Check whether we got valid language from store or
		// explicit language change.
		if ( self::isSupportedLanguage( $languageToUse ) ) {
			$code = $languageToUse;
		} elseif ( $user->isAnon() && $wgULSLanguageDetection ) {
			$preferred = $wgRequest->getAcceptLang();
			$default = self::getDefaultLanguage( $preferred );
			if ( $default !== '' ) {
				$code = $default;
			}
		}

		// Fall back to content language
		return true;
	}

	/**
	 * Hook: ResourceLoaderGetConfigVars
	 * @param $vars Array
	 * @return bool
	 */
	public static function addConfig( &$vars ) {
		global $wgULSGeoService;
		$vars['wgULSGeoService'] = $wgULSGeoService;
		return true;
	}

	/**
	 * Hook: MakeGlobalVariablesScript
	 * @param $vars Array
	 * @return bool
	 */
	public static function addVariables( &$vars, OutputPage $out ) {
		$vars['wgULSLanguages'] = Language::fetchLanguageNames( $out->getLanguage()->getCode(), 'mwfile' );
		$vars['wgULSAcceptLanguageList'] = array_keys( $out->getRequest()->getAcceptLang() );
		return true;
	}
}
