# Example: Plugin system

In this example I will show you how to create a generic plugin system with
événement where plugins can alter the behaviour of the app. The app is a blog.
Boring, I know. By using the EventEmitter it will be easy to extend this blog
with additional functionality without modifying the core system.

The blog is quite basic. Users are able to create blog posts when they log in.
The users are stored in a static config file, so there is no sign up process.
Once logged in they get a "new post" link which gives them a form where they
can create a new blog post with plain HTML. That will store the post in a
document database. The index lists all blog post titles by date descending.
Clicking on the post title will take you to the full post.

## Plugin structure

The goal of the plugin system is to allow features to be added to the blog
without modifying any core files of the blog.

The plugins are managed through a config file, `plugins.json`. This JSON file
contains a JSON-encoded list of class-names for plugin classes. This allows
you to enable and disable plugins in a central location. The initial
`plugins.json` is just an empty array:
```json
[]
```

A plugin class must implement the `PluginInterface`:
```php
interface PluginInterface
{
    function attachEvents(EventEmitterInterface $emitter);
}
```

The `attachEvents` method allows the plugin to attach any events to the
emitter. For example:
```php
class FooPlugin implements PluginInterface
{
    public function attachEvents(EventEmitterInterface $emitter)
    {
        $emitter->on('foo', function () {
            echo 'bar!';
        });
    }
}
```

The blog system creates an emitter instance and loads the plugins:
```php
$emitter = new EventEmitter();

$pluginClasses = json_decode(file_get_contents('plugins.json'), true);
foreach ($pluginClasses as $pluginClass) {
    $plugin = new $pluginClass();
    $pluginClass->attachEvents($emitter);
}
```

This is the base system. There are no plugins yet, and there are no events yet
either. That's because I don't know which extension points will be needed. I
will add them on demand.

## Feature: Markdown

Writing blog posts in HTML sucks! Wouldn't it be great if I could write them
in a nice format such as markdown, and have that be converted to HTML for me?

This feature will need two extension points. I need to be able to mark posts
as markdown, and I need to be able to hook into the rendering of the post body
and convert it from markdown to HTML. So the blog needs two new events:
`post.create` and `post.render`.

In the code that creates the post, I'll insert the `post.create` event:
```php
class PostEvent
{
    public $post;

    public function __construct(array $post)
    {
        $this->post = $post;
    }
}

$post = createPostFromRequest($_POST);

$event = new PostEvent($post);
$emitter->emit('post.create', [$event]);
$post = $event->post;

$db->save('post', $post);
```

This shows that you can wrap a value in an event object to make it mutable,
allowing listeners to change it.

The same thing for the `post.render` event:
```php
public function renderPostBody(array $post)
{
    $emitter = $this->emitter;

    $event = new PostEvent($post);
    $emitter->emit('post.render', [$event]);
    $post = $event->post;

    return $post['body'];
}

<h1><?= $post['title'] %></h1>
<p><?= renderPostBody($post) %></p>
```

Ok, the events are in place. It's time to create the first plugin, woohoo! I
will call this the `MarkdownPlugin`, so here's `plugins.json`:
```json
[
    "MarkdownPlugin"
]
```

The `MarkdownPlugin` class will be autoloaded, so I don't have to worry about
including any files. I just have to worry about implementing the plugin class.
The `markdown` function represents a markdown to HTML converter.
```php
class MarkdownPlugin implements PluginInterface
{
    public function attachEvents(EventEmitterInterface $emitter)
    {
        $emitter->on('post.create', function (PostEvent $event) {
            $event->post['format'] = 'markdown';
        });

        $emitter->on('post.render', function (PostEvent $event) {
            if (isset($event->post['format']) && 'markdown' === $event->post['format']) {
                $event->post['body'] = markdown($event->post['body']);
            }
        });
    }
}
```

There you go, the blog now renders posts as markdown. But all of the previous
posts before the addition of the markdown plugin are still rendered correctly
as raw HTML.

## Feature: Comments

TODO

## Feature: Comment spam control

TODO
