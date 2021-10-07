# Config

In the `config:` section of your `yaml` file you set all the required items to start deploying your application.  It starts with `config:` following by the items in configuration in an indented list. Example

```yml
config:
  application: 'domain'
```

All the options for configuration will be discussed below.

## Application

the name of your application is the first item to start with. The domain name of the application is a standard choice:

```yml
application: 'domain'
```

## Respository

Using `repository` you can set the repository you would like to use for deployment. Example:

```yml
repository: 'git@github.com:user/domain.git'
```

## PHP FPM Version 

To set the appropriate PHP FPM version you add a line starting with `php_fpm_version` followed by the version needed. Example:

```yml
php_fpm_version: '7.4'
```

## Releases

How many releases to keep can be set with `keep_releases`:

```yml
keep_releases: '10'
```

## Shared Files

```
shared_files: 
```
