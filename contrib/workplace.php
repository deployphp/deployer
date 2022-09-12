<?php
/*
This recipes works with Custom Integrations and Publishing Bots.


Add hook on deploy:

```
before('deploy', 'workplace:notify');
```

## Configuration

 - `workplace_webhook` - incoming workplace webhook **required**
   ```
   // With custom integration
   set('workplace_webhook', 'https://graph.facebook.com/<GROUP_ID>/feed?access_token=<ACCESS_TOKEN>');

   // With publishing bot
   set('workplace_webhook', 'https://graph.facebook.com/v3.0/group/feed?access_token=<ACCESS_TOKEN>');

   // Use markdown on message
   set('workplace_webhook', 'https://graph.facebook.com/<GROUP_ID>/feed?access_token=<ACCESS_TOKEN>&formatting=MARKDOWN');
   ```

 - `workplace_text` - notification message
   ```
   set('workplace_text', '_{{user}}_ deploying `{{branch}}` to *{{target}}*');
   ```

 - `workplace_success_text` – success template, default:
  ```
  set('workplace_success_text', 'Deploy to *{{target}}* successful');
  ```
 - `workplace_failure_text` – failure template, default:
  ```
  set('workplace_failure_text', 'Deploy to *{{target}}* failed');
  ```
 - `workplace_edit_post` – whether to create a new post for deploy result, or edit the first one created, default creates a new post:
  ```
  set('workplace_edit_post', false);
  ```

## Usage

If you want to notify only about beginning of deployment add this line only:

```php
before('deploy', 'workplace:notify');
```

If you want to notify about successful end of deployment add this too:

```php
after('deploy:success', 'workplace:notify:success');
```

If you want to notify about failed deployment add this too:

```php
after('deploy:failed', 'workplace:notify:failure');
```

 */
namespace Deployer;

use Deployer\Utility\Httpie;

// Deploy message
set('workplace_text', '_{{user}}_ deploying `{{branch}}` to *{{target}}*');
set('workplace_success_text', 'Deploy to *{{target}}* successful');
set('workplace_failure_text', 'Deploy to *{{target}}* failed');

// By default, create a new post for every message
set('workplace_edit_post', false);

desc('Notifies Workplace');
task('workplace:notify', function () {
    if (!get('workplace_webhook', false)) {
        return;
    }
    $url = get('workplace_webhook') . '&message=' . urlencode(get('workplace_text'));
    $response = Httpie::post($url)->getJson();

    if (get('workplace_edit_post', false)) {
        // Endpoint will be something like: https//graph.facebook.com/<POST_ID>?<QUERY_PARAMS>
        $url = sprintf(
            '%s://%s/%s?%s',
            parse_url(get('workplace_webhook'), PHP_URL_SCHEME),
            parse_url(get('workplace_webhook'), PHP_URL_HOST),
            $response['id'],
            parse_url(get('workplace_webhook'), PHP_URL_QUERY)
        );
        // Replace the webhook with a url that points to the created post
        set('workplace_webhook', $url);
    }
})
    ->once()
    ->hidden();

desc('Notifies Workplace about deploy finish');
task('workplace:notify:success', function () {
    if (!get('workplace_webhook', false)) {
        return;
    }
    $url = get('workplace_webhook') . '&message=' . urlencode(get('workplace_success_text'));
    Httpie::post($url)->send();
})
    ->once()
    ->hidden();

desc('Notifies Workplace about deploy failure');
task('workplace:notify:failure', function () {
    if (!get('workplace_webhook', false)) {
        return;
    }
    $url = get('workplace_webhook') . '&message=' . urlencode(get('workplace_failure_text'));
    Httpie::post($url)->send();
})
    ->once()
    ->hidden();
