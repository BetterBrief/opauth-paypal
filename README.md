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

2. Create PayPal application at https://devportal.x.com/
   - Select [PayPal Access] on API Scope
   - Select [OAuth 2.0 / Open Id Connect] on Protocols
   - set the value of "Return URL" to http://path_to_opauth/paypal/int_callback

3. Configure Opauth-PayPal strategy with at least `App ID` and `App Secret`.

4. Direct user to `http://path_to_opauth/paypal` to authenticate

Strategy configuration
----------------------

Required parameters:

```php
<?php
'PayPal' => array(
	'app_id' => 'YOUR APP ID',
	'app_secret' => 'YOUR APP SECRET'
)
```

The list of permissions are available on Attribute Level when registering application.

License
---------
Opauth-PayPal is MIT Licensed  
Copyright © 2012 U-Zyn Chua (http://uzyn.com)

[1]: https://github.com/uzyn/opauth
