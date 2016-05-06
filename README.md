The bundle provides roles for authenticated users according SAML entitlement attributes in $_SERVER variables
in Symfony 3.x which implements the Guard abstract class.

Then you can implement access control as symfony does.
 
 You _must_ implement your own user provider, this bundle not working without them.

# Install

Insert lines above to ```composer.json```:

```json
...
    "require": {
        "niif/shib-auth-bundle": "dev-master"
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
...
```

Install the bundle,

```
composer require niif/shib-auth-bundle
```

Update ```app/AppKernel.php```

```php
$bundles = array(
            ...
            new Niif\ShibAuthBundle\NiifShibAuthBundle(),
            ...
        );

```

Configure the shibboleth bundle.

update your ```app/config/config.yml```

```yaml
...
niif_shib_auth:
    baseURL:          "%shib_auth_base_URL%" # required
    sessionInitiator: "%shib_auth_session_initiator%" # have default value
    logoutPath:       "%shib_auth_logout_path%" # have default value
...
```
then update your

in ```app/config/parameters.yml```

```yaml
parameters
    ...
    shib_auth_base_url: "https://yoursp.com/"
    shib_auth_session_initiator: "Shibboleth.sso/DSS"
    shib_auth_logout_path: "Shiboleth.sso/Logout"
    ...
```

then add new firewall rule

in ```app/config/security.yml```

```yaml
    ...
    providers:
        ...
        shibboleth:
            id: shibboleth.user.provider
        ...
    ...
    firewalls:
        ...            
        main:
            guard:
                authenticators:
                    - niif_shib_auth.shib_authenticator
        ...
```

# Simulate shibboleth authentication in development environment

When you develop an application you shoud simulate shibboleth authentication anyhow.
You can do it in apache config, after enable *headers* and *env* modules:

```
        Alias /my_app /home/me/my_app/web
        <Directory /home/me/my_app/web>
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted           
           SetEnv Shib-Person-uid myuid
           SetEnv Shib-EduPersonEntitlement urn:oid:whatever
           RequestHeader append Shib-Identity-Provider "fakeIdPId"
           RequestHeader append eppn "myeppn"
        </Directory>
```