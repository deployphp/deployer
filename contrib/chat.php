<?php
/* (c) Daniel Roe <daniel@concision.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

desc('Notifying Google Hangouts Chat');
task('chat:notify', function () {
    if (!get('chat_webhook', false)) {
        return;
    }

    $card = [
        'header' => [
            'title'      => get('chat_title'),
            'subtitle'   => get('chat_subtitle'),
            'imageUrl'   => (get('favicon') ? 'http://' . get('hostname') . '/favicon.png' : ''),
            'imageStyle' => 'IMAGE'
        ],
        'sections' => [
            'widgets' => [
                'keyValue' => [
                    'topLabel'         => get('chat_line1'),
                    'content'          => get('chat_line2'),
                    'contentMultiline' => false,
                    'bottomLabel'      => 'started',
                    // Use 'iconUrl' to set a custom icon URL (png)
                    'icon'             => 'CLOCK',
                    'button'           => [
                        'textButton' => [
                            'text'    => 'Visit site',
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

    Httpie::post(get('chat_webhook'))->body(['cards' => $card])->send();
})
    ->once()
    ->shallow()
    ->setPrivate();

desc('Notifying Google Hangouts Chat about deploy finish');
task('chat:notify:success', function () {
    if (!get('chat_webhook', false)) {
        return;
    }

    $card = [
        'header' => [
            'title'      => get('chat_title'),
            'subtitle'   => get('chat_subtitle'),
            'imageUrl'   => (get('favicon') ? 'http://' . get('hostname') . '/favicon.png' : ''),
            'imageStyle' => 'IMAGE'
        ],
        'sections' => [
            'widgets' => [
                'keyValue' => [
                    'topLabel'         => get('chat_line1'),
                    'content'          => get('chat_line2'),
                    'contentMultiline' => false,
                    'bottomLabel'      => 'succeeded',
                    // Use 'iconUrl' to set a custom icon URL (png)
                    'icon'             => 'STAR',
                    'button'           => [
                        'textButton' => [
                            'text'    => 'Visit site',
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

    Httpie::post(get('chat_webhook'))->body(['cards' => $card])->send();
})
    ->once()
    ->shallow()
    ->setPrivate();

    desc('Notifying Google Hangouts Chat about deploy failure');
task('chat:notify:failure', function () {
    if (!get('chat_webhook', false)) {
        return;
    }

    $card = [
        'header' => [
            'title'      => get('chat_title'),
            'subtitle'   => get('chat_subtitle'),
            'imageUrl'   => get('favicon'),
            'imageStyle' => 'IMAGE'
        ],
        'sections' => [
            'widgets' => [
                'keyValue' => [
                    'topLabel'         => get('chat_line1'),
                    'content'          => get('chat_line2'),
                    'contentMultiline' => false,
                    'bottomLabel'      => 'failed',
                    // Use 'iconUrl' to set a custom icon URL (png)
                    // or use 'icon' and pick from this list:
                    // https://developers.google.com/hangouts/chat/reference/message-formats/cards#customicons
                    'button'           => [
                        'textButton' => [
                            'text'    => 'Visit site',
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

    Httpie::post(get('chat_webhook'))->body(['cards' => $card])->send();
})
    ->once()
    ->shallow()
    ->setPrivate();
