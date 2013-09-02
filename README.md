Opauth-PayPal
=============
[Opauth][1] strategy for PayPal authentication.

Implemented based on @uzyn's initial repository and updated to work with the new PayPal API.

Getting started
----------------
1. Install Opauth-PayPal:
   ```bash
   cd path_to_opauth/Strategy
   git clone https://github.com/BetterBrief/opauth-paypal.git PayPal
   ```

2. Create PayPal application at https://developer.paypal.com/webapps/developer/applications
   - Select [Create Application] under "My Apps"
   - Fill in the application creation form.
   - Switch on "Log In With PayPal" and fill out the required fields. Note "Return URL" is not the OAuth process return URL.
   
3. Configure Opauth-PayPal strategy with at least `App ID` and `App Secret`.

4. Direct user to `http://path_to_opauth/paypal` to authenticate

Strategy configuration
----------------------

Required parameters:

```php
<?php
'PayPal' => array(
	/**
	 * @var string
	 */
	'app_id' => 'YOUR APP ID',
	/**
	 * @var string
	 */
	'app_secret' => 'YOUR APP SECRET',
	/**
	 * @var string space separated scopes
	 */
	'scopes' => 'openid profile address email',
	/**
	 * @var boolean
	 */
	'sandbox' => 'USE_THE_SANDBOX',
);
```

Scopes
------
Data available from the identity call are detailed here: https://developer.paypal.com/webapps/developer/docs/integration/direct/log-in-with-paypal/detailed/#attributes

The scope names are mostly inherited from the OpenID spec here: http://openid.net/specs/openid-connect-basic-1_0.html#scopes
License
---------
Opauth-PayPal is MIT Licensed  
Copyright © 2012 U-Zyn Chua (http://uzyn.com)

[1]: https://github.com/uzyn/opauth
