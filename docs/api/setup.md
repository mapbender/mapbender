# Mapbender REST API

Mapbender offers a REST API. A subset of the [console commands](../workflows/commands.md) is also available here for usage
e.g. in external tools. If it for example used by the [QGIS2Mapbender QGIS Plugin](https://github.com/WhereGroup/QGIS2Mapbender).

## Setup
- Replace the environment variable `JWT_PASSPHRASE=<change_me>` in the `.env` or `.env.local` file. If you are in a  
  unix environment this is automatically done (using a random string) when calling the `bootstrap` script.

:warning: Each time you change the passphrase you also need to re-generate the encryption keys

- Create the encryption keys by calling `php bin/console lexik:jwt:generate-keypair`. The keys will be saved to `config/jwt` and 
  and ignored from version control. On unix this is also automatically done when calling the bootstrap script, on windows
  it needs to be called manually if you want to use the API.

- (only when using Apache): Extend your virtual host configuration according to https://github.com/lexik/LexikJWTAuthenticationBundle/blob/3.x/Resources/doc/index.rst#important-note-for-apache-users

## Authentication
The JWT token can be obtained by sending authentication data (in JSON format: `{ "username": "<username>", "password": "<password>" }`) to `<server_url>/api/login_check`

Example Curl command:

```bash
curl -X POST <server_url>/api/login_check  -H "Content-Type: application/json"  -d '{"username": "<username>", "password": "<password>"}'`
```

You will get a JSON response like `{"token": "<token>"}` in the success case. 

## Calling the API

Call the API by supplying the obtained token in the Authorization header:

```bash
curl -X GET -H "Authorization: Bearer <token>" <server_url>/api/example
```



[↑ Back to top](#controllers)

[← Back to README](../README.md)
