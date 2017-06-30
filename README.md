The bundle provides the authentication security token to users who authenticate via Shibboleth SP apache implementation.

Then you can implement access control as symfony does.
 
 You _must_ implement your own user provider, this bundle not working without them.

# Install

Install the bundle by composer

`composer require niif/shib-auth-bundle`

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
niif_shib_auth: ~
# niif_shib_auth:
    # baseURL:          "%shib_auth_base_url%" # optional, have default value:  /Shibboleth.sso/
    # sessionInitiator: "%shib_auth_session_initiator%" # optional, have default value: Login
    # logoutPath:       "%shib_auth_logout_path%" # optional, have default value: Logout
    # logoutReturnPath:       "%shib_auth_logout_return_path%" # optional, have default value: "/" you should use absolute url, or named symfony route too.
    # usernameAttribute: "%shib_auth_username_attribute%" # optional, have default value: REMOTE_USER
...
```

then add new firewall rule

in `app/config/security.yml`

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
        logout:
                path:   /logout
                target: /
                invalidate_session: true
                success_handler: niif_shib_auth.shib_authenticator
        ...
```
You should create a simple the logout action in any controller:
 
 ```php
    /**
     * @Route("/logout")
     * @Template()
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function logoutAction()
    {
        return $this->redirect($this->generateUrl('logged_out'));
    }
```

# Impersonate
The authenticator support the impersonate feature.

in `app/config/security.yml`

```yaml
    ...
    providers:
        ...
        shibboleth:
            id: shibboleth.user.provider
        in_memory:
            memory: ~
        ...
    ...
    firewalls:
        ...
        switch_user: { provider: in_memory }         
        main:
            guard:
                authenticators:
                    - niif_shib_auth.shib_authenticator
        logout:
                path:   /logout
                target: /
                invalidate_session: true
                success_handler: niif_shib_auth.shib_authenticator
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
