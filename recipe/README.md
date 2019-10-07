# Deployer Recipes
`recipe` contains officially supported Deployer recipes.
All of them are based on `common.php` recipe which contains tasks for deployment environment preparation,
loading code, changing files permissions, and much more.


Other recipes can be found in [github.com/deployphp/recipes](https://github.com/deployphp/recipes).


To add support for framework or app create new file, require `recipe/common.php`, and describe `deploy` task.
Take a look at example of `composer.php` recipe.
