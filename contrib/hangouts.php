<?php
/*

Add hook on deploy:

```php
before('deploy', 'chat:notify');
```

## Configuration

- `chat_webhook` – chat incoming webhook url, **required**
- `chat_title` – the title of your notification card, default `{{application}}`
- `chat_subtitle` – the subtitle of your card, default `{{hostname}}`
- `chat_favicon` – an image for the header of your card, default `http://{{hostname}}/favicon.png`
- `chat_line1` – first line of the text in your card, default: `{{branch}}`
- `chat_line2` – second line of the text in your card, default: `{{stage}}`

## Usage

If you want to notify only about beginning of deployment add this line only:

```php
before('deploy', 'chat:notify');
```

If you want to notify about successful end of deployment add this too:

```php
after('deploy:success', 'chat:notify:success');
```

If you want to notify about failed deployment add this too:

```php
after('deploy:failed', 'chat:notify:failure');
```

 */
namespace Deployer;

use Deployer\Utility\Httpie;

// Title of project
set('chat_title', function () {
    return get('application', 'Project');
});

set('chat_subtitle', get('hostname'));

// If 'favicon' is set Google Hangouts Chat will decorate your card with an image.
set('favicon', 'http://{{hostname}}/favicon.png');

// Deploy messages
set('chat_line1', '{{branch}}');
set('chat_line2', '{{stage}}');

desc('Notifies Google Hangouts Chat');
task('chat:notify', function () {
    if (!get('chat_webhook', false)) {
        return;
    }

    $card = [
        'header' => [
            'title' => get('chat_title'),
            'subtitle' => get('chat_subtitle'),
            'imageUrl' => get('favicon'),
            'imageStyle' => 'IMAGE'
        ],
        'sections' => [
            'widgets' => [
                'keyValue' => [
                    'topLabel' => get('chat_line1'),
                    'content' => get('chat_line2'),
                    'contentMultiline' => false,
                    'bottomLabel' => 'started',
                    // Use 'iconUrl' to set a custom icon URL (png)
                    'icon' => 'CLOCK',
                    'button' => [
                        'textButton' => [
                            'text' => 'Visit site',
                            'onClick' => [
                                'openLink' => [
                                    'url' => get('hostname')
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    Httpie::post(get('chat_webhook'))->jsonBody(['cards' => $card])->send();
})
    ->once()
    ->hidden();

desc('Notifies Google Hangouts Chat about deploy finish');
task('chat:notify:success', function () {
    if (!get('chat_webhook', false)) {
        return;
    }

    $card = [
        'header' => [
            'title' => get('chat_title'),
            'subtitle' => get('chat_subtitle'),
            'imageUrl' => get('favicon'),
            'imageStyle' => 'IMAGE'
        ],
        'sections' => [
            'widgets' => [
                'keyValue' => [
                    'topLabel' => get('chat_line1'),
                    'content' => get('chat_line2'),
                    'contentMultiline' => false,
                    'bottomLabel' => 'succeeded',
                    // Use 'iconUrl' to set a custom icon URL (png)
                    'icon' => 'STAR',
                    'button' => [
                        'textButton' => [
                            'text' => 'Visit site',
                            'onClick' => [
                                'openLink' => [
                                    'url' => get('hostname')
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    Httpie::post(get('chat_webhook'))->jsonBody(['cards' => $card])->send();
})
    ->once()
    ->hidden();

desc('Notifies Google Hangouts Chat about deploy failure');
task('chat:notify:failure', function () {
    if (!get('chat_webhook', false)) {
        return;
    }

    $card = [
        'header' => [
            'title' => get('chat_title'),
            'subtitle' => get('chat_subtitle'),
            'imageUrl' => get('favicon'),
            'imageStyle' => 'IMAGE'
        ],
        'sections' => [
            'widgets' => [
                'keyValue' => [
                    'topLabel' => get('chat_line1'),
                    'content' => get('chat_line2'),
                    'contentMultiline' => false,
                    'bottomLabel' => 'failed',
                    // Use 'iconUrl' to set a custom icon URL (png)
                    // or use 'icon' and pick from this list:
                    // https://developers.google.com/hangouts/chat/reference/message-formats/cards#customicons
                    'button' => [
                        'textButton' => [
                            'text' => 'Visit site',
                            'onClick' => [
                                'openLink' => [
                                    'url' => get('hostname')
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    Httpie::post(get('chat_webhook'))->jsonBody(['cards' => $card])->send();
})
    ->once()
    ->hidden();
