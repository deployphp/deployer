module.exports = [
  'installation',
  'getting-started',
  'basics',
  {
    type: 'category',
    label: 'Main Concepts',
    items: [
      'hosts',
      'tasks',
      'config',
    ],
  },
  'ci-cd',
  'yaml',
  'cli',
  {
    type: 'category',
    label: 'Advanced Guides',
    items: [
      'avoid-php-fpm-reloading',
    ],
  },
  'api',
  {
    type: 'category',
    label: 'Other',
    items: [
      'upgrade_7x',
      'upgrade_6x',
      'upgrade_5x',
      'upgrade_4x',
      'upgrade_3x',
    ],
  },
]
