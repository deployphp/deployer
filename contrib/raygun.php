<?php
/*

## Configuration

- `raygun_api_key` – the API key of your Raygun application
- `raygun_version` – the version of your application that this deployment is releasing
- `raygun_owner_name` – the name of the person creating this deployment
- `raygun_email` – the email of the person creating this deployment
- `raygun_comment` – the deployment notes
- `raygun_scm_identifier` – the commit that this deployment was built off
- `raygun_scm_type` - the source control system you use

## Usage

To notify Raygun of a successful deployment, you can use the 'raygun:notify' task after a deployment.

```php
after('deploy', 'raygun:notify');
```
 */
namespace Deployer;

use Deployer\Utility\Httpie;

desc('Notifies Raygun of deployment');
task('raygun:notify', function () {
    $data = [
        'apiKey'       => get('raygun_api_key'),
        'version' => get('raygun_version'),
        'ownerName'   => get('raygun_owner_name'),
        'emailAddress' => get('raygun_email'),
        'comment' => get('raygun_comment'),
        'scmIdentifier' => get('raygun_scm_identifier'),
        'scmType' => get('raygun_scm_type')
    ];

    Httpie::post('https://app.raygun.io/deployments')
        ->jsonBody($data)
        ->send();
});
