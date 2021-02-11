<?php

use MediaWiki\Auth\AbstractPreAuthenticationProvider;

class CTAuth extends AbstractPreAuthenticationProvider {

	public function testForAccountCreation( $user, $creator, array $reqs ) {

		global $wgCTExtName, $wgCTCheckEmail;

		$allowAccount = true;

		$paramsCheck = array(
			'sender_nickname' => $user->mName,
		);
		if( $wgCTCheckEmail )
			$paramsCheck['sender_email'] = $user->mEmail;

        	// Check
		$ctResult = CTBody::onSpamCheck( 'check_newuser', $paramsCheck );

		// Allow account if we have any API errors
		if ( $ctResult->errno != 0 )
		{
			if(CTBody::JSTest()!=1)
			{
				$ctResult->allow=0;
				$ctResult->comment = "Forbidden. Please, enable Javascript.";
			}
			else
			{
				$ctResult->allow=1;
			}
		}

		// Disallow account with CleanTalk comment
		if ($ctResult->allow == 0) {
			$allowAccount = false;
			return Status::newFatal( $ctResult->comment  );
		}

		if ($ctResult->inactive === 1) {
			CTBody::SendAdminEmail( $wgCTExtName, $ctResult->comment );
		}

		return $allowAccount ? Status::newGood() : Status::newFatal( $ctResult->comment  );

	}

}
