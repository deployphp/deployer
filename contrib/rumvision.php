<?php
/*

### Configuration options

- **api_key** *(required)*: RUMvision API key, used as Bearer token. Each user can create an API key in their RUMvision account.
- **domains** *(required)*: Array of domain names the annotation applies to. When multiple domains are provided, `url_category_ids` must be omitted and `url_scope` must be `all`.
- **title** *(optional)*: Title of the annotation (max 255 characters), defaults to Deployer release name.
- **name_prefix** *(optional)*: A string prefixed to the title.
- **description** *(optional)*: More details about the release.
- **url_scope** *(optional)*: URL scope for the annotation, `all` or `restricted`. Defaults to `all`.
- **url_category_ids** *(optional)*: Array of URL category IDs (UUIDs) scoped to the single provided domain. Required when `url_scope` is `restricted`.
- **impact_at** *(optional)*: Impact timestamp (ISO 8601), defaults to the current time.
- **category_slug** *(optional)*: Annotation category slug, defaults to `hosting`. Available categories: `caching`, `css`, `fonts`, `hosting`, `html`, `images`, `javascript`, `other`, `video`.

```php
// deploy.php

set('rumvision', [
    'api_key' => 'k7mbqpzs.Qm8JxL2...',
    'domains' => ['example.com'],
]);
```

### Suggested Usage

Since you should only notify RUMvision of a successful deployment, the deploy:rumvision task should be executed right at the end.

```php
// deploy.php

after('deploy', 'deploy:rumvision');
```

 */

namespace Deployer;

use Deployer\Utility\Httpie;

desc('Notifies RUMvision of deployment');
task(
    'deploy:rumvision',
    static function () {
        $defaultConfig = [
            'title' => get('release_name'),
            'name_prefix' => null,
            'description' => null,
            'url_scope' => 'all',
            'url_category_ids' => null,
            'impact_at' => date('c'),
            'category_slug' => 'hosting',
            'domains' => [],
        ];

        $config = array_merge($defaultConfig, (array) get('rumvision'));
        array_walk(
            $config,
            static function (&$value) use ($config) {
                if (is_callable($value)) {
                    $value = $value($config);
                }
            },
        );

        if (
            empty($config['api_key'])
            || empty($config['domains'])
            || !is_array($config['domains'])
        ) {
            throw new \RuntimeException(
                <<<EXAMPLE
                    Required data missing. Please configure rumvision:
                    set(
                        'rumvision',
                        [
                            'api_key' => 'k7mbqpzs.Qm8JxL2...',
                            'domains' => ['example.com'],
                        ]
                    );
                    EXAMPLE,
            );
        }

        $annotationData = array_filter(
            [
                'title' => ($config['name_prefix'] ?? '') . $config['title'],
                'description' => $config['description'],
                'url_scope' => $config['url_scope'],
                'url_category_ids' => $config['url_category_ids'],
                'impact_at' => $config['impact_at'],
                'category_slug' => $config['category_slug'],
                'domains' => array_values((array) $config['domains']),
            ],
            static fn($value) => $value !== null && $value !== [],
        );

        $response = Httpie::post('https://insights.rumvision.com/api/v1/annotations')
            ->setopt(CURLOPT_TIMEOUT, 10)
            ->header('Authorization', sprintf('Bearer %s', $config['api_key']))
            ->jsonBody($annotationData)
            ->getJson();

        if (!isset($response['data']['id'])) {
            throw new \RuntimeException(sprintf('Unable to create annotation: %s', print_r($response, true)));
        }

        writeln(
            sprintf(
                '<info>RUMvision:</info> Annotation <comment>%s</comment> '
                . 'for domain(s): <comment>%s</comment> created successfully.',
                $annotationData['title'],
                implode(', ', $annotationData['domains']),
            ),
        );
    },
);
